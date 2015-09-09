<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\migrate\source\d6\Vocabulary.
 */

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;

/**
 * Drupal 6 vocabularies source from database.
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_vocabulary",
 *   source_provider = "taxonomy"
 * )
 */
class Vocabulary extends VocabularyBase {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find node types for this row.
    $node_types = $this->select('vocabulary_node_types', 'nt')
      ->fields('nt', array('type', 'vid'))
      ->condition('vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('node_types', $node_types);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    return $ids;
  }

}
