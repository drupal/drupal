<?php

/**
 * @file
 * Definition of Drupal\field_test\TestEntityController.
 */

namespace Drupal\field_test;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for the test entity entity types.
 */
class TestEntityController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   */
  public function preSave(EntityInterface $entity) {
    // Prepare for a new revision.
    if (!$entity->isNew() && !empty($entity->revision)) {
      $entity->old_ftvid = $entity->ftvid;
      $entity->ftvid = NULL;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  public function postSave(EntityInterface $entity, $update) {
    // Only the test_entity entity type has revisions.
    if ($entity->entityType() == 'test_entity') {
      $update_entity = TRUE;
      if (!$update || !empty($entity->revision)) {
        drupal_write_record('test_entity_revision', $entity);
      }
      else {
        drupal_write_record('test_entity_revision', $entity, 'ftvid');
        $update_entity = FALSE;
      }
      if ($update_entity) {
        db_update('test_entity')
          ->fields(array('ftvid' => $entity->ftvid))
          ->condition('ftid', $entity->ftid)
          ->execute();
      }
    }
  }

}
