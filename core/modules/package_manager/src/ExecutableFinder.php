<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\InstalledVersions;
use Drupal\Core\Config\ConfigFactoryInterface;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * An executable finder which looks for executable paths in configuration.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ExecutableFinder implements ExecutableFinderInterface {

  /**
   * The path where Composer is installed in the project, or FALSE if it's not.
   */
  private string|false|null $composerPath = NULL;

  public function __construct(
    private readonly ExecutableFinderInterface $decorated,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->composerPath = InstalledVersions::isInstalled('composer/composer')
      ? InstalledVersions::getInstallPath('composer/composer') . '/bin/composer'
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
    if ($name === 'composer' && $this->composerPath && file_exists($this->composerPath)) {
      return $this->composerPath;
    }
    return $this->decorated->find($name);
  }

}
