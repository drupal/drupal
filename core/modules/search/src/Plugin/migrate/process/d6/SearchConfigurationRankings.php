<?php

namespace Drupal\search\Plugin\migrate\process\d6;

use Drupal\search\Plugin\migrate\process\SearchConfigurationRankings as BaseSearchConfigurationRankings;

/**
 * Generate configuration rankings.
 *
 * @deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.x. Use
 *   \Drupal\search\Plugin\migrate\process\SearchConfigurationRankings instead.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_search_configuration_rankings"
 * )
 *
 * @see https://www.drupal.org/node/3009364
 */
class SearchConfigurationRankings extends BaseSearchConfigurationRankings {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error('SearchConfigurationRankings is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use Drupal\search\Plugin\migrate\process\SearchConfigurationRankings instead. See https://www.drupal.org/node/3009364.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
