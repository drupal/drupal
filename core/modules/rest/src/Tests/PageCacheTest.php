<?php

/**
 * @file
 * Contains \Drupal\rest\Tests\PageCacheTest.
 */

namespace Drupal\rest\Tests;

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
  public static $modules = array('hal', 'rest', 'entity_test');

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
    $entity->save();
    // Read it over the REST API.
    $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'MISS');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');

    // Read it again, should be page-cached now.
    $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'HIT');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');

    // Trigger a config save which should clear the page cache, so we should get
    // a cache miss now for the same request.
    $this->config('rest.settings')->save();
    $this->httpRequest($entity->urlInfo()->setRouteParameter('_format', $this->defaultFormat), 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'MISS');
    $this->assertCacheTag('config:rest.settings');
    $this->assertCacheTag('entity_test:1');
  }

}
