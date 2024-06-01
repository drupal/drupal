<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

interface MigratePluginManagerInterface extends PluginManagerInterface {

  /**
   * Creates a pre-configured instance of a migration plugin.
   *
   * A specific createInstance method is necessary to pass the migration on.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration context in which the plugin will run.
   *
   * @return object
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = [], ?MigrationInterface $migration = NULL);

}
