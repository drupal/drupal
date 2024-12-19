<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

/**
 * Maintains a list of checkpoints.
 *
 * @internal
 *   This API is experimental.
 *
 * @see \Drupal\Core\Config\Checkpoint\Checkpoint
 *
 * @phpstan-extends \IteratorAggregate<string, \Drupal\Core\Config\Checkpoint\Checkpoint>
 */
interface CheckpointListInterface extends \IteratorAggregate, \Countable {

  /**
   * Gets the active checkpoint.
   *
   * @return \Drupal\Core\Config\Checkpoint\Checkpoint|null
   *   The active checkpoint or NULL if there are no checkpoints.
   */
  public function getActiveCheckpoint(): ?Checkpoint;

  /**
   * Gets a checkpoint.
   *
   * @param string $id
   *   The checkpoint ID.
   *
   * @return \Drupal\Core\Config\Checkpoint\Checkpoint
   *   The checkpoint.
   *
   * @throws \Drupal\Core\Config\Checkpoint\UnknownCheckpointException
   *   Thrown when the provided checkpoint does not exist.
   */
  public function get(string $id): Checkpoint;

  /**
   * Gets a checkpoint's parents.
   *
   * @param string $id
   *   The checkpoint ID.
   *
   * @return iterable<string, \Drupal\Core\Config\Checkpoint\Checkpoint>
   *   The parents for the given checkpoint.
   */
  public function getParents(string $id): iterable;

  /**
   * Adds a new checkpoint.
   *
   * @param string $id
   *   The ID of the checkpoint add.
   * @param string|\Stringable $label
   *   The checkpoint label.
   *
   * @return \Drupal\Core\Config\Checkpoint\Checkpoint
   *   The new checkpoint, which is now at the end of the checkpoint sequence.
   *
   * @throws \Drupal\Core\Config\Checkpoint\CheckpointExistsException
   *   Thrown when the ID already exists.
   */
  public function add(string $id, string|\Stringable $label): Checkpoint;

  /**
   * Deletes a checkpoint.
   *
   * @param string $id
   *   The ID of the checkpoint to delete up to: only checkpoints after this one
   *   will remain.
   *
   * @return $this
   *
   * @throws \Drupal\Core\Config\Checkpoint\UnknownCheckpointException
   *   Thrown when provided checkpoint ID does not exist.
   */
  public function delete(string $id): static;

  /**
   * Deletes all checkpoints.
   *
   * @return $this
   */
  public function deleteAll(): static;

}
