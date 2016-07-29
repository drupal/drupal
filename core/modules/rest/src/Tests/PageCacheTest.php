<?php

namespace Drupal\rest\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Tests page caching for REST GET requests.
 *
 * @group rest
 */
class PageCacheTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal');

  /**
   * Tests that configuration changes also clear the page cache.
   */
  public function testConfigChangePageCache() {
    $this->enableService('entity:entity_test', 'GET');
    // Allow anonymous users to issue GET requests.
    $permissions = $this->entityPermissions('entity_test', 'view');
    $permissions[] = 'restful get entity:entity_test';
    user_role_grant_permissions('anonymous', $permissions);

    // Create an entity programmatically.
    $entity = $this->entityCreate('entity_test');
    $entity->set('field_test_text', 'custom cache tag value');
    $entity->save();
    // Read it over the REST API.
    $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'MISS');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');
    $this->assertCacheTag('entity_test_access:field_test_text');

    // Read it again, should be page-cached now.
    $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'HIT');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');
    $this->assertCacheTag('entity_test_access:field_test_text');

    // Trigger a config save which should clear the page cache, so we should get
    // a cache miss now for the same request.
    $this->config('rest.settings')->save();
    $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'MISS');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');
    $this->assertCacheTag('entity_test_access:field_test_text');
  }

  /**
   * Tests HEAD support when a REST resource supports GET.
   */
  public function testHeadSupport() {
    user_role_grant_permissions('anonymous', ['view test entity', 'restful get entity:entity_test']);

    // Create an entity programatically.
    $this->entityCreate('entity_test')->save();

    $url = Url::fromUri('internal:/entity_test/1?_format=' . $this->defaultFormat);

    $this->enableService('entity:entity_test', 'GET');

    $this->httpRequest($url, 'HEAD', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('X-Drupal-Cache', 'MISS');
    $this->assertResponseBody('');

    $response = $this->httpRequest($url, 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('X-Drupal-Cache', 'HIT');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');
    $data = Json::decode($response);
    $this->assertEqual($data['type'][0]['value'], 'entity_test');
  }

}
