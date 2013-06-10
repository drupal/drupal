<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityUnitTestBase.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Defines an abstract test base for entity unit tests.
 */
abstract class EntityUnitTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'user', 'system', 'field', 'text', 'field_sql_storage', 'entity_test');

  public function setUp() {
    parent::setUp();
    $this->installSchema('user', 'users');
    $this->installSchema('system', 'sequences');
    $this->installSchema('entity_test', 'entity_test');
    $this->installConfig(array('field'));
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user. The
   *   role_permission and users_roles tables must be installed before this can
   *   be used.
   *
   * @return \Drupal\user\Plugin\Core\Entity\User
   *   The created user entity.
   */
  protected function createUser($values = array(), $permissions = array()) {
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = entity_create('user_role', array(
        'id' => strtolower($this->randomName(8)),
        'label' => $this->randomName(8),
      ));
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = entity_create('user', $values + array(
      'name' => $this->randomName(),
      'status' => 1,
    ));
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

}
