<?php

/**
 * @file
 * Contains \Drupal\page_cache\Tests\PageCacheTest.
 */

namespace Drupal\page_cache\Tests;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Cache\Cache;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Enables the page cache and tests it with various HTTP requests.
 *
 * @group page_cache
 */
class PageCacheTest extends WebTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('test_page_test', 'system_test', 'entity_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config('system.site')
      ->set('name', 'Drupal')
      ->set('page.front', '/test-page')
      ->save();
  }

  /**
   * Test that cache tags are properly persisted.
   *
   * Since tag based invalidation works, we know that our tag properly
   * persisted.
   */
  function testPageCacheTags() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    $path = 'system-test/cache_tags_page';
    $tags = array('system_test_cache_tags_page');
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $cid_parts = array(\Drupal::url('system_test.cache_tags_page', array(), array('absolute' => TRUE)), 'html');
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('render')->get($cid);
    sort($cache_entry->tags);
    $expected_tags = array(
      'pre_render',
      'rendered',
      'system_test_cache_tags_page',
    );
    $this->assertIdentical($cache_entry->tags, $expected_tags);

    Cache::invalidateTags($tags);
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
  }

  /**
   * Tests support for different cache items with different request formats
   * specified via a query parameter.
   */
  function testQueryParameterFormatRequests() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    $accept_header_cache_url = Url::fromRoute('system_test.page_cache_accept_header');
    $accept_header_cache_url_with_json = Url::fromRoute('system_test.page_cache_accept_header', ['_format' => 'json']);

    $this->drupalGet($accept_header_cache_url);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'HTML page was not yet cached.');
    $this->drupalGet($accept_header_cache_url);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'HTML page was cached.');
    $this->assertRaw('<p>oh hai this is html.</p>', 'The correct HTML response was returned.');

    $this->drupalGet($accept_header_cache_url_with_json);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Json response was not yet cached.');
    $this->drupalGet($accept_header_cache_url_with_json);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Json response was cached.');
    $this->assertRaw('{"content":"oh hai this is json"}', 'The correct Json response was returned.');

    // Enable REST support for nodes and hal+json.
    \Drupal::service('module_installer')->install(['node', 'rest', 'hal']);
    $this->drupalCreateContentType(['type' => 'article']);
    $node = $this->drupalCreateNode(['type' => 'article']);
    $node_uri = $node->urlInfo();
    $node_url_with_hal_json_format = $node->urlInfo('canonical')->setRouteParameter('_format', 'hal_json');
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('anonymous');
    $role->grantPermission('restful get entity:node');
    $role->save();

    $this->drupalGet($node_uri);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'text/html; charset=UTF-8');
    $this->drupalGet($node_uri);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'text/html; charset=UTF-8');

    // Now request a HAL page, we expect that the first request is a cache miss
    // and it serves HTML.
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/hal+json');
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/hal+json');

    // Clear the page cache. After that request a HAL request, followed by an
    // ordinary HTML one.
    \Drupal::cache('render')->deleteAll();
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/hal+json');
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/hal+json');

    $this->drupalGet($node_uri);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'text/html; charset=UTF-8');
    $this->drupalGet($node_uri);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'text/html; charset=UTF-8');
  }

  /**
   * Tests support of requests with If-Modified-Since and If-None-Match headers.
   */
  function testConditionalRequests() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Fill the cache.
    $this->drupalGet('');
    // Verify the page is not printed twice when the cache is cold.
    $this->assertNoPattern('#<html.*<html#');

    $this->drupalHead('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $etag = $this->drupalGetHeader('ETag');
    $last_modified = $this->drupalGetHeader('Last-Modified');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(304, 'Conditional request returned 304 Not Modified.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC822, strtotime($last_modified)), 'If-None-Match: ' . $etag));
    $this->assertResponse(304, 'Conditional request with obsolete If-Modified-Since date returned 304 Not Modified.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC850, strtotime($last_modified)), 'If-None-Match: ' . $etag));
    $this->assertResponse(304, 'Conditional request with obsolete If-Modified-Since date returned 304 Not Modified.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified));
    // Verify the page is not printed twice when the cache is warm.
    $this->assertNoPattern('#<html.*<html#');
    $this->assertResponse(200, 'Conditional request without If-None-Match returned 200 OK.');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DateTimePlus::RFC7231, strtotime($last_modified) + 1), 'If-None-Match: ' . $etag));
    $this->assertResponse(200, 'Conditional request with new a If-Modified-Since date newer than Last-Modified returned 200 OK.');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');

    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(200, 'Conditional request returned 200 OK for authenticated user.');
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'Absence of Page was not cached.');
  }

  /**
   * Tests cache headers.
   */
  function testPageCache() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->set('response.gzip', 1);
    $config->save();

    // Fill the cache.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->assertEqual(strtolower($this->drupalGetHeader('Vary')), 'cookie,accept-encoding', 'Vary header was sent.');
    // Symfony's Response logic determines a specific order for the subvalues
    // of the Cache-Control header, even if they are explicitly passed in to
    // the response header bag in a different order.
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public', 'Cache-Control header was sent.');
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', 'Expires header was sent.');
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', 'Custom header was sent.');

    // Check cache.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertEqual(strtolower($this->drupalGetHeader('Vary')), 'cookie,accept-encoding', 'Vary: Cookie header was sent.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public', 'Cache-Control header was sent.');
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', 'Expires header was sent.');
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', 'Custom header was sent.');

    // Check replacing default headers.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Expires', 'value' => 'Fri, 19 Nov 2008 05:00:00 GMT')));
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Fri, 19 Nov 2008 05:00:00 GMT', 'Default header was replaced.');
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Vary', 'value' => 'User-Agent')));
    $this->assertEqual(strtolower($this->drupalGetHeader('Vary')), 'user-agent,accept-encoding', 'Default header was replaced.');

    // Check that authenticated users bypass the cache.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'Caching was bypassed.');
    $this->assertTrue(strpos(strtolower($this->drupalGetHeader('Vary')), 'cookie') === FALSE, 'Vary: Cookie header was not sent.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'must-revalidate, no-cache, post-check=0, pre-check=0, private', 'Cache-Control header was sent.');
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', 'Expires header was sent.');
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', 'Custom header was sent.');

    // Until bubbling of max-age up to the response is supported, verify that
    // a custom #cache max-age set on an element does not affect page max-age.
    $this->drupalLogout();
    $this->drupalGet('system-test/cache_maxage_page');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public');
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
  public function testPageCacheAnonymousRolePermissions() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    $content_url = Url::fromRoute('system_test.permission_dependent_content');
    $route_access_url = Url::fromRoute('system_test.permission_dependent_route_access');

    // 1. anonymous user, without permission.
    $this->drupalGet($content_url);
    $this->assertText('Permission to pet llamas: no!');
    $this->assertCacheContext('user.permissions');
    $this->assertCacheTag('config:user.role.anonymous');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertCacheTag('config:user.role.anonymous');

    // 2. anonymous user, with permission.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['pet llamas']);
    $this->drupalGet($content_url);
    $this->assertText('Permission to pet llamas: yes!');
    $this->assertCacheContext('user.permissions');
    $this->assertCacheTag('config:user.role.anonymous');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertCacheTag('config:user.role.anonymous');

    // 3. authenticated user, without permission.
    $auth_user = $this->drupalCreateUser();
    $this->drupalLogin($auth_user);
    $this->drupalGet($content_url);
    $this->assertText('Permission to pet llamas: no!');
    $this->assertCacheContext('user.permissions');
    $this->assertNoCacheTag('config:user.role.authenticated');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertNoCacheTag('config:user.role.authenticated');

    // 4. authenticated user, with permission.
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['pet llamas']);
    $this->drupalGet($content_url);
    $this->assertText('Permission to pet llamas: yes!');
    $this->assertCacheContext('user.permissions');
    $this->assertNoCacheTag('config:user.role.authenticated');
    $this->drupalGet($route_access_url);
    $this->assertCacheContext('user.permissions');
    $this->assertNoCacheTag('config:user.role.authenticated');
  }

  /**
   * Tests the 4xx-response cache tag is added and invalidated.
   */
  function testPageCacheAnonymous403404() {
    $admin_url = Url::fromRoute('system.admin');
    $invalid_url = 'foo/does_not_exist';
    $tests = [
      403 => $admin_url,
      404 => $invalid_url,
    ];
    foreach ($tests as $code => $content_url) {
      // Anonymous user, without permissions.
      $this->drupalGet($content_url);
      $this->assertResponse($code);
      $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
      $this->assertCacheTag('4xx-response');
      $this->drupalGet($content_url);
      $this->assertResponse($code);
      $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
      $entity_values = array(
        'name' => $this->randomMachineName(),
        'user_id' => 1,
        'field_test_text' => array(
          0 => array(
            'value' => $this->randomString(),
            'format' => 'plain_text',
          )
        ),
      );
      $entity = EntityTest::create($entity_values);
      $entity->save();
      // Saving an entity clears 4xx cache tag.
      $this->drupalGet($content_url);
      $this->assertResponse($code);
      $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
      $this->drupalGet($content_url);
      $this->assertResponse($code);
      $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
      // Rebuilding the router should invalidate the 4xx cache tag.
      $this->container->get('router.builder')->rebuild();
      $this->drupalGet($content_url);
      $this->assertResponse($code);
      $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    }
  }

  /**
   * Tests the omit_vary_cookie setting.
   */
  public function testPageCacheWithoutVaryCookie() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    $settings['settings']['omit_vary_cookie'] = (object) array(
      'value' => TRUE,
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    // Fill the cache.
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->assertTrue(strpos($this->drupalGetHeader('Vary'), 'Cookie') === FALSE, 'Vary: Cookie header was not sent.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public', 'Cache-Control header was sent.');

    // Check cache.
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertTrue(strpos($this->drupalGetHeader('Vary'), 'Cookie') === FALSE, 'Vary: Cookie header was not sent.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public', 'Cache-Control header was sent.');
  }

  /**
   * Test the setting of forms to be immutable.
   */
  public function testFormImmutability() {
    // Install the module that provides the test form.
    $this->container->get('module_installer')
      ->install(['page_cache_form_test']);
    // Uninstall the page_cache module to verify that form is immutable
    // regardless of the internal page cache module.
    $this->container->get('module_installer')->uninstall(['page_cache']);
    \Drupal::service('router.builder')->rebuild();

    $this->drupalGet('page_cache_form_test_immutability');

    $this->assertText("Immutable: TRUE", "Form is immutable.");

    // The immutable flag is set unconditionally by system_form_alter(), set
    // a flag to tell page_cache_form_test_module_implements_alter() to disable
    // that implementation.
    \Drupal::state()->set('page_cache_bypass_form_immutability', TRUE);
    \Drupal::moduleHandler()->resetImplementations();
    \Drupal::cache('render')->deleteAll();

    $this->drupalGet('page_cache_form_test_immutability');

    $this->assertText("Immutable: FALSE", "Form is not immutable,");
  }

}
