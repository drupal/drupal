<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the user reference field functionality.
 *
 * @group user
 */
class UserEntityReferenceTest extends EntityKernelTestBase {

  use EntityReferenceFieldCreationTrait;

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
  protected function setUp(): void {
    parent::setUp();

    $this->role1 = Role::create([
      'id' => $this->randomMachineName(8),
      'label' => $this->randomMachineName(8),
    ]);
    $this->role1->save();

    $this->role2 = Role::create([
      'id' => $this->randomMachineName(8),
      'label' => $this->randomMachineName(8),
    ]);
    $this->role2->save();

    $this->createEntityReferenceField('user', 'user', 'user_reference', 'User reference', 'user');
  }

  /**
   * Tests user selection by roles.
   */
  public function testUserSelectionByRole() {
    $field_definition = FieldConfig::loadByName('user', 'user', 'user_reference');
    $handler_settings = $field_definition->getSetting('handler_settings');
    $handler_settings['filter']['role'] = [
      $this->role1->id() => $this->role1->id(),
      $this->role2->id() => 0,
    ];
    $handler_settings['filter']['type'] = 'role';
    $field_definition->setSetting('handler_settings', $handler_settings);
    $field_definition->save();

    // cspell:ignore aabb aabbb aabbbb aabbbb
    $user1 = $this->createUser([], 'aabb');
    $user1->addRole($this->role1->id());
    $user1->save();

    $user2 = $this->createUser([], 'aabbb');
    $user2->addRole($this->role1->id());
    $user2->save();

    $user3 = $this->createUser([], 'aabbbb');
    $user3->addRole($this->role2->id());
    $user3->save();

    /** @var \Drupal\Core\Entity\EntityAutocompleteMatcherInterface $autocomplete */
    $autocomplete = \Drupal::service('entity.autocomplete_matcher');

    $matches = $autocomplete->getMatches('user', 'default', $field_definition->getSetting('handler_settings'), 'aabb');
    $this->assertCount(2, $matches);
    $users = [];
    foreach ($matches as $match) {
      $users[] = $match['label'];
    }
    $this->assertContains($user1->label(), $users);
    $this->assertContains($user2->label(), $users);
    $this->assertNotContains($user3->label(), $users);

    $matches = $autocomplete->getMatches('user', 'default', $field_definition->getSetting('handler_settings'), 'aabbbb');
    $this->assertCount(0, $matches);
  }

}
