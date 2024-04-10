<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Session;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test case for getting all permissions as a super user.
 *
 * @covers \Drupal\Core\DependencyInjection\Compiler\SuperUserAccessPolicyPass
 * @group Session
 */
class SuperUserPermissionsTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected bool $usesSuperUserAccessPolicy = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests the super user access policy grants all permissions.
   */
  public function testPermissionChange(): void {
    $account = $this->createUser();
    $this->assertSame('1', $account->id());
    $this->assertTrue($account->hasPermission('administer modules'));
    $this->assertTrue($account->hasPermission('non-existent permission'));

    // Turn off the super user access policy and try again.
    $this->usesSuperUserAccessPolicy = FALSE;
    $this->bootKernel();
    $this->assertFalse($account->hasPermission('administer modules'));
    $this->assertFalse($account->hasPermission('non-existent permission'));
  }

}
