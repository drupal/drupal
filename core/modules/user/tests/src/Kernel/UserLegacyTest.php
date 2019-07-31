<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests legacy user functionality.
 *
 * @group user
 * @group legacy
 */
class UserLegacyTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * @expectedDeprecation user_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\user\Entity\User::loadMultiple(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation user_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\user\Entity\User::load(). See https://www.drupal.org/node/2266845
   */
  public function testEntityLegacyCode() {
    $this->installSchema('system', ['sequences']);
    $this->assertCount(0, user_load_multiple());
    User::create(['name' => 'foo'])->save();
    $this->assertCount(1, user_load_multiple());
    User::create(['name' => 'bar'])->save();
    $this->assertCount(2, user_load_multiple());

    $this->assertNull(user_load(300));
    $this->assertInstanceOf(UserInterface::class, user_load(1));
  }

  /**
   * @expectedDeprecation user_view() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('user')->view() instead. See https://www.drupal.org/node/3033656
   * @expectedDeprecation user_view_multiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('user')->viewMultiple() instead. See https://www.drupal.org/node/3033656
   */
  public function testUserView() {
    $entity = User::create();
    $this->assertNotEmpty(user_view($entity));
    $entities = [
      User::create(),
      User::create(),
    ];
    $this->assertEquals(4, count(user_view_multiple($entities)));
  }

  /**
   * Tests that user_delete throws a deprecation error.
   *
   * @expectedDeprecation user_delete() is deprecated in drupal:8.8.0. Use the user entity's delete method to delete the user. See https://www.drupal.org/node/3051463
   */
  public function testUserDelete() {
    User::create(['name' => 'foo', 'uid' => 10])->save();
    user_delete(10);
    $this->assert(NULL === User::load(10), "User has been deleted");
  }

  /**
   * Tests that user_delete_multiple throws a deprecation error.
   *
   * @expectedDeprecation user_delete_multiple() is deprecated in drupal:8.8.0. Use the entity storage system to delete the users. See https://www.drupal.org/node/3051463
   */
  public function testUserDeleteMultiple() {
    User::create(['name' => 'foo', 'uid' => 10])->save();
    User::create(['name' => 'bar', 'uid' => 11])->save();
    user_delete_multiple([10, 11]);
    $this->assert(NULL === User::load(10), "User 10 has been deleted");
    $this->assert(NULL === User::load(11), "User 11 has been deleted");
  }

  /**
   * Tests user_format_name().
   *
   * @expectedDeprecation user_format_name() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use $account->label() or $account->getDisplayName() instead. See https://www.drupal.org/node/3050794
   */
  public function testUserFormatName() {
    $user = User::create(['name' => 'foo', 'uid' => 10]);
    $this->assertSame('foo', user_format_name($user));
  }

}
