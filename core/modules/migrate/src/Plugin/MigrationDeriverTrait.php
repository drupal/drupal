<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrationDeriverTrait.
 */

namespace Drupal\migrate\Plugin;

/**
 * Provides functionality for migration derivers.
 */
trait MigrationDeriverTrait {

  /**
   * Returns a fully initialized instance of a source plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $configuration
   *   (optional) Additional configuration for the plugin. Defaults to an empty
   *   array.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|\Drupal\migrate\Plugin\RequirementsInterface
   *   The fully initialized source plugin.
   */
  public static function getSourcePlugin($source_plugin_id) {
    $definition = [
      'source' => [
        'ignore_map' => TRUE,
        'plugin' => $source_plugin_id,
      ],
      'destination' => [
        'plugin' => 'null',
      ],
    ];
    return (new Migration([], uniqid(), $definition))->getSourcePlugin();
  }

}
