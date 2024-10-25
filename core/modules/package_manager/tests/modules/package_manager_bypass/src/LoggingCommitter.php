<?php

declare(strict_types=1);

namespace Drupal\package_manager_bypass;

use Drupal\Core\State\StateInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * A composer-stager Committer decorator that adds logging.
 *
 * @internal
 */
final class LoggingCommitter implements CommitterInterface {

  use ComposerStagerExceptionTrait;
  use LoggingDecoratorTrait;

  /**
   * The decorated service.
   *
   * @var \PhpTuf\ComposerStager\API\Core\CommitterInterface
   */
  private $inner;

  /**
   * Constructs a Committer object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \PhpTuf\ComposerStager\API\Core\CommitterInterface $inner
   *   The decorated committer service.
   */
  public function __construct(StateInterface $state, CommitterInterface $inner) {
    $this->state = $state;
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function commit(PathInterface $stagingDir, PathInterface $activeDir, ?PathListInterface $exclusions = NULL, ?OutputCallbackInterface $callback = NULL, ?int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    $this->saveInvocationArguments($stagingDir, $activeDir, $exclusions?->getAll(), $timeout);
    $this->throwExceptionIfSet();
    $this->inner->commit($stagingDir, $activeDir, $exclusions, $callback, $timeout);
  }

}
