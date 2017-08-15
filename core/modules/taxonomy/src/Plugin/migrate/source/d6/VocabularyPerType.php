<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

/**
 * Gets all the vocabularies based on the node types that have Taxonomy enabled.
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_vocabulary_per_type",
 *   source_module = "taxonomy"
 * )
 */
class VocabularyPerType extends Vocabulary {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->join('vocabulary_node_types', 'nt', 'v.vid = nt.vid');
    $query->fields('nt', ['type']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'nt';
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
