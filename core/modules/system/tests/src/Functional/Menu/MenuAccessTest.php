<?php

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the route access checks on menu links.
 *
 * @group Menu
 */
class MenuAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'menu_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests menu link for route with access check.
   *
   * @see \Drupal\menu_test\Access\AccessCheck::access()
   */
  public function testMenuBlockLinksAccessCheck() {
    $this->drupalPlaceBlock('system_menu_block:account');
    // Test that there's link rendered on the route.
    $this->drupalGet('menu_test_access_check_session');
    $this->assertLink('Test custom route access check');
    // Page is still accessible but there should be no menu link.
    $this->drupalGet('menu_test_access_check_session');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoLink('Test custom route access check');
    // Test that page is no more accessible.
    $this->drupalGet('menu_test_access_check_session');
    $this->assertSession()->statusCodeEquals(403);

    // Check for access to a restricted local task from a default local task.
    $this->drupalGet('foo/asdf');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertLinkByHref('foo/asdf');
    $this->assertLinkByHref('foo/asdf/b');
    $this->assertNoLinkByHref('foo/asdf/c');

    // Attempt to access a restricted local task.
    $this->drupalGet('foo/asdf/c');
    $this->assertSession()->statusCodeEquals(403);
    $elements = $this->xpath('//ul[@class=:class]/li/a[@href=:href]', [
      ':class' => 'tabs primary',
      ':href' => Url::fromRoute('menu_test.router_test1', ['bar' => 'asdf'])->toString(),
    ]);
    $this->assertTrue(empty($elements), 'No tab linking to foo/asdf found');
    $this->assertNoLinkByHref('foo/asdf/b');
    $this->assertNoLinkByHref('foo/asdf/c');
  }

}
