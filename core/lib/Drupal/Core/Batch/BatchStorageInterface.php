<?php

/**
 * @file
 * Definition of \Drupal\Core\Batch\BatchStorageInterface.
 */

namespace Drupal\Core\Batch;

/**
 * Defines a common interface for batch storage operations.
 */
interface BatchStorageInterface {

  /**
   * Loads a batch.
   *
   * @param int $id
   *   The ID of the batch to load.
   *
   * @return array
   *   An array representing the batch, or FALSE if no batch was found.
   */
  public function load($id);

  /**
   * Creates and saves a batch.
   *
   * @param array $batch
   *   The array representing the batch to create.
   */
  public function create(array $batch);

  /**
   * Updates a batch.
   *
   * @param array $batch
   *   The array representing the batch to update.
   */
  public function update(array $batch);

  /**
   * Deletes a batch.
   *
   * @param int $id
   *   The ID of the batch to delete.
   */
  public function delete($id);

  /**
   * Cleans up failed or old batches.
   */
  public function cleanup();

}
