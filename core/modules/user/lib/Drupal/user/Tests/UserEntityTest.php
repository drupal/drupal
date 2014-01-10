<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserEntityTest.
 */

namespace Drupal\user\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the user entity class.
 *
 * @see \Drupal\user\Entity\User
 */
class UserEntityTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field', 'entity');

  public static function getInfo() {
    return array(
      'name' => 'User entity tests',
      'description' => 'Tests the user entity class.',
      'group' => 'User'
    );
  }

  /**
   * Tests some of the methods.
   *
   * @see \Drupal\user\Entity\User::getRoles()
   * @see \Drupal\user\Entity\User::addRole()
   * @see \Drupal\user\Entity\User::removeRole()
   */
  public function testUserMethods() {
    $role_storage = $this->container->get('entity.manager')->getStorageController('user_role');
    $role_storage->create(array('id' => 'test_role_one'))->save();
    $role_storage->create(array('id' => 'test_role_two'))->save();
    $role_storage->create(array('id' => 'test_role_three'))->save();

    $values = array('roles' => array(Language::LANGCODE_DEFAULT => array('test_role_one')));
    $user = new User($values, 'user');

    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertFalse($user->hasRole('test_role_two'));
    $this->assertEqual(array('test_role_one'), $user->getRoles());

    $user->addRole('test_role_one');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertFalse($user->hasRole('test_role_two'));
    $this->assertEqual(array('test_role_one'), $user->getRoles());

    $user->addRole('test_role_two');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEqual(array('test_role_one', 'test_role_two'), $user->getRoles());

    $user->removeRole('test_role_three');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEqual(array('test_role_one', 'test_role_two'), $user->getRoles());

    $user->removeRole('test_role_one');
    $this->assertFalse($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEqual(array('test_role_two'), $user->getRoles());
  }

}
