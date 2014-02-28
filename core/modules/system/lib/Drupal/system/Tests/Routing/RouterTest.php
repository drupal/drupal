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
    global $base_path;

    $this->assertEqual($this->container->get('url_generator')->generate('<front>'), $base_path);
    $this->assertEqual($this->container->get('url_generator')->generateFromPath('<front>'), $base_path);
  }

  /**
   * Tests that a page trying to match a path will succeed.
   */
  public function testRouterMatching() {
    $this->drupalGet('router_test/test14/1');
    $this->assertResponse(200);
    $this->assertText('User route "user.view" was matched.');

    // Try to match a route for a non-existent user.
    $this->drupalGet('router_test/test14/2');
    $this->assertResponse(200);
    $this->assertText('Route not matched.');
  }

  /**
   * Tests the user account on the DIC.
   */
  public function testUserAccount() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $second_account = $this->drupalCreateUser();

    $this->drupalGet('router_test/test12/' . $second_account->id());
    $this->assertText($account->getUsername() . ':' . $second_account->getUsername());
    $this->assertEqual($account->id(), $this->loggedInUser->id(), 'Ensure that the user was not changed.');

    $this->drupalGet('router_test/test13/' . $second_account->id());
    $this->assertText($account->getUsername() . ':' . $second_account->getUsername());
    $this->assertEqual($account->id(), $this->loggedInUser->id(), 'Ensure that the user was not changed.');
  }

  /**
   * Checks that an ajax request gets rendered as an Ajax response, by mime.
   */
  public function testControllerResolutionAjax() {
    // This will fail with a JSON parse error if the request is not routed to
    // The correct controller.
    $this->drupalGetAJAX('/router_test/test10');

    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/json', 'Correct mime content type was returned');

    $this->assertRaw('abcde', 'Correct body was found.');
  }

  /**
   * Tests that routes no longer exist for a module that has been uninstalled.
   */
  public function testRouterUninstall() {
    \Drupal::moduleHandler()->uninstall(array('router_test'));
    $route_count = \Drupal::database()->query('SELECT COUNT(*) FROM {router} WHERE provider = :provider', array(':provider' => 'router_test'))->fetchField();
    $this->assertEqual(0, $route_count, 'All router_test routes have been removed on uninstall.');
  }
}
