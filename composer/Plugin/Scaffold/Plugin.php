<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Filesystem;
use Drupal\Composer\Plugin\Scaffold\CommandProvider as ScaffoldCommandProvider;

/**
 * Composer plugin for handling drupal scaffold.
 *
 * @internal
 */
class Plugin implements PluginInterface, EventSubscriberInterface, Capable {

  /**
   * The Composer service.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * Composer's I/O service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * The Composer Scaffold handler.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\Handler
   */
  protected $handler;

  /**
   * Record whether the 'require' command was called.
   *
   * @var bool
   */
  protected $requireWasCalled;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->requireWasCalled = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCapabilities() {
    return [CommandProvider::class => ScaffoldCommandProvider::class];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Important note: We only instantiate our handler on "post" events.
    return [
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
      ScriptEvents::POST_INSTALL_CMD => 'postCmd',
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PluginEvents::COMMAND => 'onCommand',
      ScriptEvents::PRE_AUTOLOAD_DUMP => 'preAutoloadDump',
    ];
  }

  /**
   * Post command event callback.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public function postCmd(Event $event) {
    $this->handler()->scaffold();
    $this->ensureAutoloadRuntimeFile();
  }

  /**
   * Generates autoload_runtime.php when Handler::scaffold() did not.
   *
   * When this plugin package is itself updated during a Composer command,
   * the Handler class may already have been loaded from the previous version
   * of the plugin (via postPackage()) before the plugin files were replaced
   * on disk. PHP keeps the loaded class for the rest of the process, so
   * Handler::scaffold() then runs the old code, which does not generate the
   * autoload_runtime.php file (introduced in Drupal 11.4.0).
   *
   * Composer reloads this Plugin class from the new plugin code after the
   * plugin package is updated. It must only use other classes of this plugin
   * through APIs that exist in every version of the plugin that can be updated
   * from, because those classes may still be loaded from a previous version.
   *
   * @todo Remove after upgrades from Drupal 11.3 are no longer supported.
   *
   * @see \Composer\Plugin\PluginManager::registerPackage()
   * @see https://www.drupal.org/project/drupal/issues/3607866
   */
  protected function ensureAutoloadRuntimeFile(): void {
    $manage_options = new ManageOptions($this->composer);
    $allowed_packages = (new AllowedPackages($this->composer, $this->io, $manage_options))->getAllowedPackages();
    if (empty($allowed_packages)) {
      return;
    }

    $web_root = $manage_options->getOptions()->getLocation('web-root');
    if (file_exists($web_root . '/autoload_runtime.php')) {
      return;
    }

    $vendor_dir = $this->composer->getConfig()->get('vendor-dir');
    $vendor_path = (new Filesystem())->normalizePath(realpath($vendor_dir));
    $package_name = $this->composer->getPackage()->getName();
    $result = GenerateAutoloadRuntimeReferenceFile::generateAutoloadRuntime($this->io, $package_name, $web_root, $vendor_path);
    $git_ignore_manager = new ManageGitIgnore($this->io, getcwd());
    $git_ignore_manager->manageIgnored([$result], $manage_options->getOptions());
  }

  /**
   * Post package event behavior.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Composer package event sent on install/update/remove.
   */
  public function postPackage(PackageEvent $event) {
    $this->handler()->onPostPackageEvent($event);
  }

  /**
   * Pre command event callback.
   *
   * @param \Composer\Plugin\CommandEvent $event
   *   The Composer command event.
   */
  public function onCommand(CommandEvent $event) {
    if ($event->getCommandName() == 'require') {
      if ($this->handler) {
        throw new \Error('Core Scaffold Plugin handler instantiated too early. See https://www.drupal.org/project/drupal/issues/3104922');
      }
      $this->requireWasCalled = TRUE;
    }
  }

  /**
   * Instantiates the handler object upon demand.
   *
   * It is dangerous to update a Composer plugin if it loads any classes prior
   * to the `composer update` operation, and later tries to use them in a
   * post-update hook.
   */
  protected function handler() {
    if (!$this->handler) {
      $this->handler = new Handler($this->composer, $this->io);
      // On instantiation of our handler, notify it if the 'require' command
      // was executed.
      if ($this->requireWasCalled) {
        $this->handler->requireWasCalled();
      }
    }
    return $this->handler;
  }

  /**
   * Add vendor classes to Composer's static classmap.
   *
   * @param \Composer\Script\Event $event
   *   The event.
   */
  public static function preAutoloadDump(Event $event): void {
    // Get the configured vendor directory.
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    // We need the root_package package so we can add our classmaps to its
    // loader.
    $package = $event->getComposer()->getPackage();
    // We need the local repository so that we can query and see if it's likely
    // that our files are present there.
    $repository = $event->getComposer()->getRepositoryManager()->getLocalRepository();
    // This is, essentially, a null constraint. We only care whether the package
    // is present in the vendor directory yet, but findPackage() requires it.
    $constraint = new Constraint('>', '');
    // It's possible that there is no classmap specified in a custom project
    // composer.json file. We need one so we can optimize lookup for some of our
    // dependencies.
    $autoload = $package->getAutoload();
    $autoload['classmap'] ??= [];
    // Check for packages used prior to the default classloader being able to
    // use APCu and optimize them if they're present.
    // @see \Drupal\Core\DrupalKernel::boot()
    if ($repository->findPackage('symfony/http-foundation', $constraint)) {
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        $vendor_dir . '/symfony/http-foundation/Request.php',
        $vendor_dir . '/symfony/http-foundation/RequestStack.php',
        $vendor_dir . '/symfony/http-foundation/ParameterBag.php',
        $vendor_dir . '/symfony/http-foundation/FileBag.php',
        $vendor_dir . '/symfony/http-foundation/ServerBag.php',
        $vendor_dir . '/symfony/http-foundation/HeaderBag.php',
        $vendor_dir . '/symfony/http-foundation/HeaderUtils.php',
      ]);
    }
    if ($repository->findPackage('symfony/http-kernel', $constraint)) {
      $autoload['classmap'] = array_merge($autoload['classmap'], [
        $vendor_dir . '/symfony/http-kernel/HttpKernel.php',
        $vendor_dir . '/symfony/http-kernel/HttpKernelInterface.php',
        $vendor_dir . '/symfony/http-kernel/TerminableInterface.php',
      ]);
    }
    if ($repository->findPackage('symfony/dependency-injection', $constraint)) {
      $autoload['classmap'][] = $vendor_dir . '/symfony/dependency-injection/ContainerInterface.php';
    }
    if ($repository->findPackage('psr/container', $constraint)) {
      $autoload['classmap'][] = $vendor_dir . '/psr/container/src/ContainerInterface.php';
    }

    $filesystem = new Filesystem();
    // Do not remove double realpath() calls.
    // Fixes failing Windows realpath() implementation.
    // See https://bugs.php.net/bug.php?id=72738
    $vendor_path = realpath(realpath($vendor_dir));
    $filesystem->ensureDirectoryExists($vendor_path . '/drupal');
    // Create the Drupal\DrupalInstalled class.
    file_put_contents($vendor_path . '/drupal/DrupalInstalled.php', DrupalInstalledTemplate::getCode($package, $repository));
    $autoload['classmap'][] = $vendor_dir . '/drupal/DrupalInstalled.php';

    $package->setAutoload($autoload);
  }

}
