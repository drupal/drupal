<?php

/**
 * @file
 * Definition of Drupal\taxonomy\VocabularyStorageController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a controller class for taxonomy vocabularies.
 */
class VocabularyStorageController extends ConfigStorageController {

  /**
   * Overrides Drupal\Core\Config\Entity\ConfigStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
    if (!$update) {
      entity_invoke_bundle_hook('create', 'taxonomy_term', $entity->id());
    }
    elseif ($entity->getOriginalID() != $entity->id()) {
      // Reflect machine name changes in the definitions of existing 'taxonomy'
      // fields.
      $fields = field_read_fields();
      foreach ($fields as $field_name => $field) {
        $update_field = FALSE;
        if ($field['type'] == 'taxonomy_term_reference') {
          foreach ($field['settings']['allowed_values'] as $key => &$value) {
            if ($value['vocabulary'] == $entity->getOriginalID()) {
              $value['vocabulary'] = $entity->id();
              $update_field = TRUE;
            }
          }
          if ($update_field) {
            field_update_field($field);
          }
        }
      }
      // Update bundles.
      entity_invoke_bundle_hook('rename', 'taxonomy_term', $entity->getOriginalID(), $entity->id());
    }
    parent::postSave($entity, $update);
    $this->resetCache($update ? array($entity->getOriginalID()) : array());
  }

  /**
   * Overrides Drupal\Core\Config\Entity\ConfigStorageController::preDelete().
   */
  protected function preDelete($entities) {
    parent::preDelete($entities);
    // Only load terms without a parent, child terms will get deleted too.
    $tids = db_query('SELECT t.tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} th ON th.tid = t.tid WHERE t.vid IN (:vids) AND th.parent = 0', array(':vids' => array_keys($entities)))->fetchCol();
    taxonomy_term_delete_multiple($tids);
  }

  /**
   * Overrides Drupal\Core\Config\Entity\ConfigStorageController::postDelete().
   */
  protected function postDelete($entities) {
    parent::postDelete($entities);

    $vocabularies = array();
    foreach ($entities as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->id();
    }
    // Load all Taxonomy module fields and delete those which use only this
    // vocabulary.
    $taxonomy_fields = field_read_fields(array('module' => 'taxonomy'));
    foreach ($taxonomy_fields as $field_name => $taxonomy_field) {
      $modified_field = FALSE;
      // Term reference fields may reference terms from more than one
      // vocabulary.
      foreach ($taxonomy_field['settings']['allowed_values'] as $key => $allowed_value) {
        if (isset($vocabularies[$allowed_value['vocabulary']])) {
          unset($taxonomy_field['settings']['allowed_values'][$key]);
          $modified_field = TRUE;
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
    // Reset caches.
    $this->resetCache(array_keys($vocabularies));
  }

  /**
   * Overrides Drupal\Core\Config\Entity\ConfigStorageController::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('taxonomy_vocabulary_get_names');
    parent::resetCache($ids);
    cache_invalidate_tags(array('content' => TRUE));
    entity_info_cache_clear();
  }

}
