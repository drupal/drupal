<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Multiple variables source from database.
 *
 * Unlike the variable source plugin, this one returns one row per
 * variable.
 *
 * @MigrateSource(
 *   id = "variable_multirow",
 *   source_module = "system",
 * )
 */
class VariableMultiRow extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      // Cast scalars to array so we can consistently use an IN condition.
      ->condition('name', (array) $this->configuration['variables'], 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Name'),
      'value' => $this->t('Value'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($value = $row->getSourceProperty('value')) {
      $row->setSourceProperty('value', unserialize($value));
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
