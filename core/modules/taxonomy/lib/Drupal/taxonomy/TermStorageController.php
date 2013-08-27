<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorageController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorageController extends DatabaseStorageControllerNG implements TermStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values) {
    // Save new terms with no parents by default.
    if (empty($values['parent'])) {
      $values['parent'] = array(0);
    }
    $entity = parent::create($values);
    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildPropertyQuery().
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    if (isset($values['name'])) {
      $entity_query->condition('name', $values['name'], 'LIKE');
      unset($values['name']);
    }
    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::resetCache().
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

}
