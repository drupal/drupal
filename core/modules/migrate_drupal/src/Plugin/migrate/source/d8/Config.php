<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d8;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 8+ configuration source from database.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
 * \Drupal\migrate\Plugin\migrate\source\ConfigEntity instead.
 * @see https://www.drupal.org/node/3508578
 *
 * Available configuration keys:
 * - collections: (optional) The collection of configuration storage to retrieve
 *   from the source - can be a string or an array. If omitted, configuration
 *   objects of all available collections are retrieved.
 * - names: (optional) Names of configuration objects to retrieve from the
 *   source - can be a string or an array. If omitted, all available
 *   configuration objects are retrieved.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: d8_config
 *   names:
 *     - node.type.article
 *     - node.type.page
 * @endcode
 *
 * In this example configuration objects of article and page content types are
 * retrieved from the source database.
 *
 * @code
 * source:
 *   plugin: d8_config
 *   collections: language.fr
 *   names:
 *     - node.type.article
 *     - node.type.page
 * @endcode
 *
 * In this example configuration objects are filtered by language.fr collection.
 * As a result, French versions of specified configuration objects are retrieved
 * from the source database.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d8_config",
 *   source_module = "system",
 * )
 */
class Config extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\migrate\Plugin\migrate\source\ContentEntity instead. See https://www.drupal.org/node/3508578', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('config', 'c')
      ->fields('c', ['collection', 'name', 'data']);
    if (!empty($this->configuration['collections'])) {
      $query->condition('collection', (array) $this->configuration['collections'], 'IN');
    }
    if (!empty($this->configuration['names'])) {
      $query->condition('name', (array) $this->configuration['names'], 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('The config object collection.'),
      'name' => $this->t('The config object name.'),
      'data' => $this->t('Serialized configuration object data.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['collection']['type'] = 'string';
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
