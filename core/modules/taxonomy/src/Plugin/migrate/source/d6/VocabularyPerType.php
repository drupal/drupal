<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;

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
    $query->join('vocabulary_node_types', 'nt', '[v].[vid] = [nt].[vid]');
    $query->fields('nt', ['type']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the i18n taxonomy translation setting for this vocabulary.
    // 0 - No multilingual options
    // 1 - Localizable terms. Run through the localization system.
    // 2 - Predefined language for a vocabulary and its terms.
    // 3 - Per-language terms, translatable (referencing terms with different
    // languages) but not localizable.
    $i18ntaxonomy_vocab = $this->variableGet('i18ntaxonomy_vocabulary', []);
    $vid = $row->getSourceProperty('vid');
    $i18ntaxonomy_vocabulary = FALSE;
    if (array_key_exists($vid, $i18ntaxonomy_vocab)) {
      $i18ntaxonomy_vocabulary = $i18ntaxonomy_vocab[$vid];
    }
    $row->setSourceProperty('i18ntaxonomy_vocabulary', $i18ntaxonomy_vocabulary);
    return parent::prepareRow($row);
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
