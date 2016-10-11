<?php

namespace Drupal\migrate_drupal\Plugin;

use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

interface MigrateCckFieldPluginManagerInterface extends MigratePluginManagerInterface {

  /**
   * Get the plugin ID from the field type.
   *
   * @param string $field_type
   *   The field type being migrated.
   * @param array $configuration
   *   (optional) An array of configuration relevant to the plugin instance.
   * @param \Drupal\migrate\Plugin\MigrationInterface|null $migration
   *   (optional) The current migration instance.
   *
   * @return string
   *   The ID of the plugin for the field_type if available.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   If the plugin cannot be determined, such as if the field type is invalid.
   */
  public function getPluginIdFromFieldType($field_type, array $configuration = [], MigrationInterface $migration = NULL);

}
