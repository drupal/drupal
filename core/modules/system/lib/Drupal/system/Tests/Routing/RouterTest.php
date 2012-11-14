<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\RouterTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\simpletest\WebTestBase;

/**
 * Functional class for the full integrated routing system.
 */
class RouterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'router_test');

  public static function getInfo() {
    return array(
      'name' => 'Integrated Router tests',
      'description' => 'Function Tests for the fully integrated routing system.',
      'group' => 'Routing',
    );
  }

  /**
   * Confirms that the router can get to a controller.
   */
  public function testCanRoute() {
    $this->drupalGet('router_test/test1');
    $this->assertRaw('test1', 'The correct string was returned because the route was successful.');
  }

  /**
   * Confirms that our default controller logic works properly.
   */
  public function testDefaultController() {
    $this->drupalGet('router_test/test2');
    $this->assertRaw('test2', 'The correct string was returned because the route was successful.');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertRaw('</html>', 'Page markup was found.');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertNoPattern('#</body>.*</body>#s', 'There was no double-page effect from a misrendered subrequest.');
  }

  /**
   * Confirms that placeholders in paths work correctly.
   */
  public function testControllerPlaceholders() {
    $value = $this->randomName();
    $this->drupalGet('router_test/test3/' . $value);
    $this->assertRaw($value, 'The correct string was returned because the route was successful.');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertRaw('</html>', 'Page markup was found.');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertNoPattern('#</body>.*</body>#s', 'There was no double-page effect from a misrendered subrequest.');
  }

  /**
   * Confirms that default placeholders in paths work correctly.
   */
  public function testControllerPlaceholdersDefaultValues() {
    $this->drupalGet('router_test/test4');
    $this->assertRaw('narf', 'The correct string was returned because the route was successful.');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertRaw('</html>', 'Page markup was found.');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertNoPattern('#</body>.*</body>#s', 'There was no double-page effect from a misrendered subrequest.');
  }

  /**
   * Checks that dynamically defined and altered routes work correctly.
   *
   * @see \Drupal\router_test\RouteSubscriber
   */
  public function testDynamicRoutes() {
    // Test the dynamically added route.
    $this->drupalGet('router_test/test5');
    $this->assertResponse(200);
    $this->assertRaw('test5', 'The correct string was returned because the route was successful.');

    // Test the altered route.
    $this->drupalGet('router_test/test6');
    $this->assertResponse(200);
    $this->assertRaw('test5', 'The correct string was returned because the route was successful.');
  }
}
