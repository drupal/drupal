<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
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
 * For every of these concrete subclasses, a comprehensive test scenario will
 * run per HTTP method:
 * - ::testGet()
 * - ::testPost()
 * - ::testPatch()
 * - ::testDelete()
 *
 * If there is an entity type-specific edge case scenario to test, then add that
 * to the entity type-specific abstract subclass. Example:
 * \Drupal\Tests\rest\Functional\EntityResource\Comment\CommentResourceTestBase::testPostDxWithoutCriticalBaseFields
 *
 * If there is an entity type-specific format-specific edge case to test, then
 * add that to a concrete subclass. Example:
 * \Drupal\Tests\hal\Functional\EntityResource\Comment\CommentHalJsonTestBase::$patchProtectedFieldNames
 */
abstract class EntityResourceTestBase extends ResourceTestBase {

  /**
   * The tested entity type.
   *
   * @var string
   */
  protected static $entityTypeId = NULL;

  /**
   * The fields that are protected against modification during PATCH requests.
   *
   * @var string[]
   */
  protected static $patchProtectedFieldNames;

  /**
   * Optionally specify which field is the 'label' field. Some entities specify
   * a 'label_callback', but not a 'label' entity key. For example: User.
   *
   * @see ::getInvalidNormalizedEntityToCreate
   *
   * @var string|null
   */
  protected static $labelFieldName = NULL;

  /**
   * The entity ID for the first created entity in testPost().
   *
   * The default value of 2 should work for most content entities.
   *
   * @see ::testPost()
   *
   * @var string|int
   */
  protected static $firstCreatedEntityId = 2;

  /**
   * The entity ID for the second created entity in testPost().
   *
   * The default value of 3 should work for most content entities.
   *
   * @see ::testPost()
   *
   * @var string|int
   */
  protected static $secondCreatedEntityId = 3;

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
  public static $modules = ['rest_test', 'text'];

  /**
   * Provides an entity resource.
   */
  protected function provisionEntityResource() {
    // It's possible to not have any authentication providers enabled, when
    // testing public (anonymous) usage of a REST resource.
    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth);
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

      // Reload entity so that it has the new field.
      $this->entity = $this->entityStorage->loadUnchanged($this->entity->id());

      // Set a default value on the field.
      $this->entity->set('field_rest_test', ['value' => 'All the faith he had had had had no effect on the outcome of his life.']);
      $this->entity->save();
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
   * Returns the normalized POST entity.
   *
   * @see ::testPost
   *
   * @return array
   */
  abstract protected function getNormalizedPostEntity();

  /**
   * Returns the normalized PATCH entity.
   *
   * By default, reuses ::getNormalizedPostEntity(), which works fine for most
   * entity types. A counterexample: the 'comment' entity type.
   *
   * @see ::testPatch
   *
   * @return array
   */
  protected function getNormalizedPatchEntity() {
    return $this->getNormalizedPostEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {

    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

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
      $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('GET'), $response);
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "GET ' . str_replace($this->baseUrl, '', $this->getEntityResourceUrl()->setAbsolute()->toString()) . '"', $response);
    }

    $this->provisionEntityResource();
    // Simulate the developer again forgetting the ?_format query string.
    $url->setOption('query', []);

    // DX: 406 when ?_format is missing, except when requesting a canonical HTML
    // route.
    $response = $this->request('GET', $url, $request_options);
    if ($has_canonical_url && (!static::$auth || static::$auth === 'cookie')) {
      $this->assertSame(403, $response->getStatusCode());
    }
    else {
      $this->assert406Response($response);
    }

    $url->setOption('query', ['_format' => static::$format]);

    // DX: forgetting authentication: authentication provider-specific error
    // response.
    if (static::$auth) {
      $response = $this->request('GET', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication($response);
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

    // DX: 403 when unauthorized.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('GET'), $response);
    $this->assertArrayNotHasKey('Link', $response->getHeaders());

    $this->setUpAuthorization('GET');

    // 200 for well-formed HEAD request.
    $response = $this->request('HEAD', $url, $request_options);
    $this->assertResourceResponse(200, '', $response);
    // @todo Entity resources with URLs that begin with '/admin/' are marked as
    //   administrative (see https://www.drupal.org/node/2874938), which
    //   excludes them from Dynamic Page Cache (see
    //   https://www.drupal.org/node/2877528). When either of those issues is
    //   fixed, remove the if-test and the 'else' block.
    if (strpos($this->entity->getEntityType()->getLinkTemplate('canonical'), '/admin/') !== 0) {
      $this->assertTrue($response->hasHeader('X-Drupal-Dynamic-Cache'));
      $this->assertSame(['MISS'], $response->getHeader('X-Drupal-Dynamic-Cache'));
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Dynamic-Cache'));
    }
    if (!$this->account) {
      $this->assertSame(['MISS'], $response->getHeader('X-Drupal-Cache'));
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    }
    $head_headers = $response->getHeaders();

    // 200 for well-formed GET request. Page Cache hit because of HEAD request.
    // Same for Dynamic Page Cache hit.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    // @todo Entity resources with URLs that begin with '/admin/' are marked as
    //   administrative (see https://www.drupal.org/node/2874938), which
    //   excludes them from Dynamic Page Cache (see
    //   https://www.drupal.org/node/2877528). When either of those issues is
    //   fixed, remove the if-test and the 'else' block.
    if (strpos($this->entity->getEntityType()->getLinkTemplate('canonical'), '/admin/') !== 0) {
      $this->assertTrue($response->hasHeader('X-Drupal-Dynamic-Cache'));
      if (!static::$auth) {
        $this->assertSame(['HIT'], $response->getHeader('X-Drupal-Cache'));
        $this->assertSame(['MISS'], $response->getHeader('X-Drupal-Dynamic-Cache'));
      }
      else {
        $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
        $this->assertSame(['HIT'], $response->getHeader('X-Drupal-Dynamic-Cache'));
        // Assert that Dynamic Page Cache did not store a ResourceResponse object,
        // which needs serialization after every cache hit. Instead, it should
        // contain a flattened response. Otherwise performance suffers.
        // @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::flattenResponse()
        $cache_items = $this->container->get('database')
          ->query("SELECT cid, data FROM {cache_dynamic_page_cache} WHERE cid LIKE :pattern", [
            ':pattern' => '%[route]=rest.%',
          ])
          ->fetchAllAssoc('cid');
        $this->assertCount(2, $cache_items);
        $found_cache_redirect = FALSE;
        $found_cached_response = FALSE;
        foreach ($cache_items as $cid => $cache_item) {
          $cached_data = unserialize($cache_item->data);
          if (!isset($cached_data['#cache_redirect'])) {
            $found_cached_response = TRUE;
            $cached_response = $cached_data['#response'];
            $this->assertNotInstanceOf(ResourceResponseInterface::class, $cached_response);
            $this->assertInstanceOf(CacheableResponseInterface::class, $cached_response);
          }
          else {
            $found_cache_redirect = TRUE;
          }
        }
        $this->assertTrue($found_cache_redirect);
        $this->assertTrue($found_cached_response);
      }
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Dynamic-Cache'));
    }
    $cache_tags_header_value = $response->getHeader('X-Drupal-Cache-Tags')[0];
    $this->assertEquals($this->getExpectedCacheTags(), empty($cache_tags_header_value) ? [] : explode(' ', $cache_tags_header_value));
    $cache_contexts_header_value = $response->getHeader('X-Drupal-Cache-Contexts')[0];
    $this->assertEquals($this->getExpectedCacheContexts(), empty($cache_contexts_header_value) ? [] : explode(' ', $cache_contexts_header_value));
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
    $unserialized = $this->serializer->deserialize((string) $response->getBody(), get_class($this->entity), static::$format);
    $this->assertSame($unserialized->uuid(), $this->entity->uuid());
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
    // is that there's no body. For this reason the 'Transfer-Encoding' header
    // is also added to the list of headers to ignore, as this could be added to
    // GET requests - depending on web server configuration. This would usually
    // be 'Transfer-Encoding: chunked'.
    $ignored_headers = ['Date', 'Content-Length', 'X-Drupal-Cache', 'X-Drupal-Dynamic-Cache', 'Transfer-Encoding'];
    foreach ($ignored_headers as $ignored_header) {
      unset($head_headers[$ignored_header]);
      unset($get_headers[$ignored_header]);
    }
    $this->assertSame($get_headers, $head_headers);

    // BC: serialization_update_8302().
    // Only run this for fieldable entities. It doesn't make sense for config
    // entities as config values are already casted. They also run through the
    // ConfigEntityNormalizer, which doesn't deal with fields individually.
    if ($this->entity instanceof FieldableEntityInterface) {
      // Test primitive data casting BC (strings).
      $this->config('serialization.settings')->set('bc_primitives_as_strings', TRUE)->save(TRUE);
      // Rebuild the container so new config is reflected in the addition of the
      // PrimitiveDataNormalizer.
      $this->rebuildAll();

      $response = $this->request('GET', $url, $request_options);
      $this->assertResourceResponse(200, FALSE, $response);

      // Again do an identical comparison, but this time transform the expected
      // normalized entity's values to strings. This ensures the BC layer for
      // bc_primitives_as_strings works as expected.
      $expected = $this->getExpectedNormalizedEntity();
      // Config entities are not affected.
      // @see \Drupal\serialization\Normalizer\ConfigEntityNormalizer::normalize()
      $expected = static::castToString($expected);
      static::recursiveKSort($expected);
      $actual = $this->serializer->decode((string) $response->getBody(), static::$format);
      static::recursiveKSort($actual);
      $this->assertSame($expected, $actual);

      // Reset the config value and rebuild.
      $this->config('serialization.settings')->set('bc_primitives_as_strings', FALSE)->save(TRUE);
      $this->rebuildAll();
    }

    // BC: serialization_update_8401().
    // Only run this for fieldable entities. It doesn't make sense for config
    // entities as config values always use the raw values (as per the config
    // schema), returned directly from the ConfigEntityNormalizer, which
    // doesn't deal with fields individually.
    if ($this->entity instanceof FieldableEntityInterface) {
      // Test the BC settings for timestamp values.
      $this->config('serialization.settings')->set('bc_timestamp_normalizer_unix', TRUE)->save(TRUE);
      // Rebuild the container so new config is reflected in the addition of the
      // TimestampItemNormalizer.
      $this->rebuildAll();

      $response = $this->request('GET', $url, $request_options);
      $this->assertResourceResponse(200, FALSE, $response);

      // This ensures the BC layer for bc_timestamp_normalizer_unix works as
      // expected. This method should be using
      // ::formatExpectedTimestampValue() to generate the timestamp value. This
      // will take into account the above config setting.
      $expected = $this->getExpectedNormalizedEntity();
      // Config entities are not affected.
      // @see \Drupal\serialization\Normalizer\ConfigEntityNormalizer::normalize()
      static::recursiveKSort($expected);
      $actual = $this->serializer->decode((string) $response->getBody(), static::$format);
      static::recursiveKSort($actual);
      $this->assertSame($expected, $actual);

      // Reset the config value and rebuild.
      $this->config('serialization.settings')->set('bc_timestamp_normalizer_unix', FALSE)->save(TRUE);
      $this->rebuildAll();
    }

    // BC: rest_update_8203().
    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    $this->refreshTestStateAfterRestConfigChange();

    // DX: 403 when unauthorized.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('GET'), $response);

    $this->grantPermissionsToTestedRole(['restful get entity:' . static::$entityTypeId]);

    // 200 for well-formed request.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    $this->resourceConfigStorage->load(static::$resourceConfigId)->disable()->save();
    $this->refreshTestStateAfterRestConfigChange();

    // DX: upon disabling a resource, it's immediately no longer available.
    $this->assertResourceNotAvailable($url, $request_options);

    $this->resourceConfigStorage->load(static::$resourceConfigId)->enable()->save();
    $this->refreshTestStateAfterRestConfigChange();

    // DX: upon re-enabling a resource, immediate 200.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

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

    $url = Url::fromRoute('rest.entity.' . static::$entityTypeId . '.GET.' . static::$format);
    $url->setRouteParameter(static::$entityTypeId, 987654321);
    $url->setOption('query', ['_format' => static::$format]);

    // DX: 404 when GETting non-existing entity.
    $response = $this->request('GET', $url, $request_options);
    $path = str_replace('987654321', '{' . static::$entityTypeId . '}', $url->setAbsolute()->setOptions(['base_url' => '', 'query' => []])->toString());
    $message = 'The "' . static::$entityTypeId . '" parameter was not converted for the path "' . $path . '" (route name: "rest.entity.' . static::$entityTypeId . '.GET.' . static::$format . '")';
    $this->assertResourceErrorResponse(404, $message, $response);
  }

  /**
   * Transforms a normalization: casts all non-string types to strings.
   *
   * @param array $normalization
   *   A normalization to transform.
   *
   * @return array
   *   The transformed normalization.
   */
  protected static function castToString(array $normalization) {
    foreach ($normalization as $key => $value) {
      if (is_bool($value)) {
        $normalization[$key] = (string) (int) $value;
      }
      elseif (is_int($value) || is_float($value)) {
        $normalization[$key] = (string) $value;
      }
      elseif (is_array($value)) {
        $normalization[$key] = static::castToString($value);
      }
    }
    return $normalization;
  }

  /**
   * Recursively sorts an array by key.
   *
   * @param array $array
   *   An array to sort.
   *
   * @return array
   *   The sorted array.
   */
  protected static function recursiveKSort(array &$array) {
    // First, sort the main array.
    ksort($array);

    // Then check for child arrays.
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        static::recursiveKSort($value);
      }
    }
  }

  /**
   * Tests a POST request for an entity, plus edge cases to ensure good DX.
   */
  public function testPost() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'POSTing config entities is not yet supported.');
      return;
    }

    $this->initAuthentication();
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body   = $this->serializer->encode($this->getNormalizedPostEntity(), static::$format);
    $parseable_valid_request_body_2 = $this->serializer->encode($this->getNormalizedPostEntity(), static::$format);
    $parseable_invalid_request_body   = $this->serializer->encode($this->makeNormalizationInvalid($this->getNormalizedPostEntity()), static::$format);
    $parseable_invalid_request_body_2 = $this->serializer->encode($this->getNormalizedPostEntity() + ['uuid' => [$this->randomMachineName(129)]], static::$format);
    $parseable_invalid_request_body_3 = $this->serializer->encode($this->getNormalizedPostEntity() + ['field_rest_test' => [['value' => $this->randomString()]]], static::$format);

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = $this->getEntityResourcePostUrl();
    $request_options = [];

    // DX: 404 when resource not provisioned. HTML response because missing
    // ?_format query string.
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 404 when resource not provisioned.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(404, 'No route found for "POST ' . str_replace($this->baseUrl, '', $this->getEntityResourcePostUrl()->setAbsolute()->toString()) . '"', $response);

    $this->provisionEntityResource();
    // Simulate the developer again forgetting the ?_format query string.
    $url->setOption('query', []);

    // DX: 415 when no Content-Type request header. HTML response because
    // missing ?_format query string.
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    $this->assertContains('A client error happened', (string) $response->getBody());

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // DX: 400 when no request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No entity content received.', $response);

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Syntax error', $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    if (static::$auth) {
      // DX: forgetting authentication: authentication provider-specific error
      // response.
      $response = $this->request('POST', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication($response);
    }

    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('POST'));

    // DX: 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('POST'), $response);

    $this->setUpAuthorization('POST');

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('POST', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
    $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\n$label_field: $label_field_capitalized: this field cannot hold more than 1 values.\n", $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // DX: 422 when invalid entity: UUID field too long.
    // @todo Fix this in https://www.drupal.org/node/2149851.
    if ($this->entity->getEntityType()->hasKey('uuid')) {
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nuuid.0.value: UUID: may not be longer than 128 characters.\n", $response);
    }

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;

    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(403, "Access denied on creating field 'field_rest_test'.", $response);

    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;

    // Before sending a well-formed request, allow the normalization and
    // authentication provider edge cases to also be tested.
    $this->assertNormalizationEdgeCases('POST', $url, $request_options);
    $this->assertAuthenticationEdgeCases('POST', $url, $request_options);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';

    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No route found that matches "Content-Type: text/xml"', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // 201 for well-formed request.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    if ($has_canonical_url) {
      $location = $this->entityStorage->load(static::$firstCreatedEntityId)->toUrl('canonical')->setAbsolute(TRUE)->toString();
      $this->assertSame([$location], $response->getHeader('Location'));
    }
    else {
      $this->assertSame([], $response->getHeader('Location'));
    }
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // Assert that the entity was indeed created, and that the response body
    // contains the serialized created entity.
    $created_entity = $this->entityStorage->loadUnchanged(static::$firstCreatedEntityId);
    $created_entity_normalization = $this->serializer->normalize($created_entity, static::$format, ['account' => $this->account]);
    // @todo Remove this if-test in https://www.drupal.org/node/2543726: execute
    // its body unconditionally.
    if (static::$entityTypeId !== 'taxonomy_term') {
      $this->assertSame($created_entity_normalization, $this->serializer->decode((string) $response->getBody(), static::$format));
    }
    // Assert that the entity was indeed created using the POSTed values.
    foreach ($this->getNormalizedPostEntity() as $field_name => $field_normalization) {
      // Some top-level keys in the normalization may not be fields on the
      // entity (for example '_links' and '_embedded' in the HAL normalization).
      if ($created_entity->hasField($field_name)) {
        // Subset, not same, because we can e.g. send just the target_id for the
        // bundle in a POST request; the response will include more properties.
        $this->assertArraySubset(static::castToString($field_normalization), $created_entity->get($field_name)->getValue(), TRUE);
      }
    }

    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    $this->refreshTestStateAfterRestConfigChange();
    $request_options[RequestOptions::BODY] = $parseable_valid_request_body_2;

    // DX: 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('POST'), $response);

    $this->grantPermissionsToTestedRole(['restful post entity:' . static::$entityTypeId]);

    // 201 for well-formed request.
    // Delete the first created entity in case there is a uniqueness constraint.
    $this->entityStorage->load(static::$firstCreatedEntityId)->delete();
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    if ($has_canonical_url) {
      $location = $this->entityStorage->load(static::$secondCreatedEntityId)->toUrl('canonical')->setAbsolute(TRUE)->toString();
      $this->assertSame([$location], $response->getHeader('Location'));
    }
    else {
      $this->assertSame([], $response->getHeader('Location'));
    }
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));

    // BC: old default POST URLs have their path updated by the inbound path
    // processor \Drupal\rest\PathProcessor\PathProcessorEntityResourceBC to the
    // new URL, which is derived from the 'create' link template if an entity
    // type specifies it.
    if ($this->entity->getEntityType()->hasLinkTemplate('create')) {
      $this->entityStorage->load(static::$secondCreatedEntityId)->delete();
      $old_url = Url::fromUri('base:entity/' . static::$entityTypeId);
      $response = $this->request('POST', $old_url, $request_options);
      $this->assertResourceResponse(201, FALSE, $response);
    }
  }

  /**
   * Tests a PATCH request for an entity, plus edge cases to ensure good DX.
   */
  public function testPatch() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'PATCHing config entities is not yet supported.');
      return;
    }

    $this->initAuthentication();
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body   = $this->serializer->encode($this->getNormalizedPatchEntity(), static::$format);
    $parseable_valid_request_body_2 = $this->serializer->encode($this->getNormalizedPatchEntity(), static::$format);
    $parseable_invalid_request_body   = $this->serializer->encode($this->makeNormalizationInvalid($this->getNormalizedPatchEntity()), static::$format);
    $parseable_invalid_request_body_2 = $this->serializer->encode($this->getNormalizedPatchEntity() + ['field_rest_test' => [['value' => $this->randomString()]]], static::$format);

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = $this->getEntityResourceUrl();
    $request_options = [];

    // DX: 404 when resource not provisioned, 405 if canonical route. Plain text
    // or HTML response because missing ?_format query string.
    $response = $this->request('PATCH', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(405, $response->getStatusCode());
      $this->assertSame(['GET, POST, HEAD'], $response->getHeader('Allow'));
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
      $this->assertContains('A client error happened', (string) $response->getBody());
    }
    else {
      $this->assertSame(404, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    }

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 404 when resource not provisioned, 405 if canonical route.
    $response = $this->request('PATCH', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertResourceErrorResponse(405, 'No route found for "PATCH ' . str_replace($this->baseUrl, '', $this->getEntityResourceUrl()->setAbsolute()->toString()) . '": Method Not Allowed (Allow: GET, POST, HEAD)', $response);
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "PATCH ' . str_replace($this->baseUrl, '', $this->getEntityResourceUrl()->setAbsolute()->toString()) . '"', $response);
    }

    $this->provisionEntityResource();
    // Simulate the developer again forgetting the ?_format query string.
    $url->setOption('query', []);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    $this->assertContains('A client error happened', (string) $response->getBody());

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // DX: 400 when no request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No entity content received.', $response);

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Syntax error', $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    if (static::$auth) {
      // DX: forgetting authentication: authentication provider-specific error
      // response.
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication($response);
    }

    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('PATCH'));

    // DX: 403 when unauthorized.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('PATCH'), $response);

    $this->setUpAuthorization('PATCH');

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('PATCH', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
    $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\n$label_field: $label_field_capitalized: this field cannot hold more than 1 values.\n", $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "Access denied on updating field 'field_rest_test'.", $response);

    // DX: 403 when sending PATCH request with read-only fields.
    // First send all fields (the "maximum normalization"). Assert the expected
    // error message for the first PATCH-protected field. Remove that field from
    // the normalization, send another request, assert the next PATCH-protected
    // field error message. And so on.
    $max_normalization = $this->getNormalizedPatchEntity() + $this->serializer->normalize($this->entity, static::$format);
    for ($i = 0; $i < count(static::$patchProtectedFieldNames); $i++) {
      $max_normalization = $this->removeFieldsFromNormalization($max_normalization, array_slice(static::$patchProtectedFieldNames, 0, $i));
      $request_options[RequestOptions::BODY] = $this->serializer->serialize($max_normalization, static::$format);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(403, "Access denied on updating field '" . static::$patchProtectedFieldNames[$i] . "'.", $response);
    }

    // 200 for well-formed request that sends the maximum number of fields.
    $max_normalization = $this->removeFieldsFromNormalization($max_normalization, static::$patchProtectedFieldNames);
    $request_options[RequestOptions::BODY] = $this->serializer->serialize($max_normalization, static::$format);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;

    // Before sending a well-formed request, allow the normalization and
    // authentication provider edge cases to also be tested.
    $this->assertNormalizationEdgeCases('PATCH', $url, $request_options);
    $this->assertAuthenticationEdgeCases('PATCH', $url, $request_options);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';

    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No route found that matches "Content-Type: text/xml"', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // Assert that the entity was indeed updated, and that the response body
    // contains the serialized updated entity.
    $updated_entity = $this->entityStorage->loadUnchanged($this->entity->id());
    $updated_entity_normalization = $this->serializer->normalize($updated_entity, static::$format, ['account' => $this->account]);
    $this->assertSame($updated_entity_normalization, $this->serializer->decode((string) $response->getBody(), static::$format));
    // Assert that the entity was indeed created using the PATCHed values.
    foreach ($this->getNormalizedPatchEntity() as $field_name => $field_normalization) {
      // Some top-level keys in the normalization may not be fields on the
      // entity (for example '_links' and '_embedded' in the HAL normalization).
      if ($updated_entity->hasField($field_name)) {
        // Subset, not same, because we can e.g. send just the target_id for the
        // bundle in a PATCH request; the response will include more properties.
        $this->assertArraySubset(static::castToString($field_normalization), $updated_entity->get($field_name)->getValue(), TRUE);
      }
    }
    // Ensure that fields do not get deleted if they're not present in the PATCH
    // request. Test this using the configurable field that we added, but which
    // is not sent in the PATCH request.
    $this->assertSame('All the faith he had had had had no effect on the outcome of his life.', $updated_entity->get('field_rest_test')->value);

    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    $this->refreshTestStateAfterRestConfigChange();
    $request_options[RequestOptions::BODY] = $parseable_valid_request_body_2;

    // DX: 403 when unauthorized.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('PATCH'), $response);

    $this->grantPermissionsToTestedRole(['restful patch entity:' . static::$entityTypeId]);

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
  }

  /**
   * Tests a DELETE request for an entity, plus edge cases to ensure good DX.
   */
  public function testDelete() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'DELETEing config entities is not yet supported.');
      return;
    }

    $this->initAuthentication();
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = $this->getEntityResourceUrl();
    $request_options = [];

    // DX: 404 when resource not provisioned, but 405 if canonical route. Plain
    // text  or HTML response because missing ?_format query string.
    $response = $this->request('DELETE', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(405, $response->getStatusCode());
      $this->assertSame(['GET, POST, HEAD'], $response->getHeader('Allow'));
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
      $this->assertContains('A client error happened', (string) $response->getBody());
    }
    else {
      $this->assertSame(404, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    }

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 404 when resource not provisioned, 405 if canonical route.
    $response = $this->request('DELETE', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(['GET, POST, HEAD'], $response->getHeader('Allow'));
      $this->assertResourceErrorResponse(405, 'No route found for "DELETE ' . str_replace($this->baseUrl, '', $this->getEntityResourceUrl()->setAbsolute()->toString()) . '": Method Not Allowed (Allow: GET, POST, HEAD)', $response);
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "DELETE ' . str_replace($this->baseUrl, '', $this->getEntityResourceUrl()->setAbsolute()->toString()) . '"', $response);
    }

    $this->provisionEntityResource();

    if (static::$auth) {
      // DX: forgetting authentication: authentication provider-specific error
      // response.
      $response = $this->request('DELETE', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication($response);
    }

    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('PATCH'));

    // DX: 403 when unauthorized.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('DELETE'), $response);

    $this->setUpAuthorization('DELETE');

    // Before sending a well-formed request, allow the authentication provider's
    // edge cases to also be tested.
    $this->assertAuthenticationEdgeCases('DELETE', $url, $request_options);

    // 204 for well-formed request.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertSame(204, $response->getStatusCode());
    // DELETE responses should not include a Content-Type header. But Apache
    // sets it to 'text/html' by default. We also cannot detect the presence of
    // Apache either here in the CLI. For now having this documented here is all
    // we can do.
    // $this->assertSame(FALSE, $response->hasHeader('Content-Type'));
    $this->assertSame('', (string) $response->getBody());
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));

    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    $this->refreshTestStateAfterRestConfigChange();
    $this->entity = $this->createEntity();
    $url = $this->getEntityResourceUrl()->setOption('query', $url->getOption('query'));

    // DX: 403 when unauthorized.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('DELETE'), $response);

    $this->grantPermissionsToTestedRole(['restful delete entity:' . static::$entityTypeId]);

    // 204 for well-formed request.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertSame(204, $response->getStatusCode());
    // @todo Uncomment the following line when https://www.drupal.org/node/2821711 is fixed.
    // $this->assertSame(FALSE, $response->hasHeader('Content-Type'));
    $this->assertSame('', (string) $response->getBody());
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
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
   * Gets an entity resource's POST URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to POST to.
   */
  protected function getEntityResourcePostUrl() {
    $has_create_url = $this->entity->hasLinkTemplate('create');
    return $has_create_url ? Url::fromUri('internal:' . $this->entity->getEntityType()->getLinkTemplate('create')) : Url::fromUri('base:entity/' . static::$entityTypeId);
  }

  /**
   * Makes the given entity normalization invalid.
   *
   * @param array $normalization
   *   An entity normalization.
   *
   * @return array
   *   The updated entity normalization, now invalid.
   */
  protected function makeNormalizationInvalid(array $normalization) {
    // Add a second label to this entity to make it invalid.
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $normalization[$label_field][1]['value'] = 'Second Title';

    return $normalization;
  }

  /**
   * Removes fields from a normalization.
   *
   * @param array $normalization
   *   An entity normalization.
   * @param string[] $field_names
   *   The field names to remove from the entity normalization.
   *
   * @return array
   *   The updated entity normalization.
   *
   * @see ::testPatch
   */
  protected function removeFieldsFromNormalization(array $normalization, $field_names) {
    return array_diff_key($normalization, array_flip($field_names));
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
