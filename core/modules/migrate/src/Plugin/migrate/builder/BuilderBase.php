<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\builder\BuilderBase.
 */

namespace Drupal\migrate\Plugin\migrate\builder;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\MigrateBuilderInterface;

/**
 * Base class for builder plugins.
 */
abstract class BuilderBase extends PluginBase implements MigrateBuilderInterface {

  /**
   * Returns a fully initialized instance of a source plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $configuration
   *   (optional) Additional configuration for the plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|\Drupal\migrate\Plugin\RequirementsInterface
   *   The fully initialized source plugin.
   */
  protected function getSourcePlugin($plugin_id, array $configuration = []) {
    $configuration['plugin'] = $plugin_id;
    // By default, SqlBase subclasses will try to join on a map table. But in
    // this case we're trying to use the source plugin as a detached iterator
    // over the source data, so we don't want to join on (or create) the map
    // table.
    // @see SqlBase::initializeIterator()
    $configuration['ignore_map'] = TRUE;
    // Source plugins are tightly coupled to migration entities, so we need
    // to create a fake migration in order to properly initialize the plugin.
    $values = [
      'id' => uniqid(),
      'source' => $configuration,
      // Since this isn't a real migration, we don't want a real destination --
      // the 'null' destination is perfect for this.
      'destination' => [
        'plugin' => 'null',
      ],
    ];
    return Migration::create($values)->getSourcePlugin();
  }

}
