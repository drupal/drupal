<?php

declare(strict_types=1);

namespace Drupal\Tests\page_cache\Functional;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;

/**
 * Enables the page cache and tests it with various HTTP requests.
 *
 * @group page_cache
 * @group #slow
 */
class PageCacheTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['test_page_test', 'system_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')
      ->set('name', 'Drupal')
      ->set('page.front', '/test-page')
      ->save();
  }

  /**
   * Tests that cache tags are properly persisted.
   *
   * Since tag based invalidation works, we know that our tag properly
   * persisted.
   */
  public function testPageCacheTags(): void {
    $this->enablePageCaching();

    $path = 'system-test/cache_tags_page';
    $tags = ['system_test_cache_tags_page'];
    $this->drupalGet($path);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet($path);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $cid_parts = [Url::fromRoute('system_test.cache_tags_page', [], ['absolute' => TRUE])->toString(), ''];
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('page')->get($cid);
    sort($cache_entry->tags);
    $expected_tags = [
      'config:user.role.anonymous',
      'http_response',
      'pre_render',
      'rendered',
      'system_test_cache_tags_page',
    ];
    $this->assertSame($expected_tags, $cache_entry->tags);

    Cache::invalidateTags($tags);
    $this->drupalGet($path);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
  }

  /**
   * Tests that the page cache doesn't depend on cacheability headers.
   */
  public function testPageCacheTagsIndependentFromCacheabilityHeaders(): void {
    // Disable the cacheability headers.
    $this->setContainerParameter('http.response.debug_cacheability_headers', FALSE);
    $this->rebuildContainer();
    $this->resetAll();

    $path = 'system-test/cache_tags_page';
    $tags = ['system_test_cache_tags_page'];
    $this->drupalGet($path);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet($path);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $cid_parts = [Url::fromRoute('system_test.cache_tags_page', [], ['absolute' => TRUE])->toString(), ''];
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('page')->get($cid);
    sort($cache_entry->tags);
    $expected_tags = [
      'config:user.role.anonymous',
      'http_response',
      'pre_render',
      'rendered',
      'system_test_cache_tags_page',
    ];
    $this->assertSame($expected_tags, $cache_entry->tags);

    Cache::invalidateTags($tags);
    $this->drupalGet($path);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
  }

  /**
   * Tests support for different cache items with different request formats.
   *
   * The request formats are specified via a query parameter.
   */
  public function testQueryParameterFormatRequests(): void {
    $this->enablePageCaching();

    $accept_header_cache_url = Url::fromRoute('system_test.page_cache_accept_header');
    $accept_header_cache_url_with_json = Url::fromRoute('system_test.page_cache_accept_header', ['_format' => 'json']);
    $accept_header_cache_url_with_ajax = Url::fromRoute('system_test.page_cache_accept_header', ['_format' => 'json'], ['query' => ['_wrapper_format' => 'drupal_ajax']]);

    $this->drupalGet($accept_header_cache_url);
    // Verify that HTML page was not yet cached.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->drupalGet($accept_header_cache_url);
    // Verify that HTML page was cached.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    // Verify that the correct HTML response was returned.
    $this->assertSession()->responseContains('<p>oh hai this is html.</p>');

    $this->drupalGet($accept_header_cache_url_with_json);
    // Verify that JSON response was not yet cached.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->drupalGet($accept_header_cache_url_with_json);
    // Verify that JSON response was cached.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    // Verify that the correct JSON response was returned.
    $this->assertSession()->responseContains('{"content":"oh hai this is json"}');

    $this->drupalGet($accept_header_cache_url_with_ajax);
    // Verify that AJAX response was not yet cached.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->drupalGet($accept_header_cache_url_with_ajax);
    // Verify that AJAX response was cached.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    // Verify that the correct AJAX response was returned.
    $this->assertSession()->responseContains('{"content":"oh hai this is ajax"}');
  }

  /**
   * Tests support of requests with If-Modified-Since and If-None-Match headers.
   */
  public function testConditionalRequests(): void {
    $this->enablePageCaching();

    // Fill the cache.
    $this->drupalGet('');
    // Verify the page is not printed twice when the cache is cold.
    $this->assertSession()->responseNotMatches('#<html.*<html#');

    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $etag = $this->getSession()->getResponseHeader('ETag');
    $last_modified = $this->getSession()->getResponseHeader('Last-Modified');

    // Ensure a conditional request returns 304 Not Modified.
    $this->drupalGet('', [], ['If-Modified-Since' => $last_modified, 'If-None-Match' => $etag]);
    $this->assertSession()->statusCodeEquals(304);

    // Ensure a conditional request with obsolete If-Modified-Since date
    // returns 304 Not Modified.
    $this->drupalGet('', [], [
      'If-Modified-Since' => gmdate(DATE_RFC822, strtotime($last_modified)),
      'If-None-Match' => $etag,
    ]);
    $this->assertSession()->statusCodeEquals(304);

    // Ensure a conditional request with obsolete If-Modified-Since date
    // returns 304 Not Modified.
    $this->drupalGet('', [], [
      'If-Modified-Since' => gmdate(DATE_RFC850, strtotime($last_modified)),
      'If-None-Match' => $etag,
    ]);
    $this->assertSession()->statusCodeEquals(304);

    // Ensure a conditional request without If-None-Match returns 200 OK.
    $this->drupalGet('', [], ['If-Modified-Since' => $last_modified, 'If-None-Match' => NULL]);
    // Verify the page is not printed twice when the cache is warm.
    $this->assertSession()->responseNotMatches('#<html.*<html#');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Ensure a conditional request with If-Modified-Since newer than
    // Last-Modified returns 200 OK.
    $this->drupalGet('', [], [
      'If-Modified-Since' => gmdate(DateTimePlus::RFC7231, strtotime($last_modified) + 1),
      'If-None-Match' => $etag,
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Ensure a conditional request by an authenticated user returns 200 OK.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('', [], ['If-Modified-Since' => $last_modified, 'If-None-Match' => $etag]);
    $this->assertSession()->statusCodeEquals(200);
    // Verify that absence of Page was not cached.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
  }

  /**
   * Tests cache headers.
   */
  public function testPageCache(): void {
    $this->enablePageCaching();

    // Fill the cache.
    $this->drupalGet('system-test/set-header', ['query' => ['name' => 'Foo', 'value' => 'bar']]);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderContains('Vary', 'cookie');
    // Symfony's Response logic determines a specific order for the subvalues
    // of the Cache-Control header, even if they are explicitly passed in to
    // the response header bag in a different order.
    $this->assertCacheMaxAge(300);
    $this->assertSession()->responseHeaderEquals('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
    $this->assertSession()->responseHeaderEquals('Foo', 'bar');

    // Check cache.
    $this->drupalGet('system-test/set-header', ['query' => ['name' => 'Foo', 'value' => 'bar']]);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderContains('Vary', 'cookie');
    $this->assertCacheMaxAge(300);
    $this->assertSession()->responseHeaderEquals('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
    $this->assertSession()->responseHeaderEquals('Foo', 'bar');

    // Check replacing default headers.
    $this->drupalGet('system-test/set-header', ['query' => ['name' => 'Expires', 'value' => 'Fri, 19 Nov 2008 05:00:00 GMT']]);
    $this->assertSession()->responseHeaderEquals('Expires', 'Fri, 19 Nov 2008 05:00:00 GMT');
    $this->drupalGet('system-test/set-header', ['query' => ['name' => 'Vary', 'value' => 'User-Agent']]);
    $this->assertSession()->responseHeaderContains('Vary', 'user-agent');

    // Check that authenticated users bypass the cache.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('system-test/set-header', ['query' => ['name' => 'Foo', 'value' => 'bar']]);
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertSession()->responseHeaderNotContains('Vary', 'cookie');
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'must-revalidate, no-cache, private');
    $this->assertSession()->responseHeaderEquals('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
    $this->assertSession()->responseHeaderEquals('Foo', 'bar');

    // Until bubbling of max-age up to the response is supported, verify that
    // a custom #cache max-age set on an element does not affect page max-age.
    $this->drupalLogout();
    $this->drupalGet('system-test/cache_max_age_page');
    $this->assertCacheMaxAge(300);
  }

  /**
   * Tests the automatic presence of the anonymous role's cache tag.
   *
   * The 'user.permissions' cache context ensures that if the permissions for a
   * role are modified, users are not served stale render cache content. But,
   * when entire responses are cached in reverse proxies, the value for the
   * cache context is never calculated, causing the stale response to not be
   * invalidated. Therefore, when varying by permissions and the current user is
   * the anonymous user, the cache tag for the 'anonymous' role must be added.
   *
   * This test verifies that, and it verifies that it does not happen for other
   * roles.
   */
  public function testPageCacheAnonymousRolePermissions(): void {
    $this->enablePageCaching();

    $content_url = Url::fromRoute('system_test.permission_dependent_content');
    $route_access_url = Url::fromRoute('system_test.permission_dependent_route_access');

    // 1. anonymous user, without permission.
    $this->drupalGet($content_url);
    $this->assertSession()->pageTextContains('Permission to pet llamas: no!');
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.role.anonymous');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.role.anonymous');

    // 2. anonymous user, with permission.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['pet llamas']);
    $this->drupalGet($content_url);
    $this->assertSession()->pageTextContains('Permission to pet llamas: yes!');
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.role.anonymous');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:user.role.anonymous');

    // 3. authenticated user, without permission.
    $auth_user = $this->drupalCreateUser();
    $this->drupalLogin($auth_user);
    $this->drupalGet($content_url);
    $this->assertSession()->pageTextContains('Permission to pet llamas: no!');
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:user.role.authenticated');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:user.role.authenticated');

    // 4. authenticated user, with permission.
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['pet llamas']);
    $this->drupalGet($content_url);
    $this->assertSession()->pageTextContains('Permission to pet llamas: yes!');
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:user.role.authenticated');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:user.role.authenticated');
  }

  /**
   * Tests the 4xx-response cache tag is added and invalidated.
   */
  public function testPageCacheAnonymous403404(): void {
    $admin_url = Url::fromRoute('system.admin');
    $invalid_url = 'foo/does_not_exist';
    $tests = [
      403 => $admin_url,
      404 => $invalid_url,
    ];
    $cache_ttl_4xx = Settings::get('cache_ttl_4xx', 3600);
    foreach ($tests as $code => $content_url) {
      // Anonymous user, without permissions.
      $this->drupalGet($content_url);
      $this->assertSession()->statusCodeEquals($code);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
      $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', '4xx-response');
      $this->drupalGet($content_url);
      $this->assertSession()->statusCodeEquals($code);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
      $entity_values = [
        'name' => $this->randomMachineName(),
        'user_id' => 1,
        'field_test_text' => [
          0 => [
            'value' => $this->randomString(),
            'format' => 'plain_text',
          ],
        ],
      ];
      $entity = EntityTest::create($entity_values);
      $entity->save();
      // Saving an entity clears 4xx cache tag.
      $this->drupalGet($content_url);
      $this->assertSession()->statusCodeEquals($code);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
      $this->drupalGet($content_url);
      $this->assertSession()->statusCodeEquals($code);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
      // Rebuilding the router should invalidate the 4xx cache tag.
      $this->container->get('router.builder')->rebuild();
      $this->drupalGet($content_url);
      $this->assertSession()->statusCodeEquals($code);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

      // Ensure the 'expire' field on the cache entry uses cache_ttl_4xx.
      $cache_item = \Drupal::service('cache.page')->get($this->getUrl() . ':');
      $difference = $cache_item->expire - (int) $cache_item->created;
      // Given that a second might have passed we cannot be sure that
      // $difference will exactly equal the default cache_ttl_4xx setting.
      // Account for any timing difference or rounding errors by ensuring the
      // value is within 10 seconds.
      $this->assertTrue(
        $difference > $cache_ttl_4xx - 10 &&
        $difference < $cache_ttl_4xx + 10,
        "The cache entry expiry time uses the cache_ttl_4xx setting. Expire: {$cache_item->expire} Created: {$cache_item->created}"
      );
    }

    // Disable 403 and 404 caching.
    $settings['settings']['cache_ttl_4xx'] = (object) [
      'value' => 0,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    \Drupal::service('cache.page')->deleteAll();

    foreach ($tests as $code => $content_url) {
      // Getting the 404 page twice should still result in a cache miss.
      $this->drupalGet($content_url);
      $this->drupalGet($content_url);
      $this->assertSession()->statusCodeEquals($code);
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    }
  }

  /**
   * Tests the omit_vary_cookie setting.
   */
  public function testPageCacheWithoutVaryCookie(): void {
    $this->enablePageCaching();

    $settings['settings']['omit_vary_cookie'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    // Fill the cache.
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderNotContains('Vary', 'cookie');
    $this->assertCacheMaxAge(300);

    // Check cache.
    $this->drupalGet('');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderNotContains('Vary', 'cookie');
    $this->assertCacheMaxAge(300);
  }

  /**
   * Tests the setting of forms to be immutable.
   */
  public function testFormImmutability(): void {
    // Install the module that provides the test form.
    $this->container->get('module_installer')
      ->install(['page_cache_form_test']);
    // Uninstall the page_cache module to verify that form is immutable
    // regardless of the internal page cache module.
    $this->container->get('module_installer')->uninstall(['page_cache']);

    $this->drupalGet('page_cache_form_test_immutability');

    $this->assertSession()->pageTextContains("Immutable: TRUE");

    // The immutable flag is set unconditionally by system_form_alter(), set
    // a flag to tell page_cache_form_test_module_implements_alter() to disable
    // that implementation.
    \Drupal::state()->set('page_cache_bypass_form_immutability', TRUE);
    \Drupal::moduleHandler()->resetImplementations();
    Cache::invalidateTags(['rendered']);

    $this->drupalGet('page_cache_form_test_immutability');

    $this->assertSession()->pageTextContains("Immutable: FALSE");
  }

  /**
   * Tests cacheability of a CacheableResponse.
   *
   * Tests the difference between having a controller return a plain Symfony
   * Response object versus returning a Response object that implements the
   * CacheableResponseInterface.
   */
  public function testCacheableResponseResponses(): void {
    $this->enablePageCaching();

    // GET a URL, which would be marked as a cache miss if it were cacheable.
    $this->drupalGet('/system-test/respond-response');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'must-revalidate, no-cache, private');

    // GET it again, verify it's still not cached.
    $this->drupalGet('/system-test/respond-response');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'must-revalidate, no-cache, private');

    // GET a URL, which would be marked as a cache miss if it were cacheable.
    $this->drupalGet('/system-test/respond-public-response');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'max-age=60, public');

    // GET it again, verify it's still not cached.
    $this->drupalGet('/system-test/respond-public-response');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'max-age=60, public');

    // GET a URL, which should be marked as a cache miss.
    $this->drupalGet('/system-test/respond-cacheable-response');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertCacheMaxAge(300);

    // GET it again, it should now be a cache hit.
    $this->drupalGet('/system-test/respond-cacheable-response');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertCacheMaxAge(300);

    // Uninstall page cache. This should flush all caches so the next call to a
    // previously cached page should be a miss now.
    $this->container->get('module_installer')
      ->uninstall(['page_cache']);

    // GET a URL that was cached by Page Cache before, it should not be now.
    $this->drupalGet('/respond-cacheable-response');
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
  }

  /**
   * Tests that HEAD requests are treated the same as GET requests.
   */
  public function testHead(): void {
    /** @var \GuzzleHttp\ClientInterface $client */
    $client = $this->getSession()->getDriver()->getClient()->getClient();

    // GET, then HEAD.
    $url_a = $this->buildUrl('system-test/set-header', ['query' => ['name' => 'Foo', 'value' => 'bar']]);
    $response_body = $this->drupalGet($url_a);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Foo', 'bar');
    $this->assertEquals('The following header was set: <em class="placeholder">Foo</em>: <em class="placeholder">bar</em>', $response_body);
    $response = $client->request('HEAD', $url_a);
    $this->assertEquals('HIT', $response->getHeaderLine('X-Drupal-Cache'), 'Page was cached.');
    $this->assertEquals('bar', $response->getHeaderLine('Foo'), 'Custom header was sent.');
    $this->assertEquals('', $response->getBody()->getContents());

    // HEAD, then GET.
    $url_b = $this->buildUrl('system-test/set-header', ['query' => ['name' => 'Foo', 'value' => 'baz']]);
    $response = $client->request('HEAD', $url_b);
    $this->assertEquals('MISS', $response->getHeaderLine('X-Drupal-Cache'), 'Page was not cached.');
    $this->assertEquals('baz', $response->getHeaderLine('Foo'), 'Custom header was sent.');
    $this->assertEquals('', $response->getBody()->getContents());
    $response_body = $this->drupalGet($url_b);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Foo', 'baz');
    $this->assertEquals('The following header was set: <em class="placeholder">Foo</em>: <em class="placeholder">baz</em>', $response_body);
  }

  /**
   * Tests a cacheable response with custom cache control.
   */
  public function testCacheableWithCustomCacheControl(): void {
    $this->enablePageCaching();

    $this->drupalGet('/system-test/custom-cache-control');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'bar, private');
  }

  /**
   * Tests that the Cache-Control header is added by FinishResponseSubscriber.
   */
  public function testCacheabilityOfRedirectResponses(): void {
    $this->enablePageCaching();

    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
    $this->maximumMetaRefreshCount = 0;

    foreach ([301, 302, 303, 307, 308] as $status_code) {
      foreach (['local', 'cacheable', 'trusted'] as $type) {
        $this->drupalGet("/system-test/redirect/{$type}/{$status_code}");
        $this->assertSession()->statusCodeEquals($status_code);
        $this->assertCacheMaxAge(300);
      }
    }
  }

  /**
   * Tests that URLs are cached in a not normalized form.
   */
  public function testNoUrlNormalization(): void {
    // Use absolute URLs to avoid any processing.
    $url = Url::fromRoute('<front>')->setAbsolute()->toString();

    // In each test, the first array value is raw URL, the second one is the
    // possible normalized URL.
    $tests = [
      [
        $url . '?z=z&a=a',
        $url . '?a=a&z=z',
      ],
      [
        $url . '?a=b+c',
        $url . '?a=b%20c',
      ],
    ];

    foreach ($tests as [$url_raw, $url_normalized]) {
      // Initialize cache on raw URL.
      $headers = $this->getHeaders($url_raw);
      $this->assertEquals('MISS', $headers['X-Drupal-Cache']);

      // Ensure cache was set.
      $headers = $this->getHeaders($url_raw);
      $this->assertEquals('HIT', $headers['X-Drupal-Cache'], "Cache was set for {$url_raw} URL.");

      // Check if the normalized URL is not cached.
      $headers = $this->getHeaders($url_normalized);
      $this->assertEquals('MISS', $headers['X-Drupal-Cache'], "Cache is missing for {$url_normalized} URL.");
    }
  }

  /**
   * Retrieves only the headers for an absolute path.
   *
   * Executes a cURL request without any modifications to the given URL.
   * Note that Guzzle always normalizes URLs which prevents testing all
   * possible edge cases.
   *
   * @param string $url
   *   URL to request.
   *
   * @return array
   *   Array of headers.
   */
  protected function getHeaders($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, drupal_generate_test_ua($this->databasePrefix));
    $output = curl_exec($ch);
    curl_close($ch);

    $headers = [];
    foreach (explode("\n", $output) as $header) {
      if (strpos($header, ':')) {
        [$key, $value] = explode(':', $header, 2);
        $headers[trim($key)] = trim($value);
      }
    }

    return $headers;
  }

}
