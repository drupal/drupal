<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\Process\d6\SearchPage.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\Process\d6;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;

/**
 * Generate configuration rankings.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_search_configuration_rankings"
 * )
 */
class SearchConfigurationRankings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Generate the configuration rankings.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $return = array();
    foreach ($row->getSource() as $name => $rank) {
      if (substr($name, 0, 10) == 'node_rank_' && $rank) {
        $return[substr($name, 10)] = $rank;
      }
    }
    return $return;
  }

}
