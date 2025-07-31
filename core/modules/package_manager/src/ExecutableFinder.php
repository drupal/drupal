<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\InstalledVersions;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
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
  private string|false $composerPackagePath;

  /**
   * The path of the Composer binary, or NULL if it can't be found.
   */
  private ?string $composerBinaryPath = NULL;

  public function __construct(
    private readonly ExecutableFinderInterface $decorated,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileSystemInterface $fileSystem,
  ) {
    $this->composerPackagePath = InstalledVersions::isInstalled('composer/composer')
      ? InstalledVersions::getInstallPath('composer/composer')
      : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $name): string {
    $executables = $this->configFactory->get('package_manager.settings')
      ->get('executables');

    if (isset($executables[$name])) {
      return $executables[$name];
    }

    // If we're looking for Composer, use the project's local copy if available.
    if ($name === 'composer') {
      $path = $this->getLocalComposerPath();

      if ($path && file_exists($path)) {
        // For extra security, try to make the file read-only rather than
        // directly executable. If that fails, it's worth warning about but is
        // not an actual problem.
        if (is_executable($path) && !$this->fileSystem->chmod($path, 0644)) {
          $this->logger?->warning('Composer was found at %path, but could not be made read-only.', [
            '%path' => $path,
          ]);
        }
        return $path;
      }
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
        $this->composerBinaryPath = $this->composerPackagePath . '/' . $bin;
        break;
      }
    }
    return $this->composerBinaryPath;
  }

}
