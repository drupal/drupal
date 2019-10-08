<?php

namespace Drupal\migrate;

/**
 * Provides an interface for the migrate stub service.
 */
interface MigrateStubInterface {

  /**
   * Creates a stub.
   *
   * @param string $migration_id
   *   The migration to stub.
   * @param array $source_ids
   *   An array of source ids.
   * @param array $default_values
   *   (optional) An array of default values to add to the stub.
   *
   * @return array|false
   *   An array of destination ids for the new stub, keyed by destination id
   *   key, or false if the stub failed.
   */
  public function createStub($migration_id, array $source_ids, array $default_values = []);

}
