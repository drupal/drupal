<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

/**
 * A chronological list of Checkpoint objects.
 *
 * @internal
 *   This API is experimental.
 */
final class LinearHistory implements CheckpointListInterface {

  /**
   * The store of all the checkpoint names in state.
   */
  private const CHECKPOINT_KEY = 'config.checkpoints';

  /**
   * The active checkpoint.
   *
   * In our implementation this is always the last in the list.
   *
   * @var \Drupal\Core\Config\Checkpoint\Checkpoint|null
   */
  private ?Checkpoint $activeCheckpoint;

  /**
   * The list of checkpoints, keyed by ID.
   *
   * @var \Drupal\Core\Config\Checkpoint\Checkpoint[]
   */
  private array $checkpoints;

  /**
   * Constructs a checkpoints object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly TimeInterface $time,
  ) {
    $this->checkpoints = $this->state->get(self::CHECKPOINT_KEY, []);
    $this->activeCheckpoint = end($this->checkpoints) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveCheckpoint(): ?Checkpoint {
    return $this->activeCheckpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $id): Checkpoint {
    if (!isset($this->checkpoints[$id])) {
      throw new UnknownCheckpointException(sprintf('The checkpoint "%s" does not exist', $id));
    }
    return $this->checkpoints[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getParents(string $id): \Traversable {
    if (!isset($this->checkpoints[$id])) {
      throw new UnknownCheckpointException(sprintf('The checkpoint "%s" does not exist', $id));
    }
    $checkpoint = $this->checkpoints[$id];
    while ($checkpoint->parent !== NULL) {
      $checkpoint = $this->get($checkpoint->parent);
      yield $checkpoint->id => $checkpoint;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->checkpoints);
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return count($this->checkpoints);
  }

  /**
   * {@inheritdoc}
   */
  public function add(string $id, string|\Stringable $label): Checkpoint {
    if (isset($this->checkpoints[$id])) {
      throw new CheckpointExistsException(sprintf('Cannot create a checkpoint with the ID "%s" as it already exists', $id));
    }
    $checkpoint = new Checkpoint($id, $label, $this->time->getCurrentTime(), $this->activeCheckpoint?->id);
    $this->checkpoints[$checkpoint->id] = $checkpoint;
    $this->activeCheckpoint = $checkpoint;
    $this->state->set(self::CHECKPOINT_KEY, $this->checkpoints);

    return $checkpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $id): static {
    if (!isset($this->checkpoints[$id])) {
      throw new UnknownCheckpointException(sprintf('Cannot delete a checkpoint with the ID "%s" as it does not exist', $id));
    }

    foreach ($this->checkpoints as $key => $checkpoint) {
      unset($this->checkpoints[$key]);
      if ($checkpoint->id === $id) {
        break;
      }
    }
    $this->state->set(self::CHECKPOINT_KEY, $this->checkpoints);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll(): static {
    $this->checkpoints = [];
    $this->activeCheckpoint = NULL;
    $this->state->delete(self::CHECKPOINT_KEY);
    return $this;
  }

}
