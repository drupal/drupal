<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the user reference field functionality.
 *
 * @group user
 */
class UserEntityReferenceTest extends EntityKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * A randomly-generated role for testing purposes.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $role1;

  /**
   * A randomly-generated role for testing purposes.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $role2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->role1 = Role::create(array(
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ));
    $this->role1->save();

    $this->role2 = Role::create(array(
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ));
    $this->role2->save();

    $this->createEntityReferenceField('user', 'user', 'user_reference', 'User reference', 'user');
  }

  /**
   * Tests user selection by roles.
   */
  function testUserSelectionByRole() {
    $field_definition = FieldConfig::loadByName('user', 'user', 'user_reference');
    $handler_settings = $field_definition->getSetting('handler_settings');
    $handler_settings['filter']['role'] = array(
      $this->role1->id() => $this->role1->id(),
      $this->role2->id() => 0,
    );
    $handler_settings['filter']['type'] = 'role';
    $field_definition->setSetting('handler_settings', $handler_settings);
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


    /** @var \Drupal\Core\Entity\EntityAutocompleteMatcher $autocomplete */
    $autocomplete = \Drupal::service('entity.autocomplete_matcher');

    $matches = $autocomplete->getMatches('user', 'default', $field_definition->getSetting('handler_settings'), 'aabb');
    $this->assertEqual(count($matches), 2);
    $users = array();
    foreach ($matches as $match) {
      $users[] = $match['label'];
    }
    $this->assertTrue(in_array($user1->label(), $users));
    $this->assertTrue(in_array($user2->label(), $users));
    $this->assertFalse(in_array($user3->label(), $users));

    $matches = $autocomplete->getMatches('user', 'default', $field_definition->getSetting('handler_settings'), 'aabbbb');
    $this->assertEqual(count($matches), 0, '');
  }
}
