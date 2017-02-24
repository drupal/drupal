<?php

namespace Drupal\search\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Get node search rankings for core modules.
 *
 * @MigrateSource(
 *   id = "d6_search_page"
 * )
 */
class SearchPage extends Variable {

  /**
   * Return the values of the variables specified in the plugin configuration.
   *
   * @return array
   *   An associative array where the keys are the variables specified in the
   *   plugin configuration and the values are the values found in the source.
   *   Only those values are returned that are actually in the database.
   */
  protected function values() {
    return array_merge(['module' => 'node'], parent::values());
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'module' => $this->t('The search module.'),
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
