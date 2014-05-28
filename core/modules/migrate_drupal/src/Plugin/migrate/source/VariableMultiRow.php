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
 *   id = "variable_multirow"
 * )
 */
class VariableMultiRow extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('variable', 'v')
      ->fields('v', array('name', 'value'))
      ->condition('name', $this->configuration['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'name' => $this->t('Name'),
      'value' => $this->t('Value'),
    );
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
