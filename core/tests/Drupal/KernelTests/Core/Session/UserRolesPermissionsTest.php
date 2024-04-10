<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Session;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test case for getting permissions from user roles.
 *
 * @group Session
 */
class UserRolesPermissionsTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests that assigning a role grants that role's permissions.
   */
  public function testPermissionChange(): void {
    // Create two accounts to avoid dealing with user 1.
    $this->createUser();
    $account = $this->createUser();

    $this->assertFalse($account->hasPermission('administer modules'));
    $account->addRole($this->createRole(['administer modules']))->save();
    $this->assertTrue($account->hasPermission('administer modules'));
  }

}
