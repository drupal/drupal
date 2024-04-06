<?php

namespace Drupal\search\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate configuration rankings.
 */
#[MigrateProcess('search_configuration_rankings')]
class SearchConfigurationRankings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Generate the configuration rankings.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = NULL;
    foreach ($row->getSource() as $name => $rank) {
      if (str_starts_with($name, 'node_rank_') && is_numeric($rank)) {
        $return[substr($name, 10)] = $rank;
      }
    }
    return $return;
  }

}
