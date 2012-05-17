<?php

/**
 * @file
 * Definition of TermStorageController.
 */

namespace Drupal\taxonomy;

use Drupal\entity\EntityInterface;
use Drupal\entity\EntityDatabaseStorageController;

/**
 * Defines a Controller class for taxonomy terms.
 */
class TermStorageController extends EntityDatabaseStorageController {

  /**
   * Overrides Drupal\entity\EntityDatabaseStorageController::create().
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values) {
    $entity = parent::create($values);
    // Ensure the vocabulary machine name is initialized as it is used as the
    // bundle key.
    // @todo Move to Term::bundle() once field API has been converted
    //   to make use of it.
    if (!isset($entity->vocabulary_machine_name)) {
      $vocabulary = taxonomy_vocabulary_load($entity->vid);
      $entity->vocabulary_machine_name = $vocabulary->machine_name;
    }
    // Save new terms with no parents by default.
    if (!isset($entity->parent)) {
      $entity->parent = array(0);
    }
    return $entity;
  }

  /**
   * Overrides Drupal\entity\EntityDatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $conditions, $revision_id);
    $query->addTag('translatable');
    $query->addTag('term_access');
    // When name is passed as a condition use LIKE.
    if (isset($conditions['name'])) {
      $query_conditions = &$query->conditions();
      foreach ($query_conditions as $key => $condition) {
        if ($condition['field'] == 'base.name') {
          $query_conditions[$key]['operator'] = 'LIKE';
          $query_conditions[$key]['value'] = db_like($query_conditions[$key]['value']);
        }
      }
    }
    // Add the machine name field from the {taxonomy_vocabulary} table.
    $query->innerJoin('taxonomy_vocabulary', 'v', 'base.vid = v.vid');
    $query->addField('v', 'machine_name', 'vocabulary_machine_name');
    return $query;
  }

  /**
   * Overrides Drupal\entity\EntityDatabaseStorageController::cacheGet().
   */
  protected function cacheGet($ids, $conditions = array()) {
    $terms = parent::cacheGet($ids, $conditions);
    // Name matching is case insensitive, note that with some collations
    // LOWER() and drupal_strtolower() may return different results.
    foreach ($terms as $term) {
      if (isset($conditions['name']) && drupal_strtolower($conditions['name'] != drupal_strtolower($term->name))) {
        unset($terms[$term->tid]);
      }
    }
    return $terms;
  }

  /**
   * Overrides Drupal\entity\EntityDatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    // See if any of the term's children are about to be become orphans.
    $orphans = array();
    foreach (array_keys($entities) as $tid) {
      if ($children = taxonomy_term_load_children($tid)) {
        foreach ($children as $child) {
          // If the term has multiple parents, we don't delete it.
          $parents = taxonomy_term_load_parents($child->tid);
          // Because the parent has already been deleted, the parent count might
          // be 0.
          if (count($parents) <= 1) {
            $orphans[] = $child->tid;
          }
        }
      }
    }

    // Delete term hierarchy information after looking up orphans but before
    // deleting them so that their children/parent information is consistent.
    db_delete('taxonomy_term_hierarchy')
      ->condition('tid', array_keys($entities))
      ->execute();

    if (!empty($orphans)) {
      taxonomy_term_delete_multiple($orphans);
    }
  }

  /**
   * Overrides Drupal\entity\EntityDatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    if (isset($entity->parent)) {
      db_delete('taxonomy_term_hierarchy')
        ->condition('tid', $entity->tid)
        ->execute();

      $query = db_insert('taxonomy_term_hierarchy')
        ->fields(array('tid', 'parent'));

      foreach ($entity->parent as $parent) {
        $query->values(array(
          'tid' => $entity->tid,
          'parent' => $parent
        ));
      }
      $query->execute();
    }
  }

  /**
   * Implements Drupal\entity\EntityControllerInterface::resetCache().
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
}
