<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rest\ResourceResponseInterface;
use Drupal\Tests\rest\Functional\ResourceTestBase;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Even though there is the generic EntityResource, it's necessary for every
 * entity type to have its own test, because they each have different fields,
 * validation constraints, et cetera. It's not because the generic case works,
 * that every case works.
 *
 * Furthermore, it's necessary to test every format separately, because there
 * can be entity type-specific normalization or serialization problems.
 *
 * Subclass this for every entity type. Also respect instructions in
 * \Drupal\rest\Tests\ResourceTestBase.
 *
 * For example, for the node test coverage, there is the (abstract)
 * \Drupal\Tests\rest\Functional\EntityResource\Node\NodeResourceTestBase, which
 * is then again subclassed for every authentication provider:
 * - \Drupal\Tests\rest\Functional\EntityResource\Node\NodeJsonAnonTest
 * - \Drupal\Tests\rest\Functional\EntityResource\Node\NodeJsonBasicAuthTest
 * - \Drupal\Tests\rest\Functional\EntityResource\Node\NodeJsonCookieTest
 * But the HAL module also adds a new format ('hal_json'), so that format also
 * needs test coverage (for its own peculiarities in normalization & encoding):
 * - \Drupal\Tests\hal\Functional\EntityResource\Node\NodeHalJsonAnonTest
 * - \Drupal\Tests\hal\Functional\EntityResource\Node\NodeHalJsonBasicAuthTest
 * - \Drupal\Tests\hal\Functional\EntityResource\Node\NodeHalJsonCookieTest
 *
 * In other words: for every entity type there should be:
 * 1. an abstract subclass that includes the entity type-specific authorization
 *    (permissions or perhaps custom access control handling, such as node
 *    grants), plus
 * 2. a concrete subclass extending the abstract entity type-specific subclass
 *    that specifies the exact @code $format @endcode, @code $mimeType @endcode
 *    and @code $auth @endcode for this concrete test. Usually that's all that's
 *    necessary: most concrete subclasses will be very thin.
 *
 * For each of these concrete subclasses, a comprehensive test scenario will
 * run for the GET HTTP method.
 */
abstract class EntityResourceTestBase extends ResourceTestBase {

  /**
   * The tested entity type.
   *
   * @var string
   */
  protected static $entityTypeId = NULL;

  /**
   * The main entity used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['rest_test', 'text'];

  /**
   * Provides an entity resource.
   *
   * @param bool $single_format
   *   Provisions a single-format entity REST resource. Defaults to FALSE.
   */
  protected function provisionEntityResource($single_format = FALSE) {
    if ($existing = $this->resourceConfigStorage->load(static::$resourceConfigId)) {
      $existing->delete();
    }

    $format = $single_format
      ? [static::$format]
      : [static::$format, 'foobar'];
    // It's possible to not have any authentication providers enabled, when
    // testing public (anonymous) usage of a REST resource.
    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource($format, $auth);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Calculate REST Resource config entity ID.
    static::$resourceConfigId = 'entity.' . static::$entityTypeId;

    $this->entityStorage = $this->container->get('entity_type.manager')
      ->getStorage(static::$entityTypeId);

    // Create an entity.
    $this->entity = $this->createEntity();

    if ($this->entity instanceof FieldableEntityInterface) {
      // Add access-protected field.
      FieldStorageConfig::create([
        'entity_type' => static::$entityTypeId,
        'field_name' => 'field_rest_test',
        'type' => 'text',
      ])
        ->setCardinality(1)
        ->save();
      FieldConfig::create([
        'entity_type' => static::$entityTypeId,
        'field_name' => 'field_rest_test',
        'bundle' => $this->entity->bundle(),
      ])
        ->setLabel('Test field')
        ->setTranslatable(FALSE)
        ->save();

      // Add multi-value field.
      FieldStorageConfig::create([
        'entity_type' => static::$entityTypeId,
        'field_name' => 'field_rest_test_multivalue',
        'type' => 'string',
      ])
        ->setCardinality(3)
        ->save();
      FieldConfig::create([
        'entity_type' => static::$entityTypeId,
        'field_name' => 'field_rest_test_multivalue',
        'bundle' => $this->entity->bundle(),
      ])
        ->setLabel('Test field: multi-value')
        ->setTranslatable(FALSE)
        ->save();

      // Reload entity so that it has the new field.
      $reloaded_entity = $this->entityStorage->loadUnchanged($this->entity->id());
      // Some entity types are not stored, hence they cannot be reloaded.
      if ($reloaded_entity !== NULL) {
        $this->entity = $reloaded_entity;

        // Set a default value on the fields.
        $this->entity->set('field_rest_test', ['value' => 'All the faith they had had had had no effect on the outcome of their life.']);
        $this->entity->set('field_rest_test_multivalue', [['value' => 'One'], ['value' => 'Two']]);
        $this->entity->set('rest_test_validation', ['value' => 'allowed value']);
        $this->entity->save();
      }
    }
  }

  /**
   * Creates the entity to be tested.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to be tested.
   */
  abstract protected function createEntity();

  /**
   * Returns the expected normalization of the entity.
   *
   * @see ::createEntity()
   *
   * @return array
   */
  abstract protected function getExpectedNormalizedEntity();

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    $permission = $this->entity->getEntityType()->getAdminPermission();
    if ($permission !== FALSE) {
      return "The '{$permission}' permission is required.";
    }

    $http_method_to_entity_operation = [
      'GET' => 'view',
      'POST' => 'create',
      'PATCH' => 'update',
      'DELETE' => 'delete',
    ];
    $operation = $http_method_to_entity_operation[$method];
    $message = sprintf('You are not authorized to %s this %s entity', $operation, $this->entity->getEntityTypeId());

    if ($this->entity->bundle() !== $this->entity->getEntityTypeId()) {
      $message .= ' of bundle ' . $this->entity->bundle();
    }

    return "$message.";
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return (new CacheableMetadata())
      ->setCacheTags(static::$auth
        ? ['4xx-response', 'http_response']
        : ['4xx-response', 'config:user.role.anonymous', 'http_response'])
      ->setCacheContexts(['user.permissions']);
  }

  /**
   * The cacheability of unauthorized 'view' entity access.
   *
   * @param bool $is_authenticated
   *   Whether the current request is authenticated or not. This matters for
   *   some entity access control handlers, but not for most.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The expected cacheability.
   */
  protected function getExpectedUnauthorizedEntityAccessCacheability($is_authenticated) {
    return new CacheableMetadata();
  }

  /**
   * The expected cache tags for the GET/HEAD response of the test entity.
   *
   * @see ::testGet
   *
   * @return string[]
   */
  protected function getExpectedCacheTags() {
    $expected_cache_tags = [
      'config:rest.resource.entity.' . static::$entityTypeId,
    ];
    if (!static::$auth) {
      $expected_cache_tags[] = 'config:user.role.anonymous';
    }
    $expected_cache_tags[] = 'http_response';
    return Cache::mergeTags($expected_cache_tags, $this->entity->getCacheTags());
  }

  /**
   * The expected cache contexts for the GET/HEAD response of the test entity.
   *
   * @see ::testGet
   *
   * @return string[]
   */
  protected function getExpectedCacheContexts() {
    return [
      'url.site',
      'user.permissions',
    ];
  }

  /**
   * Test a GET request for an entity, plus edge cases to ensure good DX.
   */
  public function testGet() {
    $this->initAuthentication();
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = $this->getEntityResourceUrl();
    $request_options = [];

    // DX: 404 when resource not provisioned, 403 if canonical route. HTML
    // response because missing ?_format query string.
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame($has_canonical_url ? 403 : 404, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 404 when resource not provisioned, 403 if canonical route. Non-HTML
    // response because ?_format query string is present.
    $response = $this->request('GET', $url, $request_options);
    if ($has_canonical_url) {
      $expected_cacheability = $this->getExpectedUnauthorizedAccessCacheability()
        // @see \Drupal\Core\EventSubscriber\AnonymousUserResponseSubscriber::onRespond()
        ->addCacheTags(['config:user.role.anonymous']);
      $expected_cacheability->addCacheableDependency($this->getExpectedUnauthorizedEntityAccessCacheability(FALSE));
      $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('GET'), $response, $expected_cacheability->getCacheTags(), $expected_cacheability->getCacheContexts(), 'MISS', FALSE);
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "GET ' . str_replace($this->baseUrl, '', $this->getEntityResourceUrl()->setAbsolute()->toString()) . '"', $response);
    }

    $this->provisionEntityResource();

    // DX: forgetting authentication: authentication provider-specific error
    // response.
    if (static::$auth) {
      $response = $this->request('GET', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication('GET', $response);
    }

    $request_options[RequestOptions::HEADERS]['REST-test-auth'] = '1';

    // DX: 403 when attempting to use unallowed authentication provider.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, 'The used authentication method is not allowed on this route.', $response);

    unset($request_options[RequestOptions::HEADERS]['REST-test-auth']);
    $request_options[RequestOptions::HEADERS]['REST-test-auth-global'] = '1';

    // DX: 403 when attempting to use unallowed global authentication provider.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, 'The used authentication method is not allowed on this route.', $response);

    unset($request_options[RequestOptions::HEADERS]['REST-test-auth-global']);
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('GET'));

    // First: single format. Drupal will automatically pick the only format.
    $this->provisionEntityResource(TRUE);
    $expected_403_cacheability = $this->getExpectedUnauthorizedAccessCacheability()
      ->addCacheableDependency($this->getExpectedUnauthorizedEntityAccessCacheability(static::$auth !== FALSE));
    // DX: 403 because unauthorized single-format route, ?_format is omittable.
    $url->setOption('query', []);
    $response = $this->request('GET', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(403, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    }
    else {
      $this->assertResourceErrorResponse(403, FALSE, $response, $expected_403_cacheability->getCacheTags(), $expected_403_cacheability->getCacheContexts(), static::$auth ? FALSE : 'MISS', FALSE);
    }
    $this->assertSame(static::$auth ? [] : ['MISS'], $response->getHeader('X-Drupal-Cache'));
    // DX: 403 because unauthorized.
    $url->setOption('query', ['_format' => static::$format]);
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, FALSE, $response, $expected_403_cacheability->getCacheTags(), $expected_403_cacheability->getCacheContexts(), static::$auth ? FALSE : 'MISS', FALSE);

    // Then, what we'll use for the remainder of the test: multiple formats.
    $this->provisionEntityResource();
    // DX: 406 because despite unauthorized, ?_format is not omittable.
    $url->setOption('query', []);
    $response = $this->request('GET', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(403, $response->getStatusCode());
      $this->assertSame(['HIT'], $response->getHeader('X-Drupal-Dynamic-Cache'));
    }
    else {
      $this->assertSame(406, $response->getStatusCode());
      $this->assertSame(['UNCACHEABLE'], $response->getHeader('X-Drupal-Dynamic-Cache'));
    }
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    $this->assertSame(static::$auth ? [] : ['MISS'], $response->getHeader('X-Drupal-Cache'));
    // DX: 403 because unauthorized.
    $url->setOption('query', ['_format' => static::$format]);
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('GET'), $response, $expected_403_cacheability->getCacheTags(), $expected_403_cacheability->getCacheContexts(), static::$auth ? FALSE : 'MISS', FALSE);
    $this->assertArrayNotHasKey('Link', $response->getHeaders());

    $this->setUpAuthorization('GET');

    // 200 for well-formed HEAD request.
    $response = $this->request('HEAD', $url, $request_options);
    $is_cacheable_by_dynamic_page_cache = empty(array_intersect(['user', 'session'], $this->getExpectedCacheContexts()));
    $this->assertResourceResponse(200, '', $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), static::$auth ? FALSE : 'MISS', $is_cacheable_by_dynamic_page_cache ? 'MISS' : 'UNCACHEABLE');
    $head_headers = $response->getHeaders();

    // 200 for well-formed GET request. Page Cache hit because of HEAD request.
    // Same for Dynamic Page Cache hit.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), static::$auth ? FALSE : 'HIT', $is_cacheable_by_dynamic_page_cache ? (static::$auth ? 'HIT' : 'MISS') : 'UNCACHEABLE');
    // Assert that Dynamic Page Cache did not store a ResourceResponse object,
    // which needs serialization after every cache hit. Instead, it should
    // contain a flattened response. Otherwise performance suffers.
    // @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::flattenResponse()
    $cache_items = $this->container->get('database')
      ->select('cache_dynamic_page_cache', 'c')
      ->fields('c', ['cid', 'data'])
      ->condition('c.cid', '%[route]=rest.%', 'LIKE')
      ->execute()
      ->fetchAllAssoc('cid');
    if (!$is_cacheable_by_dynamic_page_cache) {
      $this->assertCount(0, $cache_items);
    }
    else {
      $this->assertCount(2, $cache_items);
      $found_cache_redirect = FALSE;
      $found_cached_200_response = FALSE;
      $other_cached_responses_are_4xx = TRUE;
      foreach ($cache_items as $cid => $cache_item) {
        $cached_data = unserialize($cache_item->data);
        if (!isset($cached_data['#cache_redirect'])) {
          $cached_response = $cached_data['#response'];
          if ($cached_response->getStatusCode() === 200) {
            $found_cached_200_response = TRUE;
          }
          elseif (!$cached_response->isClientError()) {
            $other_cached_responses_are_4xx = FALSE;
          }
          $this->assertNotInstanceOf(ResourceResponseInterface::class, $cached_response);
          $this->assertInstanceOf(CacheableResponseInterface::class, $cached_response);
        }
        else {
          $found_cache_redirect = TRUE;
        }
      }
      $this->assertTrue($found_cache_redirect);
      $this->assertTrue($found_cached_200_response);
      $this->assertTrue($other_cached_responses_are_4xx);
    }

    // Sort the serialization data first so we can do an identical comparison
    // for the keys with the array order the same (it needs to match with
    // identical comparison).
    $expected = $this->getExpectedNormalizedEntity();
    static::recursiveKSort($expected);
    $actual = $this->serializer->decode((string) $response->getBody(), static::$format);
    static::recursiveKSort($actual);
    $this->assertSame($expected, $actual);

    // Not only assert the normalization, also assert deserialization of the
    // response results in the expected object.
    // Note: deserialization of the XML format is not supported, so only test
    // this for other formats.
    if (static::$format !== 'xml') {
      $unserialized = $this->serializer->deserialize((string) $response->getBody(), get_class($this->entity), static::$format);
      $this->assertSame($unserialized->uuid(), $this->entity->uuid());

    }
    // Finally, assert that the expected 'Link' headers are present.
    if ($this->entity->getEntityType()->getLinkTemplates()) {
      $this->assertArrayHasKey('Link', $response->getHeaders());
      $link_relation_type_manager = $this->container->get('plugin.manager.link_relation_type');
      $expected_link_relation_headers = array_map(function ($relation_name) use ($link_relation_type_manager) {
        $link_relation_type = $link_relation_type_manager->createInstance($relation_name);
        return $link_relation_type->isRegistered()
          ? $link_relation_type->getRegisteredName()
          : $link_relation_type->getExtensionUri();
      }, array_keys($this->entity->getEntityType()->getLinkTemplates()));
      $parse_rel_from_link_header = function ($value) use ($link_relation_type_manager) {
        $matches = [];
        if (preg_match('/rel="([^"]+)"/', $value, $matches) === 1) {
          return $matches[1];
        }
        return FALSE;
      };
      $this->assertSame($expected_link_relation_headers, array_map($parse_rel_from_link_header, $response->getHeader('Link')));
    }
    $get_headers = $response->getHeaders();

    // Verify that the GET and HEAD responses are the same. The only difference
    // is that there's no body. For this reason the 'Transfer-Encoding' and
    // 'Vary' headers are also added to the list of headers to ignore, as they
    // may be added to GET requests, depending on web server configuration. They
    // are usually 'Transfer-Encoding: chunked' and 'Vary: Accept-Encoding'.
    $ignored_headers = ['Date', 'Content-Length', 'X-Drupal-Cache', 'X-Drupal-Dynamic-Cache', 'Transfer-Encoding', 'Vary'];
    $header_cleaner = function ($headers) use ($ignored_headers) {
      foreach ($headers as $header => $value) {
        if (strpos($header, 'X-Drupal-Assertion-') === 0 || in_array($header, $ignored_headers)) {
          unset($headers[$header]);
        }
      }
      return $headers;
    };
    $get_headers = $header_cleaner($get_headers);
    $head_headers = $header_cleaner($head_headers);
    $this->assertSame($get_headers, $head_headers);

    $this->resourceConfigStorage->load(static::$resourceConfigId)->disable()->save();
    $this->refreshTestStateAfterRestConfigChange();

    // DX: upon disabling a resource, it's immediately no longer available.
    $this->assertResourceNotAvailable($url, $request_options);

    $this->resourceConfigStorage->load(static::$resourceConfigId)->enable()->save();
    $this->refreshTestStateAfterRestConfigChange();

    // DX: upon re-enabling a resource, immediate 200.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), static::$auth ? FALSE : 'MISS', $is_cacheable_by_dynamic_page_cache ? 'MISS' : 'UNCACHEABLE');

    $this->resourceConfigStorage->load(static::$resourceConfigId)->delete();
    $this->refreshTestStateAfterRestConfigChange();

    // DX: upon deleting a resource, it's immediately no longer available.
    $this->assertResourceNotAvailable($url, $request_options);

    $this->provisionEntityResource();
    $url->setOption('query', ['_format' => 'non_existing_format']);

    // DX: 406 when requesting unsupported format.
    $response = $this->request('GET', $url, $request_options);
    $this->assert406Response($response);
    $this->assertSame(['text/plain; charset=UTF-8'], $response->getHeader('Content-Type'));

    $request_options[RequestOptions::HEADERS]['Accept'] = static::$mimeType;

    // DX: 406 when requesting unsupported format but specifying Accept header:
    // should result in a text/plain response.
    $response = $this->request('GET', $url, $request_options);
    $this->assert406Response($response);
    $this->assertSame(['text/plain; charset=UTF-8'], $response->getHeader('Content-Type'));

    $url = Url::fromRoute('rest.entity.' . static::$entityTypeId . '.GET');
    $url->setRouteParameter(static::$entityTypeId, 987654321);
    $url->setOption('query', ['_format' => static::$format]);

    // DX: 404 when GETting non-existing entity.
    $response = $this->request('GET', $url, $request_options);
    $path = str_replace('987654321', '{' . static::$entityTypeId . '}', $url->setAbsolute()->setOptions(['base_url' => '', 'query' => []])->toString());
    $message = 'The "' . static::$entityTypeId . '" parameter was not converted for the path "' . $path . '" (route name: "rest.entity.' . static::$entityTypeId . '.GET")';
    $this->assertResourceErrorResponse(404, $message, $response);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {
    // \Drupal\serialization\Normalizer\EntityNormalizer::denormalize(): entity
    // types with bundles MUST send their bundle field to be denormalizable.
    $entity_type = $this->entity->getEntityType();
    if ($entity_type->hasKey('bundle')) {
      $bundle_field_name = $this->entity->getEntityType()->getKey('bundle');
      $normalization = $this->getNormalizedPostEntity();

      // The bundle type itself can be validated only if there's a bundle entity
      // type.
      if ($entity_type->getBundleEntityType()) {
        $normalization[$bundle_field_name] = 'bad_bundle_name';
        $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

        // DX: 422 when incorrect entity type bundle is specified.
        $response = $this->request($method, $url, $request_options);
        $this->assertResourceErrorResponse(422, '"bad_bundle_name" is not a valid bundle type for denormalization.', $response);
      }

      unset($normalization[$bundle_field_name]);
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

      // DX: 422 when no entity type bundle is specified.
      $response = $this->request($method, $url, $request_options);
      $this->assertResourceErrorResponse(422, sprintf('Could not determine entity type bundle: "%s" field is missing.', $bundle_field_name), $response);
    }
  }

  /**
   * Gets an entity resource's GET/PATCH/DELETE URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to GET/PATCH/DELETE.
   */
  protected function getEntityResourceUrl() {
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');
    // Note that the 'canonical' link relation type must be specified explicitly
    // in the call to ::toUrl(). 'canonical' is the default for
    // \Drupal\Core\Entity\Entity::toUrl(), but ConfigEntityBase overrides this.
    return $has_canonical_url ? $this->entity->toUrl('canonical') : Url::fromUri('base:entity/' . static::$entityTypeId . '/' . $this->entity->id());
  }

  /**
   * Asserts a 406 response… or in some cases a 403 response, because weirdness.
   *
   * Asserting a 406 response should be easy, but it's not, due to bugs.
   *
   * Drupal returns a 403 response instead of a 406 response when:
   * - there is a canonical route, i.e. one that serves HTML
   * - unless the user is logged in with any non-global authentication provider,
   *   because then they tried to access a route that requires the user to be
   *   authenticated, but they used an authentication provider that is only
   *   accepted for specific routes, and HTML routes never have such specific
   *   authentication providers specified. (By default, only 'cookie' is a
   *   global authentication provider.)
   *
   * @todo Remove this in https://www.drupal.org/node/2805279.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to assert.
   */
  protected function assert406Response(ResponseInterface $response) {
    if ($this->entity->hasLinkTemplate('canonical') && ($this->account && static::$auth !== 'cookie')) {
      $this->assertSame(403, $response->getStatusCode());
    }
    else {
      // This is the desired response.
      $this->assertSame(406, $response->getStatusCode());
      $actual_link_header = $response->getHeader('Link');
      if ($actual_link_header) {
        $this->assertIsArray($actual_link_header);
        $expected_type = explode(';', static::$mimeType)[0];
        $this->assertStringContainsString('?_format=' . static::$format . '>; rel="alternate"; type="' . $expected_type . '"', $actual_link_header[0]);
        $this->assertStringContainsString('?_format=foobar>; rel="alternate"', $actual_link_header[0]);
      }
    }
  }

  /**
   * Asserts that a resource is unavailable: 404, 406 if it has canonical route.
   *
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   */
  protected function assertResourceNotAvailable(Url $url, array $request_options) {
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');
    $response = $this->request('GET', $url, $request_options);
    if (!$has_canonical_url) {
      $this->assertSame(404, $response->getStatusCode());
    }
    else {
      $this->assert406Response($response);
    }
  }

}
