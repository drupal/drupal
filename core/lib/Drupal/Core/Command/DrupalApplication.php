<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Customize the Symfony Application for Drupal.
 *
 * @ingroup console_commands
 */
class DrupalApplication extends Application {

  /**
   * Flag to remember if Drupal has already been bootstrapped.
   */
  protected bool $booted = FALSE;

  public function __construct(
    protected ClassLoader $classloader,
    protected array $context,
  ) {
    parent::__construct('Drupal', \Drupal::VERSION);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultInputDefinition(): InputDefinition {
    $definition = parent::getDefaultInputDefinition();
    $definition->addOption(new InputOption('url', NULL, InputOption::VALUE_REQUIRED, 'A base URL (e.g. example.com). Used for building links, selecting a multi-site, etc.'));
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands(): array {
    $default_commands = parent::getDefaultCommands();

    // Commands registered here are available even if Drupal does not boot.
    // Only register commands here that do not need to bootstrap Drupal.
    $default_commands[] = new QuickStartCommand();
    $default_commands[] = new InstallCommand($this->classloader);
    $default_commands[] = new CacheRebuildCommand($this->classloader);
    $default_commands[] = new ServerCommand($this->classloader);
    $default_commands[] = new GenerateTheme();

    return $default_commands;
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $name): Command {
    try {
      $command = parent::find($name);
      if ($command instanceof ListCommand || $command instanceof HelpCommand) {
        $this->bootstrap();
      }
      return $command;
    }
    catch (CommandNotFoundException $e) {
      if (!$this->bootstrap()) {
        throw $e;
      }
      return parent::find($name);
    }
  }

  /**
   * Bootstraps Drupal in a way that is appropriate for console commands.
   */
  protected function bootstrap(): bool {
    if ($this->booted) {
      return TRUE;
    }
    try {
      $request = Request::create($this->getUri());

      // Discovery can get out of whack if cleared caches and try to run this
      // command without a web request priming discovery.
      chdir(\DRUPAL_ROOT);
      // We need to not load a cached copy of the container from disk. For
      // example, inside Kernel tests, we need to fully build the container so
      // we discover and register commands, instead of reusing the container
      // from the Kernel test itself. Therefore, we pass `FALSE` for the
      // `$allow_dumping` parameter here.
      $kernel = new DrupalKernel('prod', $this->classloader, FALSE);
      // We tried calling `DrupalKernel::bootEnvironment()` right here to setup
      // some common environment and PHP initialization steps. However, that
      // method also calls `set_error_handler('_drupal_error_handler')` which
      // creates problems when running commands inside of tests. Since
      // `bootEnvironment()` has a static flag to only happen once, it's hard to
      // restore the previous error handler after running commands, and we don't
      // want to make tests responsible for restoring the error handler.
      // @todo Refactor `bootEnvironment()' so that we can use the parts we need
      // but leave the error handler alone.
      // @see https://www.drupal.org/node/2690035
      // Define the DRUPAL_TEST_IN_CHILD_SITE based on if we're inside a test.
      DrupalKernel::setupDrupalTestInChildSite($kernel->getAppRoot());
      $sitePath = getenv('DRUPAL_DEV_SITE_PATH') ?: DrupalKernel::findSitePath($request, TRUE);
      $kernel->setSitePath($sitePath);
      Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $this->classloader);
      $kernel->boot();

      $container = $kernel->getContainer();
      $container->get('request_stack')->push($request);
      // Set base URL - needed when in a subdir e.g. http://example.com/subdir.
      $container->get('router.request_context')->setBaseUrl($request->getPathInfo());

      // This sets things up, esp loadLegacyIncludes().
      $kernel->preHandle($request);

      // Adds commands as part of installed modules.
      $this->addDrupalCommands($container);

      // Dispatch standard console events.
      $this->setDispatcher($container->get('event_dispatcher'));
    }
    catch (\Throwable) {
      return FALSE;
    }
    $this->booted = TRUE;
    return TRUE;
  }

  /**
   * Gets the URI for the request.
   *
   * @return string
   *   The base URL for this request.
   */
  public function getUri(): string {
    $argv = new ArgvInput();
    return $argv->getParameterOption('--url') ?: $this->context['DRUPAL_URI'] ?? 'http://localhost';
  }

  /**
   * Discovers commands that are provided by installed modules.
   */
  protected function addDrupalCommands(ContainerInterface $container): void {
    $this->setCommandLoader($container->get('console.command_loader'));
    // Add commands that don't use an attribute, relying solely on configure().
    if ($container->hasParameter('console.command.ids')) {
      foreach ($container->getParameter('console.command.ids') as $id) {
        $this->addCommand($container->get($id));
      }
    }
  }

}
