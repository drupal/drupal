<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\RouterTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\Routing\RequestContext;

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
    // Test with 0 and a random value.
    $values = array("0", $this->randomName());
    foreach ($values as $value) {
      $this->drupalGet('router_test/test3/' . $value);
      $this->assertResponse(200);
      $this->assertRaw($value, 'The correct string was returned because the route was successful.');
    }

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
    $this->assertResponse(200);
    $this->assertRaw('narf', 'The correct string was returned because the route was successful.');

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
  public function testControllerPlaceholdersDefaultValuesProvided() {
    $this->drupalGet('router_test/test4/barf');
    $this->assertResponse(200);
    $this->assertRaw('barf', 'The correct string was returned because the route was successful.');

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

  /**
   * Checks that a request with text/html response gets rendered as a page.
   */
  public function testControllerResolutionPage() {
    $this->drupalGet('/router_test/test10');

    $this->assertRaw('abcde', 'Correct body was found.');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertRaw('</html>', 'Page markup was found.');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style. This test verifies that is not happening.
    $this->assertNoPattern('#</body>.*</body>#s', 'There was no double-page effect from a misrendered subrequest.');
  }

  /**
   * Checks the generate method on the url generator using the front router.
   */
  public function testUrlGeneratorFront() {
    // Setup the request context of the URL generator. Note: Just calling the
    // code without a proper request, does not setup the request context
    // automatically.
    $context = new RequestContext();
    $context->fromRequest($this->container->get('request'));
    $this->container->get('url_generator')->setRequest($this->container->get('request'));
    $this->container->get('url_generator')->setContext($context);

    global $base_path;

    $this->assertEqual($this->container->get('url_generator')->generate('<front>'), $base_path);
    $this->assertEqual($this->container->get('url_generator')->generateFromPath('<front>'), $base_path);
  }

  /**
   * Checks that an ajax request gets rendered as an Ajax response, by mime.
   *
   * @todo This test will not work until the Ajax enhancer is corrected. However,
   *   that is dependent on fixes to the Ajax system. Re-enable this test once
   *   http://drupal.org/node/1938980 is fixed.
   */
  /*
  public function testControllerResolutionAjax() {
    // This will fail with a JSON parse error if the request is not routed to
    // The correct controller.
    $this->drupalGetAJAX('/router_test/test10');

    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/json', 'Correct mime content type was returned');

    $this->assertRaw('abcde', 'Correct body was found.');
  }
  */

}
