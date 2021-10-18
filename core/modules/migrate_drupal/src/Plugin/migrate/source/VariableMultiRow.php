<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Drupal 6/7 multiple variables source from database.
 *
 * Unlike the variable source plugin, this one returns one row per
 * variable.
 *
 * Available configuration keys:
 * - variables: (required) The list of variables to retrieve from the source
 *   database. Each variable is retrieved in a separate row.
 *
 * Example:
 *
 * @code
 * plugin: variable_multirow
 * variables:
 *   - date_format_long
 *   - date_format_medium
 *   - date_format_short
 * @endcode
 *
 * In this example the specified variables are retrieved from the source
 * database one row per variable.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
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
