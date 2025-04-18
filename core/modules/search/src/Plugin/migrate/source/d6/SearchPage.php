<?php

namespace Drupal\search\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Drupal 6 node search rankings for core modules source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate_drupal\Plugin\migrate\source\Variable
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_search_page",
 *   source_module = "search"
 * )
 */
class SearchPage extends Variable {

  /**
   * {@inheritdoc}
   */
  protected function values() {
    // Add a module key to identify the source search provider, node. This value
    // is used in the EntitySearchPage destination plugin.
    return array_merge(['module' => 'node'], parent::values());
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'module' => $this->t('The module providing a search page.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['module']['type'] = 'string';
    return $ids;
  }

}
