<?php

/**
 * @file
 * Entity classes and controllers for Taxonomy module.
 */

use Drupal\entity\Entity;
use Drupal\entity\EntityInterface;
use Drupal\entity\EntityDatabaseStorageController;

/**
 * Defines the taxonomy term entity.
 */
class TaxonomyTerm extends Entity {

  /**
   * The taxonomy term ID.
   *
   * @var integer
   */
  public $tid;

  /**
   * The taxonomy vocabulary ID this term belongs to.
   *
   * @var integer
   */
  public $vid;

  /**
   * Name of the term.
   *
   * @var string
   */
  public $name;

  /**
   * (optional) Description of the term.
   *
   * @var string
   */
  public $description;

  /**
   * (optional) The text format name for the term's description.
   *
   * @var string
   */
  public $format;

  /**
   * (optional) The weight of this term in relation to other terms of the same
   * vocabulary.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * (optional) The parent term(s) for this term.
   *
   * This property is not loaded, but may be used to modify the term parents via
   * TaxonomyTerm::save().
   *
   * The property can be set to an array of term IDs. An entry of 0 means this
   * term does not have any parents. When omitting this variable during an
   * update, the existing hierarchy for the term remains unchanged.
   *
   * @var array
   */
  public $parent;

  /**
   * (optional) The machine name of the vocabulary the term is assigned to. If
   * not given, this value will be set automatically by loading the vocabulary
   * based on the $entity->vid property.
   *
   * @var string
   */
  public $vocabulary_machine_name;

  /**
   * Implements EntityInterface::id().
   */
  public function id() {
    return $this->tid;
  }

  /**
   * Implements EntityInterface::bundle().
   */
  public function bundle() {
    return $this->vocabulary_machine_name;
  }
}

/**
 * Controller class for taxonomy terms.
 */
class TaxonomyTermController extends EntityDatabaseStorageController {

  /**
   * Overrides EntityDatabaseStorageController::create().
   *
   * @param array $values
   *   An array of values to set, keyed by property name. A value for the
   *   vocabulary ID ('vid') is required.
   */
  public function create(array $values) {
    $entity = parent::create($values);
    // Ensure the vocabulary machine name is initialized as it is used as bundle
    // key.
    // @todo Move to TaxonomyTerm::bundle() once field API has been converted
    // to make use of it.
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
   * Overrides EntityDatabaseStorageController::buildQuery().
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
   * Overrides EntityDatabaseStorageController::cacheGet().
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
   * Overrides EntityDatabaseStorageController::postDelete().
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
   * Overrides EntityDatabaseStorageController::postSave().
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

/**
 * Defines the taxonomy vocabulary entity.
 */
class TaxonomyVocabulary extends Entity {

  /**
   * The taxonomy vocabulary ID.
   *
   * @var integer
   */
  public $vid;

  /**
   * Name of the vocabulary.
   *
   * @var string
   */
  public $name;

  /**
   * The vocabulary machine name.
   *
   * @var string
   */
  public $machine_name;

  /**
   * (optional) Description of the vocabulary.
   *
   * @var string
   */
  public $description;

  /**
   * The type of hierarchy allowed within the vocabulary.
   *
   * Possible values:
   * - TAXONOMY_HIERARCHY_DISABLED: No parents.
   * - TAXONOMY_HIERARCHY_SINGLE: Single parent.
   * - TAXONOMY_HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @var integer
   */
  public $hierarchy = TAXONOMY_HIERARCHY_DISABLED;

  /**
   * (optional) The weight of this vocabulary in relation to other vocabularies.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * Implements EntityInterface::id().
   */
  public function id() {
    return $this->vid;
  }
}

/**
 * Controller class for taxonomy vocabularies.
 */
class TaxonomyVocabularyController extends EntityDatabaseStorageController {

  /**
   * Overrides EntityDatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $conditions, $revision_id);
    $query->addTag('translatable');
    $query->orderBy('base.weight');
    $query->orderBy('base.name');
    return $query;
  }

  /**
   * Overrides EntityDatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    if (!$update) {
      field_attach_create_bundle('taxonomy_term', $entity->machine_name);
    }
    elseif ($entity->original->machine_name != $entity->machine_name) {
      field_attach_rename_bundle('taxonomy_term', $entity->original->machine_name, $entity->machine_name);
    }
  }

  /**
   * Overrides EntityDatabaseStorageController::preDelete().
   */
  protected function preDelete($entities) {
    // Only load terms without a parent, child terms will get deleted too.
    $tids = db_query('SELECT t.tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} th ON th.tid = t.tid WHERE t.vid IN (:vids) AND th.parent = 0', array(':vids' => array_keys($entities)))->fetchCol();
    taxonomy_term_delete_multiple($tids);
  }

  /**
   * Overrides EntityDatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    // Load all Taxonomy module fields and delete those which use only this
    // vocabulary.
    $taxonomy_fields = field_read_fields(array('module' => 'taxonomy'));
    foreach ($taxonomy_fields as $field_name => $taxonomy_field) {
      $modified_field = FALSE;
      // Term reference fields may reference terms from more than one
      // vocabulary.
      foreach ($taxonomy_field['settings']['allowed_values'] as $key => $allowed_value) {
        foreach ($entities as $vocabulary) {
          if ($allowed_value['vocabulary'] == $vocabulary->machine_name) {
            unset($taxonomy_field['settings']['allowed_values'][$key]);
            $modified_field = TRUE;
          }
        }
      }
      if ($modified_field) {
        if (empty($taxonomy_field['settings']['allowed_values'])) {
          field_delete_field($field_name);
        }
        else {
          // Update the field definition with the new allowed values.
          field_update_field($taxonomy_field);
        }
      }
    }
  }

  /**
   * Implements Drupal\entity\EntityControllerInterface::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
    cache_clear_all();
  }
}
