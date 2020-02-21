<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\CommandEvent;
use Composer\Util\Filesystem;
use Drupal\Composer\Plugin\Scaffold\Operations\OperationData;
use Drupal\Composer\Plugin\Scaffold\Operations\OperationFactory;
use Drupal\Composer\Plugin\Scaffold\Operations\ScaffoldFileCollection;

/**
 * Core class of the plugin.
 *
 * Contains the primary logic which determines the files to be fetched and
 * processed.
 *
 * @internal
 */
class Handler {

  /**
   * Composer hook called before scaffolding begins.
   */
  const PRE_DRUPAL_SCAFFOLD_CMD = 'pre-drupal-scaffold-cmd';

  /**
   * Composer hook called after scaffolding completes.
   */
  const POST_DRUPAL_SCAFFOLD_CMD = 'post-drupal-scaffold-cmd';

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
   * The scaffold options in the top-level composer.json's 'extra' section.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\ManageOptions
   */
  protected $manageOptions;

  /**
   * The manager that keeps track of which packages are allowed to scaffold.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\AllowedPackages
   */
  protected $manageAllowedPackages;

  /**
   * The list of listeners that are notified after a package event.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\PostPackageEventListenerInterface[]
   */
  protected $postPackageListeners = [];

  /**
   * Handler constructor.
   *
   * @param \Composer\Composer $composer
   *   The Composer service.
   * @param \Composer\IO\IOInterface $io
   *   The Composer I/O service.
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->manageOptions = new ManageOptions($composer);
    $this->manageAllowedPackages = new AllowedPackages($composer, $io, $this->manageOptions);
  }

  /**
   * Registers post-package events before any 'require' event runs.
   *
   * This method is called by composer prior to doing a 'require' command.
   *
   * @param \Composer\Plugin\CommandEvent $event
   *   The Composer Command event.
   */
  public function beforeRequire(CommandEvent $event) {
    // In order to differentiate between post-package events called after
    // 'composer require' vs. the same events called at other times, we will
    // only install our handler when a 'require' event is detected.
    $this->postPackageListeners[] = $this->manageAllowedPackages;
  }

  /**
   * Posts package command event.
   *
   * We want to detect packages 'require'd that have scaffold files, but are not
   * yet allowed in the top-level composer.json file.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Composer package event sent on install/update/remove.
   */
  public function onPostPackageEvent(PackageEvent $event) {
    foreach ($this->postPackageListeners as $listener) {
      $listener->event($event);
    }
  }

  /**
   * Creates scaffold operation objects for all items in the file mappings.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param array $package_file_mappings
   *   The package file mappings array keyed by destination path and the values
   *   are operation metadata arrays.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface[]
   *   A list of scaffolding operation objects
   */
  protected function createScaffoldOperations(PackageInterface $package, array $package_file_mappings) {
    $scaffold_op_factory = new OperationFactory($this->composer);
    $scaffold_ops = [];
    foreach ($package_file_mappings as $dest_rel_path => $data) {
      $operation_data = new OperationData($dest_rel_path, $data);
      $scaffold_ops[$dest_rel_path] = $scaffold_op_factory->create($package, $operation_data);
    }
    return $scaffold_ops;
  }

  /**
   * Copies all scaffold files from source to destination.
   */
  public function scaffold() {
    // Recursively get the list of allowed packages. Only allowed packages
    // may declare scaffold files. Note that the top-level composer.json file
    // is implicitly allowed.
    $allowed_packages = $this->manageAllowedPackages->getAllowedPackages();
    if (empty($allowed_packages)) {
      $this->io->write("Nothing scaffolded because no packages are allowed in the top-level composer.json file.");
      return;
    }

    // Call any pre-scaffold scripts that may be defined.
    $dispatcher = new EventDispatcher($this->composer, $this->io);
    $dispatcher->dispatch(self::PRE_DRUPAL_SCAFFOLD_CMD);

    // Fetch the list of file mappings from each allowed package and normalize
    // them.
    $file_mappings = $this->getFileMappingsFromPackages($allowed_packages);

    $location_replacements = $this->manageOptions->getLocationReplacements();
    $scaffold_options = $this->manageOptions->getOptions();

    // Create a collection of scaffolded files to process. This determines which
    // take priority and which are conjoined.
    $scaffold_files = new ScaffoldFileCollection($file_mappings, $location_replacements);

    // Process the list of scaffolded files.
    $scaffold_results = ScaffoldFileCollection::process($scaffold_files, $this->io, $scaffold_options);

    // Generate an autoload file in the document root that includes the
    // autoload.php file in the vendor directory, wherever that is. Drupal
    // requires this in order to easily locate relocated vendor dirs.
    $web_root = $this->manageOptions->getOptions()->getLocation('web-root');
    if (!GenerateAutoloadReferenceFile::autoloadFileCommitted($this->io, $this->rootPackageName(), $web_root)) {
      $scaffold_results[] = GenerateAutoloadReferenceFile::generateAutoload($this->io, $this->rootPackageName(), $web_root, $this->getVendorPath());
    }

    // Add the managed scaffold files to .gitignore if applicable.
    $gitIgnoreManager = new ManageGitIgnore($this->io, getcwd());
    $gitIgnoreManager->manageIgnored($scaffold_results, $scaffold_options);

    // Call post-scaffold scripts.
    $dispatcher->dispatch(self::POST_DRUPAL_SCAFFOLD_CMD);
  }

  /**
   * Gets the path to the 'vendor' directory.
   *
   * @return string
   *   The file path of the vendor directory.
   */
  protected function getVendorPath() {
    $vendor_dir = $this->composer->getConfig()->get('vendor-dir');
    $filesystem = new Filesystem();
    return $filesystem->normalizePath(realpath($vendor_dir));
  }

  /**
   * Gets a consolidated list of file mappings from all allowed packages.
   *
   * @param \Composer\Package\Package[] $allowed_packages
   *   A multidimensional array of file mappings, as returned by
   *   self::getAllowedPackages().
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface[]
   *   An array of destination paths => scaffold operation objects.
   */
  protected function getFileMappingsFromPackages(array $allowed_packages) {
    $file_mappings = [];
    foreach ($allowed_packages as $package_name => $package) {
      $file_mappings[$package_name] = $this->getPackageFileMappings($package);
    }
    return $file_mappings;
  }

  /**
   * Gets the array of file mappings provided by a given package.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The Composer package from which to get the file mappings.
   *
   * @return \Drupal\Composer\Plugin\Scaffold\Operations\OperationInterface[]
   *   An array of destination paths => scaffold operation objects.
   */
  protected function getPackageFileMappings(PackageInterface $package) {
    $options = $this->manageOptions->packageOptions($package);
    if ($options->hasFileMapping()) {
      return $this->createScaffoldOperations($package, $options->fileMapping());
    }
    // Warn the user if they allow a package that does not have any scaffold
    // files. We will ignore drupal/core, though, as it is implicitly allowed,
    // but might not have scaffold files (version 8.7.x and earlier).
    if (!$options->hasAllowedPackages() && ($package->getName() != 'drupal/core')) {
      $this->io->writeError("The allowed package {$package->getName()} does not provide a file mapping for Composer Scaffold.");
    }
    return [];
  }

  /**
   * Gets the root package name.
   *
   * @return string
   *   The package name of the root project
   */
  protected function rootPackageName() {
    $root_package = $this->composer->getPackage();
    return $root_package->getName();
  }

}
