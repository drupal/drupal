<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\Process\d6\NodeUpdate7008.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\Process\d6;

use Drupal\migrate\MigrateExecutable;
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
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    if ($value === 'administer nodes') {
      return array($value, 'access content overview');
    }
    return $value;
  }

}
