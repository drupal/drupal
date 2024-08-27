<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Routing;

use Drupal\Tests\BrowserTestBase;

/**
 * Function Tests for the routing permission system.
 *
 * @group Routing
 */
class RouterPermissionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['router_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $this->drupalLogin($user);
    $this->drupalGet('router_test/test7');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->pageTextContains('test7text');
  }

}
