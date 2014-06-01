<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorage.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorage extends ContentEntityDatabaseStorage implements TermStorageInterface {

  /**
   * {@inheritdoc}
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values = array()) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = array(0);
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if (isset($values['name'])) {
      $entity_query->condition('name', $values['name'], 'LIKE');
      unset($values['name']);
    }
    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_term_count_nodes');
    drupal_static_reset('taxonomy_get_tree');
    drupal_static_reset('taxonomy_get_tree:parents');
    drupal_static_reset('taxonomy_get_tree:terms');
    drupal_static_reset('taxonomy_term_load_parents');
    drupal_static_reset('taxonomy_term_load_parents_all');
    drupal_static_reset('taxonomy_term_load_children');
    parent::resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTermHierarchy($tids) {
    $this->database->delete('taxonomy_term_hierarchy')
      ->condition('tid', $tids)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateTermHierarchy(EntityInterface $term) {
    $query = $this->database->insert('taxonomy_term_hierarchy')
      ->fields(array('tid', 'parent'));

    foreach ($term->parent as $parent) {
      $query->values(array(
        'tid' => $term->id(),
        'parent' => (int) $parent->value,
      ));
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($tid) {
    $query = $this->database->select('taxonomy_term_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.parent = t.tid');
    $query->addField('t', 'tid');
    $query->condition('h.tid', $tid);
    $query->addTag('term_access');
    $query->orderBy('t.weight');
    $query->orderBy('t.name');
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($tid, $vid = NULL) {
    $query = $this->database->select('taxonomy_term_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
    $query->addField('t', 'tid');
    $query->condition('h.parent', $tid);
    if ($vid) {
      $query->condition('t.vid', $vid);
    }
    $query->addTag('term_access');
    $query->orderBy('t.weight');
    $query->orderBy('t.name');
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree($vid) {
    $query = $this->database->select('taxonomy_term_data', 't');
    $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
    return $query
      ->addTag('term_access')
      ->fields('t')
      ->fields('h', array('parent'))
      ->condition('t.vid', $vid)
      ->orderBy('t.weight')
      ->orderBy('t.name')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCount($vid) {
    $query = $this->database->select('taxonomy_index', 'ti');
    $query->addExpression('COUNT(DISTINCT ti.nid)');
    $query->leftJoin('taxonomy_term_data', 'td', 'ti.tid = td.tid');
    $query->condition('td.vid', $vid);
    $query->addTag('vocabulary_node_count');
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function resetWeights($vid) {
    $this->database->update('taxonomy_term_data')
      ->fields(array('weight' => 0))
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['taxonomy_term_data']['fields']['weight']['not null'] = TRUE;
    $schema['taxonomy_term_data']['fields']['name']['not null'] = TRUE;

    unset($schema['taxonomy_term_data']['indexes']['field__vid']);
    unset($schema['taxonomy_term_data']['indexes']['field__description__format']);
    $schema['taxonomy_term_data']['indexes'] += array(
      'taxonomy_term__tree' => array('vid', 'weight', 'name'),
      'taxonomy_term__vid_name' => array('vid', 'name'),
      'taxonomy_term__name' => array('name'),
    );

    $schema['taxonomy_term_hierarchy'] = array(
      'description' => 'Stores the hierarchical relationship between terms.',
      'fields' => array(
        'tid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The {taxonomy_term_data}.tid of the term.',
        ),
        'parent' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => "Primary Key: The {taxonomy_term_data}.tid of the term's parent. 0 indicates no parent.",
        ),
      ),
      'indexes' => array(
        'parent' => array('parent'),
      ),
      'foreign keys' => array(
        'taxonomy_term_data' => array(
          'table' => 'taxonomy_term_data',
          'columns' => array('tid' => 'tid'),
        ),
      ),
      'primary key' => array('tid', 'parent'),
    );

    $schema['taxonomy_index'] = array(
      'description' => 'Maintains denormalized information about node/term relationships.',
      'fields' => array(
        'nid' => array(
          'description' => 'The {node}.nid this record tracks.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'tid' => array(
          'description' => 'The term ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'sticky' => array(
          'description' => 'Boolean indicating whether the node is sticky.',
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'created' => array(
          'description' => 'The Unix timestamp when the node was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
        ),
      ),
      'primary key' => array('nid', 'tid'),
      'indexes' => array(
        'term_node' => array('tid', 'sticky', 'created'),
      ),
      'foreign keys' => array(
        'tracked_node' => array(
          'table' => 'node',
          'columns' => array('nid' => 'nid'),
        ),
        'term' => array(
          'table' => 'taxonomy_term_data',
          'columns' => array('tid' => 'tid'),
        ),
      ),
    );

    return $schema;
  }

}
