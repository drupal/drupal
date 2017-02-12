<?php

namespace Drupal\search\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Get search_active_modules and rankings for core modules.
 *
 * @MigrateSource(
 *   id = "d7_search_page",
 *   source_provider = "search"
 * )
 */
class SearchPage extends Variable {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return new \ArrayIterator($this->values());
  }

  /**
   * Return the values of the variables specified in the plugin configuration.
   *
   * @return array
   *   An associative array where the keys are the variables specified in the
   *   plugin configuration and the values are the values found in the source.
   *   And includes the search module and search status.
   *   Only those values are returned that are actually in the database.
   */
  protected function values() {
    $search_active_modules = $this->variableGet('search_active_modules', '');
    $values = [];
    foreach (['node', 'user'] as $module) {
      if (isset($search_active_modules[$module])) {
        // Create an ID field so we can record migration in the map table.
        $tmp = [
          'module' => $module,
          'status' => $search_active_modules[$module],
        ];
        // Add the node_rank_* variables (only relevant to the node module).
        if ($module === 'node') {
          $tmp = array_merge($tmp, parent::values());
        }
        $values[] = $tmp;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'module' => $this->t('The search module.'),
      'status' => $this->t('Whether or not this module is enabled for search.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['module']['type'] = 'string';
    $ids['status']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->initializeIterator()->count();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $exists = $this->moduleExists($row->getSourceProperty('module'));
    $row->setSourceProperty('module_exists', $exists);
    return parent::prepareRow($row);
  }

}
