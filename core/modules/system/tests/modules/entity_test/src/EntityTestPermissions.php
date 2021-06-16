<?php

namespace Drupal\entity_test;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_test\Entity\EntityTestBundle;

/**
 * Provides dynamic permissions for entity test.
 */
class EntityTestPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of entity_test_bundle permissions.
   *
   * @return array
   *   An array of entity_test_bundle permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function entityTestBundlePermissions() {
    $perms = [];
    // Generate permissions for all EntityTestBundle bundles.
    foreach (EntityTestBundle::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of entity test permissions for a given entity test bundle.
   *
   * @param \Drupal\entity_test\Entity\EntityTestBundle $type
   *   The entity test bundle.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EntityTestBundle $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id entity_test_with_bundle entities" => [
        'title' => $this->t('%type_name: Create new entity', $type_params),
      ],
    ];
  }

}
