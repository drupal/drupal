<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests the user entity class.
 *
 * @group user
 * @see \Drupal\user\Entity\User
 */
class UserEntityTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests some of the methods.
   *
   * @see \Drupal\user\Entity\User::getRoles()
   * @see \Drupal\user\Entity\User::addRole()
   * @see \Drupal\user\Entity\User::removeRole()
   */
  public function testUserMethods() {
    $role_storage = $this->container->get('entity_type.manager')->getStorage('user_role');
    $role_storage->create(['id' => 'test_role_one', 'label' => 'Test role 1'])->save();
    $role_storage->create(['id' => 'test_role_two', 'label' => 'Test role 2'])->save();
    $role_storage->create(['id' => 'test_role_three', 'label' => 'Test role 3'])->save();

    $values = [
      'uid' => 1,
      'roles' => ['test_role_one'],
    ];
    $user = User::create($values);

    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertFalse($user->hasRole('test_role_two'));
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'test_role_one'], $user->getRoles());

    $user->addRole('test_role_one');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertFalse($user->hasRole('test_role_two'));
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'test_role_one'], $user->getRoles());

    $user->addRole('test_role_two');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'test_role_one', 'test_role_two'], $user->getRoles());

    $user->removeRole('test_role_three');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'test_role_one', 'test_role_two'], $user->getRoles());

    $user->removeRole('test_role_one');
    $this->assertFalse($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEquals([RoleInterface::AUTHENTICATED_ID, 'test_role_two'], $user->getRoles());
  }

  /**
   * Tests that all user fields validate properly.
   *
   * @see \Drupal\Core\Field\FieldItemListInterface::generateSampleItems
   * @see \Drupal\Core\Field\FieldItemInterface::generateSampleValue()
   * @see \Drupal\Core\Entity\FieldableEntityInterface::validate()
   */
  public function testUserValidation() {
    $user = User::create([]);
    foreach ($user as $field_name => $field) {
      if (!in_array($field_name, ['uid'])) {
        $user->$field_name->generateSampleItems();
      }
    }
    $violations = $user->validate();
    $this->assertFalse((bool) $violations->count());
  }

  /**
   * Tests that ::existingPassword can be used for chaining.
   */
  public function testChainExistingPasswordMethod() {
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create([
      'name' => $this->randomMachineName(),
    ]);
    $user = $user->setExistingPassword('existing_pass');
    $this->assertInstanceOf(User::class, $user);
  }

}
