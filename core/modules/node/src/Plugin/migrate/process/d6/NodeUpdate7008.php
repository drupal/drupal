<?php

namespace Drupal\node\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Split the 'administer nodes' permission from 'access content overview'.
 *
 * @MigrateProcessPlugin(
 *   id = "node_update_7008"
 * )
 */
class NodeUpdate7008 extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Split the 'administer nodes' permission from 'access content overview'.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value === 'administer nodes') {
      return [$value, 'access content overview'];
    }
    return $value;
  }

}
