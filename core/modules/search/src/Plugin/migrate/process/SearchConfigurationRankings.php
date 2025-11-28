<?php

namespace Drupal\search\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate configuration rankings.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('search_configuration_rankings')]
class SearchConfigurationRankings extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
