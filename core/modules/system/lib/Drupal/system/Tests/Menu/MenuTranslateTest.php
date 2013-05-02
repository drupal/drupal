<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\MenuTranslateTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a test class which tests the _menu_translate method.
 *
 * @see _menu_translate().
 */
class MenuTranslateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_test');

  public static function getInfo() {
    return array(
      'name' => 'menu_translate',
      'description' => 'Tests the _menu_translate() method.',
      'group' => 'Menu',
    );
  }

  /**
   * Tests _menu_translate().
   */
  public function testMenuTranslate() {
    // Check for access to a restricted local task from a default local task.
    $this->drupalGet('foo/asdf');
    $this->assertResponse(200);
    $this->assertLinkByHref('foo/asdf');
    $this->assertLinkByHref('foo/asdf/b');
    $this->assertNoLinkByHref('foo/asdf/c');

    // Attempt to access a restricted local task.
    $this->drupalGet('foo/asdf/c');
    $this->assertResponse(403);
    $this->assertNoLinkByHref('foo/asdf');
    $this->assertNoLinkByHref('foo/asdf/b');
    $this->assertNoLinkByHref('foo/asdf/c');
  }

}
