<?php

/**
 * @file
 * Definition of VocabularyStorageController.
 */

namespace Drupal\taxonomy;

use Drupal\entity\EntityInterface;
use Drupal\entity\DatabaseStorageController;

/**
 * Defines a controller class for taxonomy vocabularies.
 */
class VocabularyStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\entity\DatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $conditions, $revision_id);
    $query->addTag('translatable');
    $query->orderBy('base.weight');
    $query->orderBy('base.name');
    return $query;
  }

  /**
   * Overrides Drupal\entity\DatabaseStorageController::postSave().
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
   * Overrides Drupal\entity\DatabaseStorageController::preDelete().
   */
  protected function preDelete($entities) {
    // Only load terms without a parent, child terms will get deleted too.
    $tids = db_query('SELECT t.tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} th ON th.tid = t.tid WHERE t.vid IN (:vids) AND th.parent = 0', array(':vids' => array_keys($entities)))->fetchCol();
    taxonomy_term_delete_multiple($tids);
  }

  /**
   * Overrides Drupal\entity\DatabaseStorageController::postDelete().
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
   * Overrides Drupal\entity\DrupalDatabaseStorageController::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
    cache_clear_all();
  }
}
