<?php

declare(strict_types=1);

namespace Drupal\fixture_manipulator;

use Drupal\Core\State\StateInterface;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * A fixture manipulator service that commits changes after begin.
 */
final class StageFixtureManipulator extends FixtureManipulator implements BeginnerInterface {

  /**
   * The state key to use.
   */
  private const STATE_KEY = __CLASS__ . 'MANIPULATOR_ARGUMENTS';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * The decorated service.
   *
   * @var \PhpTuf\ComposerStager\API\Core\BeginnerInterface
   */
  private BeginnerInterface $inner;

  /**
   * Constructions a StageFixtureManipulator object.
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
    $this->inner->begin($activeDir, $stagingDir, $exclusions, $callback, $timeout);
    if ($this->getQueuedManipulationItems()) {
      $this->doCommitChanges($stagingDir->absolute());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function commitChanges(string $dir, bool $validate_composer = FALSE): self {
    throw new \BadMethodCallException('::commitChanges() should not be called directly in StageFixtureManipulator().');
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    // Overrides `__destruct` because the staged fixture manipulator service
    // will be destroyed after every request.
    // @see \Drupal\fixture_manipulator\StageFixtureManipulator::handleTearDown()
  }

  /**
   * Handles test tear down to ensure all changes were committed.
   */
  public static function handleTearDown(): void {
    if (!empty(\Drupal::state()->get(self::STATE_KEY))) {
      throw new \LogicException('The StageFixtureManipulator has arguments that were not cleared. This likely means that the PostCreateEvent was never fired.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function queueManipulation(string $method, array $arguments): void {
    $stored_arguments = $this->getQueuedManipulationItems();
    $stored_arguments[$method][] = $arguments;
    $this->state->set(self::STATE_KEY, $stored_arguments);
  }

  /**
   * {@inheritdoc}
   */
  protected function clearQueuedManipulationItems(): void {
    $this->state->delete(self::STATE_KEY);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueuedManipulationItems(): array {
    return $this->state->get(self::STATE_KEY, []);
  }

}
