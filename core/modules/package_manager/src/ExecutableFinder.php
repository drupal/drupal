<?php

declare(strict_types=1);

namespace Drupal\package_manager;

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

  public function __construct(
    private readonly ExecutableFinderInterface $decorated,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function find(string $name): string {
    $executables = $this->configFactory->get('package_manager.settings')
      ->get('executables');

    return $executables[$name] ?? $this->decorated->find($name);
  }

}
