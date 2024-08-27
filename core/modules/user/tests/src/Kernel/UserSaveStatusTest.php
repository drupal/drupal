<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests user saving status.
 *
 * @group user
 */
class UserSaveStatusTest extends KernelTestBase {

  /**
   * {@inheritdoc}
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
   * Tests SAVED_NEW and SAVED_UPDATED statuses for user entity type.
   */
  public function testUserSaveStatus(): void {
    // Create a new user.
    $values = [
      'uid' => 1,
      'name' => $this->randomMachineName(),
    ];
    $user = User::create($values);

    // Test SAVED_NEW.
    $return = $user->save();
    $this->assertEquals(SAVED_NEW, $return, "User was saved with SAVED_NEW status.");

    // Test SAVED_UPDATED.
    $user->name = $this->randomMachineName();
    $return = $user->save();
    $this->assertEquals(SAVED_UPDATED, $return, "User was saved with SAVED_UPDATED status.");
  }

}
