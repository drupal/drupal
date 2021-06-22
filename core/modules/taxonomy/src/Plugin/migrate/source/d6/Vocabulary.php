<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Drupal 6 vocabularies source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_vocabulary",
 *   source_module = "taxonomy"
 * )
 */
class Vocabulary extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('vocabulary', 'v')
      ->fields('v', [
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
      ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find node types for this row.
    $node_types = $this->select('vocabulary_node_types', 'nt')
      ->fields('nt', ['type', 'vid'])
      ->condition('vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('node_types', $node_types);
    $row->setSourceProperty('cardinality', ($row->getSourceProperty('tags') == 1 || $row->getSourceProperty('multiple') == 1) ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : 1);

    // If the vocabulary being migrated is the one defined in the
    // 'forum_nav_vocabulary' variable, set the 'forum_vocabulary' source
    // property to true so we know this is the vocabulary used by Forum.
    if ($this->variableGet('forum_nav_vocabulary', 0) == $row->getSourceProperty('vid')) {
      $row->setSourceProperty('forum_vocabulary', TRUE);
    }

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
