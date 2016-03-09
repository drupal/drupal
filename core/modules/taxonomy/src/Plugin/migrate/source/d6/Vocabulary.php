<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\migrate\source\d6\Vocabulary.
 */

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Drupal 6 vocabularies source from database.
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_vocabulary",
 *   source_provider = "taxonomy"
 * )
 */
class Vocabulary extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('vocabulary', 'v')
      ->fields('v', array(
        'vid',
        'name',
        'description',
        'help',
        'relations',
        'hierarchy',
        'multiple',
        'required',
        'tags',
        'module',
        'weight',
      ));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'vid' => $this->t('The vocabulary ID.'),
      'name' => $this->t('The name of the vocabulary.'),
      'description' => $this->t('The description of the vocabulary.'),
      'help' => $this->t('Help text to display for the vocabulary.'),
      'relations' => $this->t('Whether or not related terms are enabled within the vocabulary. (0 = disabled, 1 = enabled)'),
      'hierarchy' => $this->t('The type of hierarchy allowed within the vocabulary. (0 = disabled, 1 = single, 2 = multiple)'),
      'multiple' => $this->t('Whether or not multiple terms from this vocabulary may be assigned to a node. (0 = disabled, 1 = enabled)'),
      'required' => $this->t('Whether or not terms are required for nodes using this vocabulary. (0 = disabled, 1 = enabled)'),
      'tags' => $this->t('Whether or not free tagging is enabled for the vocabulary. (0 = disabled, 1 = enabled)'),
      'weight' => $this->t('The weight of the vocabulary in relation to other vocabularies.'),
      'parents' => $this->t("The Drupal term IDs of the term's parents."),
      'node_types' => $this->t('The names of the node types the vocabulary may be used with.'),
    );
  }

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
    $row->setSourceProperty('cardinality', ($row->getSourceProperty('tags') == 1 || $row->getSourceProperty('multiple') == 1) ?  FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : 1);
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
