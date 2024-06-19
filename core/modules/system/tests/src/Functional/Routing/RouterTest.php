<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Language\LanguageInterface;
use Drupal\router_test\TestControllers;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Core\Url;

/**
 * Functional class for the full integrated routing system.
 *
 * @group Routing
 * @group #slow
 */
class RouterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['router_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Confirms that our FinishResponseSubscriber logic works properly.
   */
  public function testFinishResponseSubscriber(): void {
    $renderer_required_cache_contexts = ['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'];
    $expected_cache_contexts = Cache::mergeContexts($renderer_required_cache_contexts, ['url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user.roles:authenticated']);
    sort($expected_cache_contexts);

    // Confirm that the router can get to a controller.
    $this->drupalGet('router_test/test1');
    $this->assertSession()->pageTextContains(TestControllers::LONG_TEXT);
    $session = $this->getSession();

    // Check expected headers from FinishResponseSubscriber.
    $this->assertSession()->responseHeaderEquals('Content-language', 'en');
    $this->assertSession()->responseHeaderEquals('X-Content-Type-Options', 'nosniff');
    $this->assertSession()->responseHeaderEquals('X-Frame-Options', 'SAMEORIGIN');
    if (strcasecmp($session->getResponseHeader('vary'), 'accept-encoding') !== 0) {
      $this->assertSession()->responseHeaderDoesNotExist('Vary');
    }

    $this->drupalGet('router_test/test2');
    $this->assertSession()->pageTextContains('test2');
    // Check expected headers from FinishResponseSubscriber.
    $headers = $session->getResponseHeaders();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Contexts', implode(' ', $expected_cache_contexts));
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'config:user.role.anonymous http_response rendered');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Max-Age', '-1 (Permanent)');
    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertSession()->responseContains('</html>');
    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertSession()->responseNotMatches('#</body>.*</body>#s');

    // Confirm that route-level access check's cacheability is applied to the
    // X-Drupal-Cache-Contexts and X-Drupal-Cache-Tags headers.
    // 1. controller result: render array, globally cacheable route access.
    $this->drupalGet('router_test/test18');
    $expected_cache_contexts = Cache::mergeContexts($renderer_required_cache_contexts, ['url', 'user.roles:authenticated']);
    sort($expected_cache_contexts);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Contexts', implode(' ', $expected_cache_contexts));
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'config:user.role.anonymous foo http_response rendered');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Max-Age', '60');
    // 2. controller result: render array, per-role cacheable route access.
    $this->drupalGet('router_test/test19');
    $expected_cache_contexts = Cache::mergeContexts($renderer_required_cache_contexts, [
      'url',
      'user.roles',
    ]);
    sort($expected_cache_contexts);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Contexts', implode(' ', $expected_cache_contexts));
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'config:user.role.anonymous foo http_response rendered');
    // 3. controller result: Response object, globally cacheable route access.
    $this->drupalGet('router_test/test1');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Contexts');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Max-Age');
    // 4. controller result: Response object, per-role cacheable route access.
    $this->drupalGet('router_test/test20');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Contexts');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Max-Age');
    // 5. controller result: CacheableResponse object, globally cacheable route access.
    $this->drupalGet('router_test/test21');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Contexts', '');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'http_response');
    // 6. controller result: CacheableResponse object, per-role cacheable route access.
    $this->drupalGet('router_test/test22');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Contexts', 'user.roles');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Tags', 'http_response');

    // Finally, verify that the X-Drupal-Cache-Contexts and X-Drupal-Cache-Tags
    // headers are not sent when their container parameter is set to FALSE.
    $this->drupalGet('router_test/test18');
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Contexts');
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderExists('X-Drupal-Cache-Max-Age');
    $this->setContainerParameter('http.response.debug_cacheability_headers', FALSE);
    $this->rebuildContainer();
    $this->resetAll();
    $this->drupalGet('router_test/test18');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Contexts');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Tags');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache-Max-Age');
  }

  /**
   * Confirms that multiple routes with the same path do not cause an error.
   */
  public function testDuplicateRoutePaths(): void {
    // Tests two routes with exactly the same path. The route with the maximum
    // fit and lowest sorting route name will match, regardless of the order the
    // routes are declared.
    // @see \Drupal\Core\Routing\RouteProvider::getRoutesByPath()
    $this->drupalGet('router-test/duplicate-path2');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('router_test.two_duplicate1');

    // Tests three routes with same the path. One of the routes the path has a
    // different case.
    $this->drupalGet('router-test/case-sensitive-duplicate-path3');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('router_test.case_sensitive_duplicate1');
    // While case-insensitive matching works, exact matches are preferred.
    $this->drupalGet('router-test/case-sensitive-Duplicate-PATH3');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('router_test.case_sensitive_duplicate2');
    // Test that case-insensitive matching works, falling back to the first
    // route defined.
    $this->drupalGet('router-test/case-sensitive-Duplicate-Path3');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('router_test.case_sensitive_duplicate1');
  }

  /**
   * Confirms that placeholders in paths work correctly.
   */
  public function testControllerPlaceholders(): void {
    // Test with 0 and a random value.
    $values = ["0", $this->randomMachineName()];
    foreach ($values as $value) {
      $this->drupalGet('router_test/test3/' . $value);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains($value);
    }

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertSession()->responseContains('</html>');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertSession()->responseNotMatches('#</body>.*</body>#s');
  }

  /**
   * Confirms that default placeholders in paths work correctly.
   */
  public function testControllerPlaceholdersDefaultValues(): void {
    $this->drupalGet('router_test/test4');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Lassie');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertSession()->responseContains('</html>');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertSession()->responseNotMatches('#</body>.*</body>#s');
  }

  /**
   * Confirms that default placeholders in paths work correctly.
   */
  public function testControllerPlaceholdersDefaultValuesProvided(): void {
    $this->drupalGet('router_test/test4/barf');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('barf');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertSession()->responseContains('</html>');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style.  This test verifies that is not happening.
    $this->assertSession()->responseNotMatches('#</body>.*</body>#s');
  }

  /**
   * Checks that dynamically defined and altered routes work correctly.
   *
   * @see \Drupal\router_test\RouteSubscriber
   */
  public function testDynamicRoutes(): void {
    // Test the altered route.
    $this->drupalGet('router_test/test6');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('test5');
  }

  /**
   * Checks that a request with text/html response gets rendered as a page.
   */
  public function testControllerResolutionPage(): void {
    $this->drupalGet('/router_test/test10');

    $this->assertSession()->pageTextContains('abcde');

    // Confirm that the page wrapping is being added, so we're not getting a
    // raw body returned.
    $this->assertSession()->responseContains('</html>');

    // In some instances, the subrequest handling may get confused and render
    // a page inception style. This test verifies that is not happening.
    $this->assertSession()->responseNotMatches('#</body>.*</body>#s');
  }

  /**
   * Checks the generate method on the URL generator using the front router.
   */
  public function testUrlGeneratorFront(): void {
    $front_url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    // Compare to the site base URL.
    $base_url = Url::fromUri('base:/', ['absolute' => TRUE]);
    $this->assertSame($base_url->toString(), $front_url->toString());
  }

  /**
   * Tests that a page trying to match a path will succeed.
   */
  public function testRouterMatching(): void {
    $this->drupalGet('router_test/test14/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('User route "entity.user.canonical" was matched.');

    // Try to match a route for a non-existent user.
    $this->drupalGet('router_test/test14/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Route not matched.');

    // Check that very long paths don't cause an error.
    $path = 'router_test/test1';
    $suffix = '/d/r/u/p/a/l';
    for ($i = 0; $i < 10; $i++) {
      $path .= $suffix;
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(404);
    }
  }

  /**
   * Tests that a PSR-7 response works.
   */
  public function testRouterResponsePsr7(): void {
    $this->drupalGet('/router_test/test23');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('test23');
  }

  /**
   * Tests the user account on the DIC.
   */
  public function testUserAccount(): void {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $second_account = $this->drupalCreateUser();

    $this->drupalGet('router_test/test12/' . $second_account->id());
    $this->assertSession()->pageTextContains($account->getAccountName() . ':' . $second_account->getAccountName());
    $this->assertEquals($this->loggedInUser->id(), $account->id(), 'Ensure that the user was not changed.');

    $this->drupalGet('router_test/test13/' . $second_account->id());
    $this->assertSession()->pageTextContains($account->getAccountName() . ':' . $second_account->getAccountName());
    $this->assertEquals($this->loggedInUser->id(), $account->id(), 'Ensure that the user was not changed.');
  }

  /**
   * Checks that an ajax request gets rendered as an Ajax response, by mime.
   */
  public function testControllerResolutionAjax(): void {
    // This will fail with a JSON parse error if the request is not routed to
    // The correct controller.
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $this->drupalGet('/router_test/test10', $options, $headers);

    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/json');

    $this->assertSession()->pageTextContains('abcde');
  }

  /**
   * Tests that routes no longer exist for a module that has been uninstalled.
   */
  public function testRouterUninstallInstall(): void {
    \Drupal::service('module_installer')->uninstall(['router_test']);
    try {
      \Drupal::service('router.route_provider')->getRouteByName('router_test.1');
      $this->fail('Route was delete on uninstall.');
    }
    catch (RouteNotFoundException $e) {
      // Expected exception; just continue testing.
    }
    // Install the module again.
    \Drupal::service('module_installer')->install(['router_test']);
    $route = \Drupal::service('router.route_provider')->getRouteByName('router_test.1');
    $this->assertNotNull($route, 'Route exists after module installation');
  }

  /**
   * Ensure that multiple successive slashes are redirected.
   */
  public function testSuccessiveSlashes(): void {
    $request = $this->container->get('request_stack')->getCurrentRequest();

    // Test a simple path with successive leading slashes.
    $url = $request->getUriForPath('//////router_test/test1');
    $this->drupalGet($url);
    $this->assertSession()->addressEquals($request->getUriForPath('/router_test/test1'));

    // Test successive slashes in the middle.
    $url = $request->getUriForPath('/router_test//////test1') . '?qs=test';
    $this->drupalGet($url);
    $this->assertSession()->addressEquals($request->getUriForPath('/router_test/test1') . '?qs=test');

    // Ensure that external URLs in destination query params are not redirected
    // to.
    $url = $request->getUriForPath('/////////////////////////////////////////////////router_test/test1') . '?qs=test&destination=http://www.example.com%5c@drupal8alt.test';
    $this->drupalGet($url);
    $this->assertSession()->addressEquals($request->getUriForPath('/router_test/test1') . '?qs=test');
  }

}
