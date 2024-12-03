<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Traits;

use Composer\InstalledVersions;
use Drupal\fixture_manipulator\FixtureManipulator;
use Drupal\package_manager\ComposerInspector;
use Symfony\Component\Process\Process;

/**
 * A utility for kernel tests that need to use 'composer/installers'.
 *
 * @internal
 */
trait ComposerInstallersTrait {

  /**
   * Installs the composer/installers package.
   *
   * @param string $dir
   *   The fixture directory to install into.
   */
  private function installComposerInstallers(string $dir): void {
    $package_name = 'composer/installers';
    $this->assertTrue(InstalledVersions::isInstalled($package_name));

    $repository = json_encode([
      'type' => 'path',
      'url' => InstalledVersions::getInstallPath($package_name),
      'options' => [
        'symlink' => FALSE,
        'versions' => [
          // Explicitly state the version contained by this path repository,
          // otherwise Composer will infer the version based on the git clone or
          // fall back to `dev-master`.
          // @see https://getcomposer.org/doc/05-repositories.md#path
          'composer/installers' => InstalledVersions::getVersion($package_name),
        ],
      ],
    ], JSON_UNESCAPED_SLASHES);
    $working_dir_option = "--working-dir=$dir";
    (new Process(['composer', 'config', 'repo.composer-installers-real', $repository, $working_dir_option]))->mustRun();
    (new FixtureManipulator())
      ->addConfig(['allow-plugins.composer/installers' => TRUE])
      ->commitChanges($dir);
    (new Process(['composer', 'require', 'composer/installers:@dev', $working_dir_option]))->mustRun();

    // Use the default installer paths for Drupal core and extensions.
    $this->setInstallerPaths([], $dir);
  }

  /**
   * Sets the installer paths config.
   *
   * @param array $installer_paths
   *   The installed paths.
   * @param string $directory
   *   The fixture directory.
   */
  private function setInstallerPaths(array $installer_paths, string $directory): void {
    // Respect any existing installer paths.
    $extra = $this->container->get(ComposerInspector::class)
      ->getConfig('extra', $directory . '/composer.json');
    $existing_installer_paths = json_decode($extra, TRUE, flags: JSON_THROW_ON_ERROR)['installer-paths'] ?? [];

    (new FixtureManipulator())
      ->addConfig([
        'extra.installer-paths' => $installer_paths + $existing_installer_paths,
      ])
      ->commitChanges($directory)
      ->updateLock();
  }

}
