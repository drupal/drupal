<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Session;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test case for getting permissions from user roles.
 */
#[Group('Session')]
#[RunTestsInSeparateProcesses]
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
