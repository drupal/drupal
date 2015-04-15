<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\RouterTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Functional class for the full integrated routing system.
 *
 * @group Routing
 */
class RouterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test');

  /**
   * Confirms that our FinishResponseSubscriber logic works properly.
   */
  public function testFinishResponseSubscriber() {
    $renderer_required_cache_contexts = ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme'];

    // Confirm that the router can get to a controller.
    $this->drupalGet('router_test/test1');
    $this->assertRaw('test1', 'The correct string was returned because the route was successful.');
    // Check expected headers from FinishResponseSubscriber.
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['x-ua-compatible'], 'IE=edge');
    $this->assertEqual($headers['content-language'], 'en');
    $this->assertEqual($headers['x-content-type-options'], 'nosniff');


    $this->drupalGet('router_test/test2');
    $this->assertRaw('test2', 'The correct string was returned because the route was successful.');
    // Check expected headers from FinishResponseSubscriber.
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['x-drupal-cache-contexts'], implode(' ', $renderer_required_cache_contexts));
    $this->assertEqual($headers['x-drupal-cache-tags'], 'rendered');
    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertRaw('</html>', 'Page markup was found.');
    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertNoPattern('#</body>.*</body>#s', 'There was no double-page effect from a misrendered subrequest.');


    // Confirm that route-level access check's cacheability is applied to the
    // X-Drupal-Cache-Contexts and X-Drupal-Cache-Tags headers.
    // 1. controller result: render array, globally cacheable route access.
    $this->drupalGet('router_test/test18');
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['x-drupal-cache-contexts'], implode(' ', Cache::mergeContexts($renderer_required_cache_contexts, ['url'])));
    $this->assertEqual($headers['x-drupal-cache-tags'], 'foo rendered');
    // 2. controller result: render array, per-role cacheable route access.
    $this->drupalGet('router_test/test19');
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['x-drupal-cache-contexts'], implode(' ', Cache::mergeContexts($renderer_required_cache_contexts, ['url', 'user.roles'])));
    $this->assertEqual($headers['x-drupal-cache-tags'], 'foo rendered');
    // 3. controller result: Response object, globally cacheable route access.
    $this->drupalGet('router_test/test1');
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['x-drupal-cache-contexts'], '');
    $this->assertEqual($headers['x-drupal-cache-tags'], '');
    // 4. controller result: Response object, per-role cacheable route access.
    $this->drupalGet('router_test/test20');
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['x-drupal-cache-contexts'], 'user.roles');
    $this->assertEqual($headers['x-drupal-cache-tags'], '');
  }

  /**
   * Confirms that placeholders in paths work correctly.
   */
  public function testControllerPlaceholders() {
    // Test with 0 and a random value.
    $values = array("0", $this->randomMachineName());
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
    $this->assertText('User route "entity.user.canonical" was matched.');

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
    $this->drupalGetAjax('/router_test/test10');

    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/json', 'Correct mime content type was returned');

    $this->assertRaw('abcde', 'Correct body was found.');
  }

  /**
   * Tests that routes no longer exist for a module that has been uninstalled.
   */
  public function testRouterUninstallInstall() {
    \Drupal::service('module_installer')->uninstall(array('router_test'));
    \Drupal::service('router.builder')->rebuild();
    try {
      \Drupal::service('router.route_provider')->getRouteByName('router_test.1');
      $this->fail('Route was delete on uninstall.');
    }
    catch (RouteNotFoundException $e) {
      $this->pass('Route was delete on uninstall.');
    }
    // Install the module again.
    \Drupal::service('module_installer')->install(array('router_test'));
    \Drupal::service('router.builder')->rebuild();
    $route = \Drupal::service('router.route_provider')->getRouteByName('router_test.1');
    $this->assertNotNull($route, 'Route exists after module installation');
  }
}
