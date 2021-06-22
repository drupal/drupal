<?php

namespace Drupal\system\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6/7 system data for a legacy extension source from database.
 *
 * Available configuration keys:
 * - name: (optional) The extension name to filter items retrieved from the
 *   source - can be a string or an array. If omitted, all extensions are
 *   retrieved.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: extension
 *   name: node
 * @endcode
 *
 * In this example the system data for node module is retrieved from the source
 * database.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "extension",
 *   source_module = "system"
 * )
 */
class Extension extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('system', 's')
      ->fields('s');

    if (isset($this->configuration['name'])) {
      $query->condition('name', (array) $this->configuration['name'], 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'filename' => $this->t('Filename'),
      'name' => $this->t('Name'),
      'type' => $this->t('Type'),
      'owner' => $this->t('Owner'),
      'status' => $this->t('Status'),
      'throttle' => $this->t('Throttle'),
      'bootstrap' => $this->t('Bootstrap'),
      'schema_version' => $this->t('Schema version'),
      'weight' => $this->t('Weight'),
      'info' => $this->t('Information array'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('info', unserialize($row->getSourceProperty('info')));
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
