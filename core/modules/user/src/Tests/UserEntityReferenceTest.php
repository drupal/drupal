<?php
/**
 * @file
 * Contains \Drupal\user\Tests\UserEntityReferenceTest.
 */

namespace Drupal\user\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the user reference field functionality.
 *
 * @group user
 */
class UserEntityReferenceTest extends EntityUnitTestBase {

  /**
   * @var \Drupal\user\Entity\Role
   */
  protected $role1;

  /**
   * @var \Drupal\user\Entity\Role
   */
  protected $role2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->role1 = entity_create('user_role', array(
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ));
    $this->role1->save();

    $this->role2 = entity_create('user_role', array(
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ));
    $this->role2->save();

    entity_reference_create_field('user', 'user', 'user_reference', 'User reference', 'user');
  }

  /**
   * Tests user selection by roles.
   */
  function testUserSelectionByRole() {
    $field_definition = FieldConfig::loadByName('user', 'user', 'user_reference');
    $field_definition->settings['handler_settings']['filter']['role'] = array(
      $this->role1->id() => $this->role1->id(),
      $this->role2->id() => 0,
    );
    $field_definition->settings['handler_settings']['filter']['type'] = 'role';
    $field_definition->save();

    $user1 = $this->createUser(array('name' => 'aabb'));
    $user1->addRole($this->role1->id());
    $user1->save();

    $user2 = $this->createUser(array('name' => 'aabbb'));
    $user2->addRole($this->role1->id());
    $user2->save();

    $user3 = $this->createUser(array('name' => 'aabbbb'));
    $user3->addRole($this->role2->id());
    $user3->save();


    /** @var \Drupal\entity_reference\EntityReferenceAutocomplete $autocomplete */
    $autocomplete = \Drupal::service('entity_reference.autocomplete');

    $matches = $autocomplete->getMatches($field_definition, 'user', 'user', 'NULL', '', 'aabb');
    $this->assertEqual(count($matches), 2);
    $users = array();
    foreach ($matches as $match) {
      $users[] = $match['label'];
    }
    $this->assertTrue(in_array($user1->label(), $users));
    $this->assertTrue(in_array($user2->label(), $users));
    $this->assertFalse(in_array($user3->label(), $users));

    $matches = $autocomplete->getMatches($field_definition, 'user', 'user', 'NULL', '', 'aabbbb');
    $this->assertEqual(count($matches), 0, '');
  }
}
