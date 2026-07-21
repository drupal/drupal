<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Routing;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Function Tests for the routing permission system.
 */
#[Group('Routing')]
#[RunTestsInSeparateProcesses]
class RouterPermissionTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['router_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests permission requirements on routes.
   */
  public function testPermissionAccess(): void {
    // Ensure 403 Access Denied for a route without permission.
    $this->drupalGet('router_test/test7');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure 403 Access Denied by default if no access specified.
    $this->drupalGet('router_test/test8');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser(['access test7']);
    $this->setCurrentUser($user);
    $this->drupalGet('router_test/test7');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->pageTextContains('test7text');
  }

}
