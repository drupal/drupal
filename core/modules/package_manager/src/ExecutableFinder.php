<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\InstalledVersions;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * An executable finder which looks for executable paths in configuration.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ExecutableFinder implements ExecutableFinderInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The path where Composer is installed in the project, or FALSE if it's not.
   */
  private string|false|null $composerPackagePath = NULL;

  /**
   * The path of the Composer binary, or NULL if it can't be found.
   */
  private ?string $composerBinaryPath = NULL;

  public function __construct(
    private readonly ExecutableFinderInterface $decorated,
    private readonly FileSystemInterface $fileSystem,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->composerPackagePath = InstalledVersions::isInstalled('composer/composer')
      ? InstalledVersions::getInstallPath('composer/composer')
      : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $name): string {

    if ($name === 'rsync') {
      return Settings::get('package_manager_rsync_path', $this->decorated->find($name));
    }
    // If we're looking for Composer, use the project's local copy if available.
    elseif ($name === 'composer') {
      $path = $this->getLocalComposerPath();

      if ($path && file_exists($path)) {
        return $path;
      }

      // Find the regular Composer executable overridden by a setting.
      return Settings::get('package_manager_composer_path', $this->decorated->find($name));
    }
    return $this->decorated->find($name);
  }

  /**
   * Tries to find the Composer binary installed in the project.
   *
   * @return string|null
   *   The path of the `composer` binary installed in the project's vendor
   *   dependencies, or NULL if it is not installed or cannot be found.
   */
  private function getLocalComposerPath(): ?string {
    // Composer is not installed in the project, so there's nothing to do.
    if ($this->composerPackagePath === FALSE) {
      return NULL;
    }

    // This is a bit expensive to compute, so statically cache it.
    if ($this->composerBinaryPath) {
      return $this->composerBinaryPath;
    }

    $composer_json = file_get_contents($this->composerPackagePath . '/composer.json');
    $composer_json = Json::decode($composer_json);

    foreach ($composer_json['bin'] ?? [] as $bin) {
      if (str_ends_with($bin, '/composer')) {
        $bin = $this->composerPackagePath . '/' . $bin;

        // For extra security, try to disable the binary's execute permission.
        // If that fails, it's worth warning about but is not an actual problem.
        if (is_executable($bin) && !$this->fileSystem->chmod($bin, 0644)) {
          $this->logger?->warning('Composer was found at %path, but could not be made read-only.', [
            '%path' => $bin,
          ]);
        }
        return $this->composerBinaryPath = $bin;
      }
    }
    return NULL;
  }

}
