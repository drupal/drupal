<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Checkpoint;

use Drupal\Core\Config\StorageInterface;

/**
 * Provides an interface for checkpoint storages.
 *
 * @internal
 *   This API is experimental.
 */
interface CheckpointStorageInterface extends StorageInterface {

  /**
   * Creates a checkpoint, if required, and returns the active checkpoint.
   *
   * If the storage determines that the current active checkpoint would contain
   * the same information, it does not have to create a new checkpoint.
   *
   * @param string|\Stringable $label
   *   The checkpoint label to use if a new checkpoint is created.
   *
   * @return \Drupal\Core\Config\Checkpoint\Checkpoint
   *   The currently active checkpoint.
   */
  public function checkpoint(string|\Stringable $label): Checkpoint;

  /**
   * Sets the checkpoint to read from.
   *
   * Calling read() or readMultiple() will return the configuration data at the
   * time of the checkpoint that was set here. If none is set, then the
   * configuration from the initial checkpoint will be returned.
   *
   * @param string|\Drupal\Core\Config\Checkpoint\Checkpoint $checkpoint_id
   *   The checkpoint ID to read from.
   *
   * @return $this
   *
   * @throws \Drupal\Core\Config\Checkpoint\UnknownCheckpointException
   *   Thrown when the provided checkpoint does not exist.
   */
  public function setCheckpointToReadFrom(string|Checkpoint $checkpoint_id): static;

}
