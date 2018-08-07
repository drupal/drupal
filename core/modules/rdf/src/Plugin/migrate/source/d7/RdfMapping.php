<?php

namespace Drupal\rdf\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 rdf source from database.
 *
 * @MigrateSource(
 *   id = "d7_rdf_mapping",
 *   source_module = "rdf"
 * )
 */
class RdfMapping extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('rdf_mapping', 'r')->fields('r');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $field_mappings = [];
    foreach (unserialize($row->getSourceProperty('mapping')) as $field => $mapping) {
      if ($field === 'rdftype') {
        $row->setSourceProperty('types', $mapping);
      }
      else {
        $field_mappings[$field] = $mapping;
      }
    }
    $row->setSourceProperty('fieldMappings', $field_mappings);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('The name of the entity type a mapping applies to (node, user, comment, etc.'),
      'bundle' => $this->t('The name of the bundle a mapping applies to.'),
      'mapping' => $this->t('The serialized mapping of the bundle type and fields to RDF terms.'),
      'types' => $this->t('RDF types.'),
      'fieldMappings' => $this->t('RDF field mappings.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'type' => [
        'type' => 'string',
      ],
      'bundle' => [
        'type' => 'string',
      ],
    ];
  }

}
