<?php

declare(strict_types=1);

namespace Drupal\package_manager_bypass;

use Drupal\Core\State\StateInterface;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * A composer-stager Beginner decorator that adds logging.
 *
 * @internal
 */
final class LoggingBeginner implements BeginnerInterface {

  use ComposerStagerExceptionTrait;
  use LoggingDecoratorTrait;

  /**
   * The decorated service.
   *
   * @var \PhpTuf\ComposerStager\API\Core\BeginnerInterface
   */
  private $inner;

  /**
   * Constructs a Beginner object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \PhpTuf\ComposerStager\API\Core\BeginnerInterface $inner
   *   The decorated beginner service.
   */
  public function __construct(StateInterface $state, BeginnerInterface $inner) {
    $this->state = $state;
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function begin(PathInterface $activeDir, PathInterface $stagingDir, ?PathListInterface $exclusions = NULL, ?OutputCallbackInterface $callback = NULL, ?int $timeout = ProcessInterface::DEFAULT_TIMEOUT): void {
    $this->saveInvocationArguments($activeDir, $stagingDir, $exclusions?->getAll(), $timeout);
    $this->throwExceptionIfSet();
    $this->inner->begin($activeDir, $stagingDir, $exclusions, $callback, $timeout);
  }

}
