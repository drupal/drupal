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
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSaveRevision().
   */
  public function preSaveRevision(\stdClass $record, EntityInterface $entity) {
    // Allow for predefined revision ids.
    if (!empty($record->use_provided_revision_id)) {
      $record->ftvid = $record->use_provided_revision_id;
    }
  }

}
