<?php

/**
 * @file
 * Definition of Drupal\field_test\TestEntityBundleController.
 */

namespace Drupal\field_test;

use Drupal\entity\EntityController;

/**
 * Controller class for the test_entity_bundle entity type.
 *
 * This extends the Drupal\entity\EntityController class, adding
 * required special handling for bundles (since they are not stored in the
 * database).
 */
class TestEntityBundleController extends EntityController {

  protected function attachLoad(&$entities, $revision_id = FALSE) {
    // Add bundle information.
    foreach ($entities as $key => $entity) {
      $entity->fttype = 'test_entity_bundle';
      $entities[$key] = $entity;
    }
    parent::attachLoad($entities, $revision_id);
  }
}
