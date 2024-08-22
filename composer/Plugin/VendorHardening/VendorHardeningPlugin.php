<?php

namespace Drupal\Composer\Plugin\VendorHardening;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * A Composer plugin to clean out your project's vendor directory.
 *
 * This plugin will remove directory paths within installed packages. You might
 * use this in order to mitigate the security risks of having your vendor
 * directory within an HTTP server's docroot.
 *
 * @see https://www.drupal.org/docs/develop/using-composer/using-drupals-vendor-cleanup-composer-plugin
 *
 * @internal
 */
class VendorHardeningPlugin implements PluginInterface, EventSubscriberInterface {

  /**
   * Composer object.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * IO object.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * Configuration.
   *
   * @var \Drupal\Composer\Plugin\VendorHardening\Config
   */
  protected $config;

  /**
   * List of projects already cleaned.
   *
   * @var string[]
   */
  protected $packagesAlreadyCleaned = [];

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;

    // Set up configuration.
    $this->config = new Config($this->composer->getPackage());
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
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
      ScriptEvents::POST_UPDATE_CMD => 'onPostCmd',
      ScriptEvents::POST_INSTALL_CMD => 'onPostCmd',
      PackageEvents::PRE_PACKAGE_INSTALL => 'onPrePackageInstall',
      PackageEvents::PRE_PACKAGE_UPDATE => 'onPrePackageUpdate',
      PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
      PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
    ];
  }

  /**
   * POST_AUTOLOAD_DUMP event handler.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public function onPostAutoloadDump(Event $event) {
    $this->writeAccessRestrictionFiles($this->composer->getConfig()->get('vendor-dir'));
  }

  /**
   * POST_UPDATE_CMD and POST_INSTALL_CMD event handler.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public function onPostCmd(Event $event) {
    $this->cleanAllPackages();
  }

  /**
   * PRE_PACKAGE_INSTALL event handler.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   The package event.
   */
  public function onPrePackageInstall(PackageEvent $event) {
    /** @var \Composer\Package\CompletePackage $package */
    $package = $event->getOperation()->getPackage();
    $this->removeBinBeforeCleanup($package);
  }

  /**
   * PRE_PACKAGE_UPDATE event handler.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   The package event.
   */
  public function onPrePackageUpdate(PackageEvent $event) {
    /** @var \Composer\Package\CompletePackage $package */
    $package = $event->getOperation()->getTargetPackage();
    $this->removeBinBeforeCleanup($package);
  }

  /**
   * POST_PACKAGE_INSTALL event handler.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   The package event.
   */
  public function onPostPackageInstall(PackageEvent $event) {
    $this->cleanPackage($event->getOperation()->getPackage());
  }

  /**
   * POST_PACKAGE_UPDATE event handler.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   The package event.
   */
  public function onPostPackageUpdate(PackageEvent $event) {
    $this->cleanPackage($event->getOperation()->getTargetPackage());
  }

  /**
   * Remove bin config for packages that would have the bin file removed.
   *
   * Where the configured bin files are in the directories to be removed, remove
   * the bin config.
   *
   * @param \Composer\Package\BasePackage $package
   *   The package we're cleaning up.
   */
  protected function removeBinBeforeCleanup(BasePackage $package) {
    // We can process AliasPackage and Package objects, and they share the
    // BasePackage parent class. However, since there is no common interface for
    // these package types that allow for the setBinaries() method, and since
    // BasePackage does not include the setBinaries() method, we have to make
    // sure we're processing a class with a setBinaries() method.
    if (!method_exists($package, 'setBinaries')) {
      return;
    }
    $binaries = $package->getBinaries();
    $clean_paths = $this->config->getPathsForPackage($package->getName());
    // Only do this if there are binaries and cleanup paths.
    if (!$binaries || !$clean_paths) {
      return;
    }
    if ($unset_these_binaries = $this->findBinOverlap($binaries, $clean_paths)) {
      $this->io->writeError(
        sprintf('%sModifying bin config for <info>%s</info> which overlaps with cleanup directories.', str_repeat(' ', 4), $package->getName()),
        TRUE,
        IOInterface::VERBOSE
      );
      $modified_binaries = [];
      foreach ($binaries as $binary) {
        if (!in_array($binary, $unset_these_binaries)) {
          $modified_binaries[] = $binary;
        }
      }
      $package->setBinaries($modified_binaries);
    }
  }

  /**
   * Find bin files which are inside cleanup directories.
   *
   * @param string[] $binaries
   *   'Bin' configuration from the package we're cleaning up.
   * @param string[] $clean_paths
   *   The paths we're cleaning up.
   *
   * @return string[]
   *   Bin files to remove, with the file as both the key and the value.
   */
  protected function findBinOverlap($binaries, $clean_paths) {
    // Make a filesystem model to explore. This is a keyed array that looks like
    // all the places that will be removed by cleanup. 'tests/src' becomes
    // $filesystem['tests']['src'] = TRUE;
    $filesystem = [];
    foreach ($clean_paths as $clean_path) {
      $clean_pieces = explode("/", $clean_path);
      // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
      $current = &$filesystem;
      foreach ($clean_pieces as $clean_piece) {
        $current = &$current[$clean_piece];
      }
      $current = TRUE;
    }
    // Explore the filesystem with our bin config.
    $unset_these_binaries = [];
    foreach ($binaries as $binary) {
      $binary_pieces = explode('/', $binary);
      $current = &$filesystem;
      foreach ($binary_pieces as $binary_piece) {
        if (!isset($current[$binary_piece])) {
          break;
        }
        else {
          // Value of TRUE means we're at the end of the path.
          if ($current[$binary_piece] === TRUE) {
            $unset_these_binaries[$binary] = $binary;
            break;
          }
        }
        $current = &$filesystem[$binary_piece];
      }
    }
    return $unset_these_binaries;
  }

  /**
   * Gets a list of all installed packages from Composer.
   *
   * @return \Composer\Package\PackageInterface[]
   *   The list of installed packages.
   */
  protected function getInstalledPackages() {
    return $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
  }

  /**
   * Gets the installed path for a package.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package.
   *
   * @return string
   *   Path to the install path for the package, relative to the project. This
   *   accounts for changes made by composer/installers, if any.
   */
  protected function getInstallPathForPackage(PackageInterface $package) {
    return $this->composer->getInstallationManager()->getInstallPath($package);
  }

  /**
   * Clean all configured packages.
   *
   * This applies in the context of a post-command event.
   */
  public function cleanAllPackages() {
    // Get a list of all the packages available after the update or install
    // command.
    $installed_packages = [];
    foreach ($this->getInstalledPackages() as $package) {
      // Normalize package names to lower case.
      $installed_packages[strtolower($package->getName())] = $package;
    }

    $all_cleanup_paths = $this->config->getAllCleanupPaths();

    // Get all the packages that we should clean up but haven't already.
    $cleanup_paths = array_diff_key($all_cleanup_paths, $this->packagesAlreadyCleaned);

    // Get all the packages that are installed that we should clean up.
    $packages_to_be_cleaned = array_intersect_key($cleanup_paths, $installed_packages);

    if (!$packages_to_be_cleaned) {
      $this->io->writeError('<info>Packages already clean.</info>');
      return;
    }
    $this->io->writeError('<info>Cleaning installed packages.</info>');

    foreach ($packages_to_be_cleaned as $package_name => $paths) {
      $this->cleanPathsForPackage($installed_packages[$package_name], $all_cleanup_paths[$package_name]);
    }
  }

  /**
   * Clean a single package.
   *
   * This applies in the context of a package post-install or post-update event.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to clean.
   */
  public function cleanPackage(PackageInterface $package) {
    // Normalize package names to lower case.
    $package_name = strtolower($package->getName());
    if (isset($this->packagesAlreadyCleaned[$package_name])) {
      $this->io->writeError(sprintf('%s<info>%s</info> already cleaned.', str_repeat(' ', 4), $package_name), TRUE, IOInterface::VERY_VERBOSE);
      return;
    }

    $paths_for_package = $this->config->getPathsForPackage($package_name);
    if ($paths_for_package) {
      $this->io->writeError(sprintf('%sCleaning: <info>%s</info>', str_repeat(' ', 4), $package_name));
      $this->cleanPathsForPackage($package, $paths_for_package);
    }
  }

  /**
   * Clean the installed directories for a named package.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to clean.
   * @param string[] $paths_for_package
   *   List of directories in $package_name to remove
   */
  protected function cleanPathsForPackage(PackageInterface $package, $paths_for_package) {
    // Whatever happens here, this package counts as cleaned so that we don't
    // process it more than once.
    $package_name = strtolower($package->getName());
    $this->packagesAlreadyCleaned[$package_name] = TRUE;

    $package_dir = $this->getInstallPathForPackage($package);
    if (!is_dir($package_dir)) {
      return;
    }

    $this->io->writeError(sprintf('%sCleaning paths in <comment>%s</comment>', str_repeat(' ', 4), $package_name), TRUE, IOInterface::VERY_VERBOSE);
    $fs = new Filesystem();
    foreach ($paths_for_package as $cleanup_item) {
      $cleanup_path = $package_dir . '/' . $cleanup_item;
      if (!file_exists($cleanup_path)) {
        // If the package has changed or the --prefer-dist version does not
        // include the directory. This is not an error.
        $this->io->writeError(sprintf("%s<comment>Path '%s' does not exist.</comment>", str_repeat(' ', 6), $cleanup_path), TRUE, IOInterface::VERY_VERBOSE);
        continue;
      }

      if (!$fs->remove($cleanup_path)) {
        // Always display a message if this fails as it means something
        // has gone wrong. Therefore the message has to include the
        // package name as the first informational message might not
        // exist.
        $this->io->writeError(sprintf("%s<error>Failure removing path '%s'</error> in package <comment>%s</comment>.", str_repeat(' ', 6), $cleanup_item, $package_name), TRUE, IOInterface::NORMAL);
        continue;
      }

      $this->io->writeError(sprintf("%sRemoving path <info>'%s'</info>", str_repeat(' ', 4), $cleanup_item), TRUE, IOInterface::VERBOSE);
    }
  }

  /**
   * Place .htaccess and web.config files into the vendor directory.
   *
   * @param string $vendor_dir
   *   Path to vendor directory.
   */
  public function writeAccessRestrictionFiles($vendor_dir) {
    $this->io->writeError('<info>Hardening vendor directory with .htaccess and web.config files.</info>');
    // Prevent access to vendor directory on Apache servers.
    FileSecurity::writeHtaccess($vendor_dir, TRUE);

    // Prevent access to vendor directory on IIS servers.
    FileSecurity::writeWebConfig($vendor_dir);
  }

}
