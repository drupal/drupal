<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
 *    that specifies the exact @code $format @endcode, @code $mimeType @endcode,
 *    @code $expectedErrorMimeType @endcode and @code $auth @endcode for this
 *    concrete test. Usually that's all that's necessary: most concrete
 *    subclasses will be very thin.
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
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
   * {@inheritdoc}
   */
  protected function provisionEntityResource() {
    // It's possible to not have any authentication providers enabled, when
    // testing public (anonymous) usage of a REST resource.
    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource('entity.' . static::$entityTypeId, [static::$format], $auth);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->serializer = $this->container->get('serializer');
    $this->entityStorage = $this->container->get('entity_type.manager')
      ->getStorage(static::$entityTypeId);

    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);

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
      // @todo Remove in this if-test in https://www.drupal.org/node/2808335.
      if ($this->entity instanceof EntityChangedInterface) {
        $changed = $this->entity->getChangedTime();
        $this->entity->setChangedTime(42);
        $this->entity->save();
        $this->entity->setChangedTime($changed);
      }
      $this->entity->save();
    }

    // @todo Remove this in https://www.drupal.org/node/2815845.
    drupal_flush_all_caches();
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
    $url = $this->getUrl();
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
      $this->assertResourceErrorResponse(403, '', $response);
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "GET ' . str_replace($this->baseUrl, '', $this->getUrl()->setAbsolute()->toString()) . '"', $response);
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

    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('GET'));


    // DX: 403 when unauthorized.
    $response = $this->request('GET', $url, $request_options);
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


    $this->setUpAuthorization('GET');


    // 200 for well-formed HEAD request.
    $response = $this->request('HEAD', $url, $request_options);
    $this->assertResourceResponse(200, '', $response);
    if (!$this->account) {
      $this->assertSame(['MISS'], $response->getHeader('X-Drupal-Cache'));
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    }
    $head_headers = $response->getHeaders();

    // 200 for well-formed GET request. Page Cache hit because of HEAD request.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    if (!static::$auth) {
      $this->assertSame(['HIT'], $response->getHeader('X-Drupal-Cache'));
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    }
    $cache_tags_header_value = $response->getHeader('X-Drupal-Cache-Tags')[0];
    $this->assertEquals($this->getExpectedCacheTags(), empty($cache_tags_header_value) ? [] : explode(' ', $cache_tags_header_value));
    $cache_contexts_header_value = $response->getHeader('X-Drupal-Cache-Contexts')[0];
    $this->assertEquals($this->getExpectedCacheContexts(), empty($cache_contexts_header_value) ? [] : explode(' ', $cache_contexts_header_value));
    // Comparing the exact serialization is pointless, because the order of
    // fields does not matter (at least not yet). That's why we only compare the
    // normalized entity with the decoded response: it's comparing PHP arrays
    // instead of strings.
    $this->assertEquals($this->getExpectedNormalizedEntity(), $this->serializer->decode((string) $response->getBody(), static::$format));
    // Not only assert the normalization, also assert deserialization of the
    // response results in the expected object.
    $unserialized = $this->serializer->deserialize((string) $response->getBody(), get_class($this->entity), static::$format);
    $this->assertSame($unserialized->uuid(), $this->entity->uuid());
    $get_headers = $response->getHeaders();

    // Verify that the GET and HEAD responses are the same. The only difference
    // is that there's no body.
    $ignored_headers = ['Date', 'Content-Length', 'X-Drupal-Cache', 'X-Drupal-Dynamic-Cache'];
    foreach ($ignored_headers as $ignored_header) {
      unset($head_headers[$ignored_header]);
      unset($get_headers[$ignored_header]);
    }
    $this->assertSame($get_headers, $head_headers);


    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    // @todo Remove this in https://www.drupal.org/node/2815845.
    drupal_flush_all_caches();


    // DX: 403 when unauthorized.
    $response = $this->request('GET', $url, $request_options);
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


    $this->grantPermissionsToTestedRole(['restful get entity:' . static::$entityTypeId]);


    // 200 for well-formed request.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);


    $url->setOption('query', ['_format' => 'non_existing_format']);


    // DX: 406 when requesting unsupported format.
    $response = $this->request('GET', $url, $request_options);
    $this->assert406Response($response);
    $this->assertNotSame([static::$expectedErrorMimeType], $response->getHeader('Content-Type'));


    $request_options[RequestOptions::HEADERS]['Accept'] = static::$mimeType;


    // DX: 406 when requesting unsupported format but specifying Accept header.
    // @todo Update in https://www.drupal.org/node/2825347.
    $response = $this->request('GET', $url, $request_options);
    $this->assert406Response($response);
    $this->assertSame([static::$expectedErrorMimeType], $response->getHeader('Content-Type'));


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
    // @todo Change to ['uuid' => UUID] in https://www.drupal.org/node/2820743.
    $parseable_invalid_request_body_2 = $this->serializer->encode($this->getNormalizedPostEntity() + ['uuid' => [['value' => $this->randomMachineName(129)]]], static::$format);
    $parseable_invalid_request_body_3 = $this->serializer->encode($this->getNormalizedPostEntity() + ['field_rest_test' => [['value' => $this->randomString()]]], static::$format);

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = $this->getPostUrl();
    $request_options = [];


    // DX: 404 when resource not provisioned, but HTML if canonical route.
    $response = $this->request('POST', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(404, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "GET ' . str_replace($this->baseUrl, '', $this->getUrl()->setAbsolute()->toString()) . '"', $response);
    }


    $url->setOption('query', ['_format' => static::$format]);


    // DX: 404 when resource not provisioned.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(404, 'No route found for "POST ' . str_replace($this->baseUrl, '', $this->getPostUrl()->setAbsolute()->toString()) . '"', $response);


    $this->provisionEntityResource();
    // Simulate the developer again forgetting the ?_format query string.
    $url->setOption('query', []);


    // DX: 415 when no Content-Type request header, but HTML if canonical route.
    $response = $this->request('POST', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(415, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
      $this->assertContains(htmlspecialchars('No "Content-Type" request header specified'), (string) $response->getBody());
    }
    else {
      $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);
    }


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
    // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813853.
    // $this->assertResourceErrorResponse(400, 'Syntax error', $response);
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['error' => 'Syntax error'], static::$format), (string) $response->getBody());



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
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


    $this->setUpAuthorization('POST');


    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('POST', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = ucfirst($label_field);
    // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813755.
    // $this->assertErrorResponse(422, "Unprocessable Entity: validation failed.\ntitle: <em class=\"placeholder\">Title</em>: this field cannot hold more than 1 values.\n", $response);
    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['message' => "Unprocessable Entity: validation failed.\n$label_field: <em class=\"placeholder\">$label_field_capitalized</em>: this field cannot hold more than 1 values.\n"], static::$format), (string) $response->getBody());


    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;


    // DX: 422 when invalid entity: UUID field too long.
    $response = $this->request('POST', $url, $request_options);
    // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813755.
    // $this->assertErrorResponse(422, "Unprocessable Entity: validation failed.\nuuid.0.value: <em class=\"placeholder\">UUID</em>: may not be longer than 128 characters.\n", $response);
    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['message' => "Unprocessable Entity: validation failed.\nuuid.0.value: <em class=\"placeholder\">UUID</em>: may not be longer than 128 characters.\n"], static::$format), (string) $response->getBody());


    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;


    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('POST', $url, $request_options);
    // @todo Add trailing period in https://www.drupal.org/node/2821013.
    $this->assertResourceErrorResponse(403, "Access denied on creating field 'field_rest_test'", $response);


    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;


    // Before sending a well-formed request, allow the normalization and
    // authentication provider edge cases to also be tested.
    $this->assertNormalizationEdgeCases('POST', $url, $request_options);
    $this->assertAuthenticationEdgeCases('POST', $url, $request_options);


    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';


    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('POST', $url, $request_options);
    // @todo Update this in https://www.drupal.org/node/2826407. Also move it
    // higher, before the "no request body" test. That's impossible right now,
    // because the format validation happens too late.
    $this->assertResourceErrorResponse(415, '', $response);


    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;


    // 201 for well-formed request.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertSame([str_replace($this->entity->id(), static::$firstCreatedEntityId, $this->entity->toUrl('canonical')->setAbsolute(TRUE)->toString())], $response->getHeader('Location'));
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));


    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    $request_options[RequestOptions::BODY] = $parseable_valid_request_body_2;
    // @todo Remove this in https://www.drupal.org/node/2815845.
    drupal_flush_all_caches();


    // DX: 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


    $this->grantPermissionsToTestedRole(['restful post entity:' . static::$entityTypeId]);


    // 201 for well-formed request.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertSame([str_replace($this->entity->id(), static::$secondCreatedEntityId, $this->entity->toUrl('canonical')->setAbsolute(TRUE)->toString())], $response->getHeader('Location'));
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
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
    $url = $this->getUrl();
    $request_options = [];


    // DX: 405 when resource not provisioned, but HTML if canonical route.
    $response = $this->request('PATCH', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(405, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "PATCH ' . str_replace($this->baseUrl, '', $this->getUrl()->setAbsolute()->toString()) . '"', $response);
    }


    $url->setOption('query', ['_format' => static::$format]);


    // DX: 405 when resource not provisioned.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(405, 'No route found for "PATCH ' . str_replace($this->baseUrl, '', $this->getUrl()->setAbsolute()->toString()) . '": Method Not Allowed (Allow: GET, POST, HEAD)', $response);


    $this->provisionEntityResource();
    // Simulate the developer again forgetting the ?_format query string.
    $url->setOption('query', []);


    // DX: 415 when no Content-Type request header, but HTML if canonical route.
    $response = $this->request('PATCH', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(415, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
      $this->assertTrue(FALSE !== strpos((string) $response->getBody(), htmlspecialchars('No "Content-Type" request header specified')));
    }
    else {
      $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);
    }


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
    // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813853.
    // $this->assertResourceErrorResponse(400, 'Syntax error', $response);
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['error' => 'Syntax error'], static::$format), (string) $response->getBody());



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
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


    $this->setUpAuthorization('PATCH');


    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('PATCH', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = ucfirst($label_field);
    // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813755.
    // $this->assertErrorResponse(422, "Unprocessable Entity: validation failed.\ntitle: <em class=\"placeholder\">Title</em>: this field cannot hold more than 1 values.\n", $response);
    // $this->assertSame(422, $response->getStatusCode());
    // $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
    $this->assertSame($this->serializer->encode(['message' => "Unprocessable Entity: validation failed.\n$label_field: <em class=\"placeholder\">$label_field_capitalized</em>: this field cannot hold more than 1 values.\n"], static::$format), (string) $response->getBody());


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
    // @todo Update this in https://www.drupal.org/node/2826407. Also move it
    // higher, before the "no request body" test. That's impossible right now,
    // because the format validation happens too late.
    $this->assertResourceErrorResponse(415, '', $response);


    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;


    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // Ensure that fields do not get deleted if they're not present in the PATCH
    // request. Test this using the configurable field that we added, but which
    // is not sent in the PATCH request.
    $this->assertSame('All the faith he had had had had no effect on the outcome of his life.', $this->entityStorage->loadUnchanged($this->entity->id())->get('field_rest_test')->value);


    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    $request_options[RequestOptions::BODY] = $parseable_valid_request_body_2;
    // @todo Remove this in https://www.drupal.org/node/2815845.
    drupal_flush_all_caches();


    // DX: 403 when unauthorized.
    $response = $this->request('PATCH', $url, $request_options);
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


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
    $url = $this->getUrl();
    $request_options = [];


    // DX: 405 when resource not provisioned, but HTML if canonical route.
    $response = $this->request('DELETE', $url, $request_options);
    if ($has_canonical_url) {
      $this->assertSame(405, $response->getStatusCode());
      $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    }
    else {
      $this->assertResourceErrorResponse(404, 'No route found for "DELETE ' . str_replace($this->baseUrl, '', $this->getUrl()->setAbsolute()->toString()) . '"', $response);
    }


    $url->setOption('query', ['_format' => static::$format]);


    // DX: 405 when resource not provisioned.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(405, 'No route found for "DELETE ' . str_replace($this->baseUrl, '', $this->getUrl()->setAbsolute()->toString()) . '": Method Not Allowed (Allow: GET, POST, HEAD)', $response);


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
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


    $this->setUpAuthorization('DELETE');


    // Before sending a well-formed request, allow the authentication provider's
    // edge cases to also be tested.
    $this->assertAuthenticationEdgeCases('DELETE', $url, $request_options);


    // 204 for well-formed request.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertSame(204, $response->getStatusCode());
    // @todo Uncomment the following line when https://www.drupal.org/node/2821711 is fixed.
    // $this->assertSame(FALSE, $response->hasHeader('Content-Type'));
    $this->assertSame('', (string) $response->getBody());
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));


    $this->config('rest.settings')->set('bc_entity_resource_permissions', TRUE)->save(TRUE);
    // @todo Remove this in https://www.drupal.org/node/2815845.
    drupal_flush_all_caches();
    $this->entity = $this->createEntity();
    $url = $this->getUrl()->setOption('query', $url->getOption('query'));


    // DX: 403 when unauthorized.
    $response = $this->request('DELETE', $url, $request_options);
    // @todo Update the message in https://www.drupal.org/node/2808233.
    $this->assertResourceErrorResponse(403, '', $response);


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


        // DX: 400 when incorrect entity type bundle is specified.
        // @todo Change to 422 in https://www.drupal.org/node/2827084.
        $response = $this->request($method, $url, $request_options);
        // @todo use this commented line instead of the 3 lines thereafter once https://www.drupal.org/node/2813853 lands.
        //      $this->assertResourceErrorResponse(400, '"bad_bundle_name" is not a valid bundle type for denormalization.', $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
        $this->assertSame($this->serializer->encode(['error' => '"bad_bundle_name" is not a valid bundle type for denormalization.'], static::$format), (string) $response->getBody());
      }


      unset($normalization[$bundle_field_name]);
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


      // DX: 400 when no entity type bundle is specified.
      // @todo Change to 422 in https://www.drupal.org/node/2827084.
      $response = $this->request($method, $url, $request_options);
      // @todo use this commented line instead of the 3 lines thereafter once https://www.drupal.org/node/2813853 lands.
      // $this->assertResourceErrorResponse(400, 'A string must be provided as a bundle value.', $response);
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
      $this->assertSame($this->serializer->encode(['error' => 'A string must be provided as a bundle value.'], static::$format), (string) $response->getBody());
    }
  }

  /**
   * Gets an entity resource's GET/PATCH/DELETE URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to GET/PATCH/DELETE.
   */
  protected function getUrl() {
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');
    return $has_canonical_url ? $this->entity->toUrl() : Url::fromUri('base:entity/' . static::$entityTypeId . '/' . $this->entity->id());
  }

  /**
   * Gets an entity resource's POST URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to POST to.
   */
  protected function getPostUrl() {
    $has_canonical_url = $this->entity->hasLinkTemplate('https://www.drupal.org/link-relations/create');
    return $has_canonical_url ? $this->entity->toUrl() : Url::fromUri('base:entity/' . static::$entityTypeId);
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

}
