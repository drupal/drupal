<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * @group user
 * @coversDefaultClass \Drupal\user\Entity\Role
 */
class UserRoleEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'user_permissions_test'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container
      ->register(BufferingLogger::class)
      ->addTag('logger');
  }

  public function testOrderOfPermissions(): void {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    $role->grantPermission('b')
      ->grantPermission('a')
      ->grantPermission('c')
      ->save();
    $this->assertEquals(['a', 'b', 'c'], $role->getPermissions());

    $role->revokePermission('b')->save();
    $this->assertEquals(['a', 'c'], $role->getPermissions());

    $role->grantPermission('b')->save();
    $this->assertEquals(['a', 'b', 'c'], $role->getPermissions());
  }

  public function testGrantingNonExistentPermission(): void {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);

    // A single permission that does not exist.
    $role->grantPermission('does not exist')
      ->save();
    $log_message = \Drupal::service(BufferingLogger::class)->cleanLogs()[0];
    $this->assertSame(RfcLogLevel::ERROR, $log_message[0]);
    $this->assertSame('Non-existent permission(s) assigned to role "@label" (@id) were removed. Invalid permission(s): @permissions.', $log_message[1]);
    $this->assertSame('Test role', $log_message[2]['@label']);
    $this->assertSame('test_role', $log_message[2]['@id']);
    $this->assertSame('does not exist', $log_message[2]['@permissions']);

    // Multiple permissions that do not exist.
    $role->grantPermission('does not exist')
      ->grantPermission('also does not exist')
      ->save();
    $log_message = \Drupal::service(BufferingLogger::class)->cleanLogs()[0];
    $this->assertSame(RfcLogLevel::ERROR, $log_message[0]);
    $this->assertSame('Non-existent permission(s) assigned to role "@label" (@id) were removed. Invalid permission(s): @permissions.', $log_message[1]);
    $this->assertSame('Test role', $log_message[2]['@label']);
    $this->assertSame('test_role', $log_message[2]['@id']);
    $this->assertSame('does not exist, also does not exist', $log_message[2]['@permissions']);
    $permissions = $role->getPermissions();
    $this->assertEmpty(array_intersect(['does not exist', 'also does not exist'], $permissions));
  }

  public function testPermissionRevokeAndConfigSync(): void {
    $role = Role::create(['id' => 'test_role', 'label' => 'Test role']);
    $role->setSyncing(TRUE);
    $role->grantPermission('a')
      ->grantPermission('b')
      ->grantPermission('c')
      ->save();
    $this->assertSame(['a', 'b', 'c'], $role->getPermissions());
    $role->revokePermission('b')->save();
    $this->assertSame(['a', 'c'], $role->getPermissions());
  }

}
