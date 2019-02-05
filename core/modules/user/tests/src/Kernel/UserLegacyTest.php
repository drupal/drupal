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

}
