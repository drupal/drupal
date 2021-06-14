<?php

namespace Drupal\migrate;

/**
 * Provides an interface for the migration lookup service.
 *
 * @package Drupal\migrate
 */
interface MigrateLookupInterface {

  /**
   * Retrieves destination ids from a migration lookup.
   *
   * @param string|string[] $migration_ids
   *   An array of migration plugin IDs to look up, or a single ID as a string.
   * @param array $source_id_values
   *   An array of source id values.
   *
   * @return array
   *   An array of arrays of destination ids, or an empty array if none were
   *   found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown by the migration plugin manager on error, or if the migration(s)
   *   cannot be found.
   * @throws \Drupal\migrate\MigrateException
   *   Thrown when $source_id_values contains unknown keys, or is the wrong
   *   length.
   */
  public function lookup($migration_ids, array $source_id_values);

}
