<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;

/**
 * StagingDirDoesNotExist override to support long-lived sandbox directory.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class StagingDirDoesNotExist implements StagingDirDoesNotExistInterface {

  /**
   * {@inheritdoc}
   */
  public function getName(): TranslatableInterface {
    return new TranslatableStringAdapter('Staging directory does not exist');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableInterface {
    return new TranslatableStringAdapter('The staging directory must not already exist before beginning the staging process.');
  }

  /**
   * Returns the status message when the precondition is fulfilled.
   *
   * @return \PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface
   *   The status message.
   */
  protected function getFulfilledStatusMessage(): TranslatableInterface {
    return new TranslatableStringAdapter('The staging directory does not already exist.');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMessage(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): TranslatableInterface {
    return $this->getFulfilledStatusMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function assertIsFulfilled(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {}

  /**
   * {@inheritdoc}
   */
  public function getLeaves(): array {
    return [$this];
  }

}
