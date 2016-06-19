<?php

namespace Drupal\rest\Tests;

use Drupal\Core\Url;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests page caching for REST GET requests.
 *
 * @group rest
 */
class PageCacheTest extends RESTTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  /**
   * The 'serializer' service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Get the 'serializer' service.
    $this->serializer = $this->container->get('serializer');
  }

  /**
   * Tests that configuration changes also clear the page cache.
   */
  public function testConfigChangePageCache() {
    // Allow anonymous users to issue GET requests.
    user_role_grant_permissions('anonymous', ['view test entity', 'restful get entity:entity_test']);

    $this->enableService('entity:entity_test', 'POST');
    $permissions = [
      'administer entity_test content',
      'restful post entity:entity_test',
      'restful get entity:entity_test',
      'restful patch entity:entity_test',
      'restful delete entity:entity_test',
    ];
    $account = $this->drupalCreateUser($permissions);

    // Create an entity and test that the response from a post request is not
    // cacheable.
    $entity = $this->entityCreate('entity_test');
    $entity->set('field_test_text', 'custom cache tag value');
    $serialized = $this->serializer->serialize($entity, $this->defaultFormat);
    // Log in for creating the entity.
    $this->drupalLogin($account);
    $this->httpRequest('entity/entity_test', 'POST', $serialized, $this->defaultMimeType);
    $this->assertResponse(201);

    if ($this->getCacheHeaderValues('x-drupal-cache')) {
      $this->fail('Post request is cached.');
    }
    $this->drupalLogout();

    $url = Url::fromUri('internal:/entity_test/1?_format=' . $this->defaultFormat);

    // Read it over the REST API.
    $this->enableService('entity:entity_test', 'GET');
    $this->httpRequest($url, 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'MISS');
    $this->assertCacheTag('config:rest.resource.entity.entity_test');
    $this->assertCacheTag('entity_test:1');
    $this->assertCacheTag('entity_test_access:field_test_text');

    // Read it again, should be page-cached now.
    $this->httpRequest($url, 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'HIT');
    $this->assertCacheTag('config:rest.resource.entity.entity_test');
    $this->assertCacheTag('entity_test:1');
    $this->assertCacheTag('entity_test_access:field_test_text');

    // Trigger a resource config save which should clear the page cache, so we
    // should get a cache miss now for the same request.
    $this->resourceConfigStorage->load('entity.entity_test')->save();
    $this->httpRequest($url, 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200, 'HTTP response code is correct.');
    $this->assertHeader('x-drupal-cache', 'MISS');
    $this->assertCacheTag('config:rest.resource.entity.entity_test');
    $this->assertCacheTag('entity_test:1');
    $this->assertCacheTag('entity_test_access:field_test_text');

    // Log in for deleting / updating entity.
    $this->drupalLogin($account);

    // Test that updating an entity is not cacheable.
    $this->enableService('entity:entity_test', 'PATCH');

    // Create a second stub entity for overwriting a field.
    $patch_values['field_test_text'] = [0 => [
      'value' => 'patched value',
      'format' => 'plain_text',
    ]];
    $patch_entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create($patch_values);
    // We don't want to overwrite the UUID.
    $patch_entity->set('uuid', NULL);
    $serialized = $this->container->get('serializer')
      ->serialize($patch_entity, $this->defaultFormat);

    // Update the entity over the REST API.
    $this->httpRequest($url, 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    if ($this->getCacheHeaderValues('x-drupal-cache')) {
      $this->fail('Patch request is cached.');
    }

    // Test that the response from a delete request is not cacheable.
    $this->enableService('entity:entity_test', 'DELETE');
    $this->httpRequest($url, 'DELETE');
    $this->assertResponse(204);

    if ($this->getCacheHeaderValues('x-drupal-cache')) {
      $this->fail('Patch request is cached.');
    }
  }

}
