<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Url;
use Drupal\path\Plugin\Field\FieldType\PathItem;
use GuzzleHttp\RequestOptions;

/**
 * Extends EntityResourceTestBase to handle content entities.
 *
 * For each of the concrete subclasses, a comprehensive test scenario will
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
abstract class ContentEntityResourceTestBase extends EntityResourceTestBase {

  /**
   * The fields that are protected against modification during PATCH requests.
   *
   * Keys are field names, values are expected access denied reasons.
   *
   * @var string[]
   */
  protected static $patchProtectedFieldNames;

  /**
   * The fields that need a different (random) value for each new entity created
   * by a POST request.
   *
   * @var string[]
   */
  protected static $uniqueFieldNames = [];

  /**
   * Optionally specify which field is the 'label' field. Some entities do not
   * specify a 'label' entity key. For example: User.
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
   * Another entity of the same type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $anotherEntity;

  /**
   * Creates another entity to be tested.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Another entity based on $this->entity.
   */
  protected function createAnotherEntity() {
    $entity = $this->entity->createDuplicate();
    $label_key = $entity->getEntityType()->getKey('label');
    if ($label_key) {
      $entity->set($label_key, $entity->label() . '_dupe');
    }
    $entity->save();
    return $entity;
  }

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
   * Gets the normalized POST entity with random values for its unique fields.
   *
   * @see ::testPost
   * @see ::getNormalizedPostEntity
   *
   * @return array
   *   An array structure as returned by ::getNormalizedPostEntity().
   */
  protected function getModifiedEntityForPostTesting() {
    $normalized_entity = $this->getNormalizedPostEntity();

    // Ensure that all the unique fields of the entity type get a new random
    // value.
    foreach (static::$uniqueFieldNames as $field_name) {
      $field_definition = $this->entity->getFieldDefinition($field_name);
      $field_type_class = $field_definition->getItemDefinition()->getClass();
      $normalized_entity[$field_name] = $field_type_class::generateSampleValue($field_definition);
    }

    return $normalized_entity;
  }

  /**
   * Tests a POST request for an entity, plus edge cases to ensure good DX.
   */
  public function testPost() {
    $this->initAuthentication();
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body = $this->serializer->encode($this->getNormalizedPostEntity(), static::$format);
    $parseable_invalid_request_body = $this->serializer->encode($this->makeNormalizationInvalid($this->getNormalizedPostEntity(), 'label'), static::$format);
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
    $this->assertStringContainsString('A client error happened', (string) $response->getBody());

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    if (static::$auth) {
      // DX: forgetting authentication: authentication provider-specific error
      // response.
      $response = $this->request('POST', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication('POST', $response);
    }

    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('POST'));

    // DX: 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('POST'), $response);

    $this->setUpAuthorization('POST');

    // DX: 400 when no request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No entity content received.', $response);

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Syntax error', $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('POST', $url, $request_options);
    if ($label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName) {
      $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
      $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\n$label_field: $label_field_capitalized: this field cannot hold more than 1 values.\n", $response);
    }

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
    // If the entity is stored, perform extra checks.
    if (get_class($this->entityStorage) !== ContentEntityNullStorage::class) {
      // Assert that the entity was indeed created, and that the response body
      // contains the serialized created entity.
      $created_entity = $this->entityStorage->loadUnchanged(static::$firstCreatedEntityId);
      $created_entity_normalization = $this->serializer->normalize($created_entity, static::$format, ['account' => $this->account]);
      $this->assertSame($created_entity_normalization, $this->serializer->decode((string) $response->getBody(), static::$format));
      $this->assertStoredEntityMatchesSentNormalization($this->getNormalizedPostEntity(), $created_entity);
    }

    if ($this->entity->getEntityType()->getStorageClass() !== ContentEntityNullStorage::class && $this->entity->getEntityType()->hasKey('uuid')) {
      // 500 when creating an entity with a duplicate UUID.
      $normalized_entity = $this->getModifiedEntityForPostTesting();
      $normalized_entity[$created_entity->getEntityType()->getKey('uuid')] = [['value' => $created_entity->uuid()]];
      if ($label_field) {
        $normalized_entity[$label_field] = [['value' => $this->randomMachineName()]];
      }
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalized_entity, static::$format);

      $response = $this->request('POST', $url, $request_options);
      $this->assertSame(500, $response->getStatusCode());
      $this->assertStringContainsString('Internal Server Error', (string) $response->getBody());

      // 201 when successfully creating an entity with a new UUID.
      $normalized_entity = $this->getModifiedEntityForPostTesting();
      $new_uuid = \Drupal::service('uuid')->generate();
      $normalized_entity[$created_entity->getEntityType()->getKey('uuid')] = [['value' => $new_uuid]];
      if ($label_field) {
        $normalized_entity[$label_field] = [['value' => $this->randomMachineName()]];
      }
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalized_entity, static::$format);

      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceResponse(201, FALSE, $response);
      $entities = $this->entityStorage->loadByProperties([$created_entity->getEntityType()->getKey('uuid') => $new_uuid]);
      $new_entity = reset($entities);
      $this->assertNotNull($new_entity);
      $new_entity->delete();
    }
  }

  /**
   * Tests a PATCH request for an entity, plus edge cases to ensure good DX.
   */
  public function testPatch() {
    // Patch testing requires that another entity of the same type exists.
    $this->anotherEntity = $this->createAnotherEntity();

    $this->initAuthentication();
    $has_canonical_url = $this->entity->hasLinkTemplate('canonical');

    // Try with all of the following request bodies.
    $unparseable_request_body         = '!{>}<';
    $parseable_valid_request_body     = $this->serializer->encode($this->getNormalizedPatchEntity(), static::$format);
    $parseable_invalid_request_body   = $this->serializer->encode($this->makeNormalizationInvalid($this->getNormalizedPatchEntity(), 'label'), static::$format);
    $parseable_invalid_request_body_2 = $this->serializer->encode($this->getNormalizedPatchEntity() + ['field_rest_test' => [['value' => $this->randomString()]]], static::$format);
    // The 'field_rest_test' field does not allow 'view' access, so does not end
    // up in the normalization. Even when we explicitly add it the normalization
    // that we send in the body of a PATCH request, it is considered invalid.
    $parseable_invalid_request_body_3 = $this->serializer->encode($this->getNormalizedPatchEntity() + ['field_rest_test' => $this->entity->get('field_rest_test')->getValue()], static::$format);

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
      $this->assertStringContainsString('A client error happened', (string) $response->getBody());
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
    $this->assertStringContainsString('A client error happened', (string) $response->getBody());

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    if (static::$auth) {
      // DX: forgetting authentication: authentication provider-specific error
      // response.
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResponseWhenMissingAuthentication('PATCH', $response);
    }

    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('PATCH'));

    // DX: 403 when unauthorized.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, $this->getExpectedUnauthorizedAccessMessage('PATCH'), $response);

    $this->setUpAuthorization('PATCH');

    // DX: 400 when no request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No entity content received.', $response);

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Syntax error', $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('PATCH', $url, $request_options);
    if ($label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName) {
      $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
      $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\n$label_field: $label_field_capitalized: this field cannot hold more than 1 values.\n", $response);
    }

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "Access denied on updating field 'field_rest_test'.", $response);

    // DX: 403 when entity trying to update an entity's ID field.
    $request_options[RequestOptions::BODY] = $this->serializer->encode($this->makeNormalizationInvalid($this->getNormalizedPatchEntity(), 'id'), static::$format);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "Access denied on updating field '{$this->entity->getEntityType()->getKey('id')}'. The entity ID cannot be changed.", $response);

    if ($this->entity->getEntityType()->hasKey('uuid')) {
      // DX: 403 when entity trying to update an entity's UUID field.
      $request_options[RequestOptions::BODY] = $this->serializer->encode($this->makeNormalizationInvalid($this->getNormalizedPatchEntity(), 'uuid'), static::$format);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(403, "Access denied on updating field '{$this->entity->getEntityType()->getKey('uuid')}'. The entity UUID cannot be changed.", $response);
    }

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;

    // DX: 403 when entity contains field without 'edit' nor 'view' access, even
    // when the value for that field matches the current value. This is allowed
    // in principle, but leads to information disclosure.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, "Access denied on updating field 'field_rest_test'.", $response);

    // DX: 403 when sending PATCH request with updated read-only fields.
    $this->assertPatchProtectedFieldNamesStructure();
    list($modified_entity, $original_values) = static::getModifiedEntityForPatchTesting($this->entity);
    // Send PATCH request by serializing the modified entity, assert the error
    // response, change the modified entity field that caused the error response
    // back to its original value, repeat.
    foreach (static::$patchProtectedFieldNames as $patch_protected_field_name => $reason) {
      $request_options[RequestOptions::BODY] = $this->serializer->serialize($modified_entity, static::$format);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(403, "Access denied on updating field '" . $patch_protected_field_name . "'." . ($reason !== NULL ? ' ' . $reason : ''), $response);
      $modified_entity->get($patch_protected_field_name)->setValue($original_values[$patch_protected_field_name]);
    }

    if ($this->entity instanceof FieldableEntityInterface) {
      // Change the rest_test_validation field to prove that then its validation
      // does run.
      $override = [
        'rest_test_validation' => [
          [
            'value' => 'ALWAYS_FAIL',
          ],
        ],
      ];
      $valid_request_body = $override + $this->getNormalizedPatchEntity() + $this->serializer->normalize($modified_entity, static::$format);
      $request_options[RequestOptions::BODY] = $this->serializer->serialize($valid_request_body, static::$format);
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nrest_test_validation: REST test validation failed\n", $response);

      // Set the rest_test_validation field to always fail validation, which
      // allows asserting that not modifying that field does not trigger
      // validation errors.
      $this->entity->set('rest_test_validation', 'ALWAYS_FAIL');
      $this->entity->save();

      // Information disclosure prevented: when a malicious user correctly
      // guesses the current invalid value of a field, ensure a 200 is not sent
      // because this would disclose to the attacker what the current value is.
      // @see rest_test_entity_field_access()
      $response = $this->request('PATCH', $url, $request_options);
      $this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nrest_test_validation: REST test validation failed\n", $response);

      // All requests after the above one will not include this field (neither
      // its current value nor any other), and therefore all subsequent test
      // assertions should not trigger a validation error.
    }

    // 200 for well-formed PATCH request that sends all fields (even including
    // read-only ones, but with unchanged values).
    $valid_request_body = $this->getNormalizedPatchEntity() + $this->serializer->normalize($this->entity, static::$format);
    $request_options[RequestOptions::BODY] = $this->serializer->serialize($valid_request_body, static::$format);
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
    $this->assertStoredEntityMatchesSentNormalization($this->getNormalizedPatchEntity(), $updated_entity);
    // Ensure that fields do not get deleted if they're not present in the PATCH
    // request. Test this using the configurable field that we added, but which
    // is not sent in the PATCH request.
    $this->assertSame('All the faith they had had had had no effect on the outcome of their life.', $updated_entity->get('field_rest_test')->value);

    // Multi-value field: remove item 0. Then item 1 becomes item 0.
    $normalization_multi_value_tests = $this->getNormalizedPatchEntity();
    $normalization_multi_value_tests['field_rest_test_multivalue'] = $this->entity->get('field_rest_test_multivalue')->getValue();
    $normalization_remove_item = $normalization_multi_value_tests;
    unset($normalization_remove_item['field_rest_test_multivalue'][0]);
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization_remove_item, static::$format);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertSame([0 => ['value' => 'Two']], $this->entityStorage->loadUnchanged($this->entity->id())->get('field_rest_test_multivalue')->getValue());

    // Multi-value field: add one item before the existing one, and one after.
    $normalization_add_items = $normalization_multi_value_tests;
    $normalization_add_items['field_rest_test_multivalue'][2] = ['value' => 'Three'];
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization_add_items, static::$format);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertSame([0 => ['value' => 'One'], 1 => ['value' => 'Two'], 2 => ['value' => 'Three']], $this->entityStorage->loadUnchanged($this->entity->id())->get('field_rest_test_multivalue')->getValue());
  }

  /**
   * Tests a DELETE request for an entity, plus edge cases to ensure good DX.
   */
  public function testDelete() {
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
      $this->assertStringContainsString('A client error happened', (string) $response->getBody());
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
      $this->assertResponseWhenMissingAuthentication('DELETE', $response);
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
    $this->assertResourceResponse(204, '', $response);
  }

  /**
   * Asserts structure of $patchProtectedFieldNames.
   */
  protected function assertPatchProtectedFieldNamesStructure() {
    $is_null_or_string = function ($value) {
      return is_null($value) || is_string($value);
    };
    $this->assertTrue(
      Inspector::assertAllStrings(array_keys(static::$patchProtectedFieldNames)),
      'In Drupal 8.6, the structure of $patchProtectedFieldNames changed. It used to be an array with field names as values. Now those values are the keys, and their values should be either NULL or a string: a string containing the reason for why the field cannot be PATCHed, or NULL otherwise.'
    );
    $this->assertTrue(
      Inspector::assertAll($is_null_or_string, static::$patchProtectedFieldNames),
      'In Drupal 8.6, the structure of $patchProtectedFieldNames changed. It used to be an array with field names as values. Now those values are the keys, and their values should be either NULL or a string: a string containing the reason for why the field cannot be PATCHed, or NULL otherwise.'
    );
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
   * Clones the given entity and modifies all PATCH-protected fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested and to modify.
   *
   * @return array
   *   Contains two items:
   *   1. The modified entity object.
   *   2. The original field values, keyed by field name.
   *
   * @internal
   */
  protected static function getModifiedEntityForPatchTesting(EntityInterface $entity) {
    $modified_entity = clone $entity;
    $original_values = [];
    foreach (array_keys(static::$patchProtectedFieldNames) as $field_name) {
      $field = $modified_entity->get($field_name);
      $original_values[$field_name] = $field->getValue();
      switch ($field->getItemDefinition()->getClass()) {
        case EntityReferenceItem::class:
          // EntityReferenceItem::generateSampleValue() picks one of the last 50
          // entities of the supported type & bundle. We don't care if the value
          // is valid, we only care that it's different.
          $field->setValue(['target_id' => 99999]);
          break;

        case BooleanItem::class:
          // BooleanItem::generateSampleValue() picks either 0 or 1. So a 50%
          // chance of not picking a different value.
          $field->value = ((int) $field->value) === 1 ? '0' : '1';
          break;

        case PathItem::class:
          // PathItem::generateSampleValue() doesn't set a PID, which causes
          // PathItem::postSave() to fail. Keep the PID (and other properties),
          // just modify the alias.
          $field->alias = str_replace(' ', '-', strtolower((new Random())->sentences(3)));
          break;

        default:
          $original_field = clone $field;
          while ($field->equals($original_field)) {
            $field->generateSampleItems();
          }
          break;
      }
    }

    return [$modified_entity, $original_values];
  }

  /**
   * Makes the given entity normalization invalid.
   *
   * @param array $normalization
   *   An entity normalization.
   * @param string $entity_key
   *   The entity key whose normalization to make invalid.
   *
   * @return array
   *   The updated entity normalization, now invalid.
   */
  protected function makeNormalizationInvalid(array $normalization, $entity_key) {
    $entity_type = $this->entity->getEntityType();
    switch ($entity_key) {
      case 'label':
        // Add a second label to this entity to make it invalid.
        if ($label_field = $entity_type->hasKey('label') ? $entity_type->getKey('label') : static::$labelFieldName) {
          $normalization[$label_field][1]['value'] = 'Second Title';
        }
        break;

      case 'id':
        $normalization[$entity_type->getKey('id')][0]['value'] = $this->anotherEntity->id();
        break;

      case 'uuid':
        $normalization[$entity_type->getKey('uuid')][0]['value'] = $this->anotherEntity->uuid();
        break;
    }
    return $normalization;
  }

  /**
   * Asserts that the stored entity matches the sent normalization.
   *
   * @param array $sent_normalization
   *   An entity normalization.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $modified_entity
   *   The entity object of the modified (PATCHed or POSTed) entity.
   */
  protected function assertStoredEntityMatchesSentNormalization(array $sent_normalization, FieldableEntityInterface $modified_entity) {
    foreach ($sent_normalization as $field_name => $field_normalization) {
      // Some top-level keys in the normalization may not be fields on the
      // entity (for example '_links' and '_embedded' in the HAL normalization).
      if ($modified_entity->hasField($field_name)) {
        $field_definition = $modified_entity->get($field_name)->getFieldDefinition();
        $property_definitions = $field_definition->getItemDefinition()->getPropertyDefinitions();
        $expected_stored_data = [];
        // Some fields don't have any property definitions, so there's nothing
        // to denormalize.
        if (empty($property_definitions)) {
          $expected_stored_data = $field_normalization;
        }
        else {
          // Denormalize every sent field item property to make it possible to
          // compare against the stored value.
          $denormalization_context = ['field_definition' => $field_definition];
          foreach ($field_normalization as $delta => $expected_field_item_normalization) {
            foreach ($property_definitions as $property_name => $property_definition) {
              // Not every property is required to be sent.
              if (!array_key_exists($property_name, $field_normalization[$delta])) {
                continue;
              }
              // Computed properties are not stored.
              if ($property_definition->isComputed()) {
                continue;
              }
              $property_value = $field_normalization[$delta][$property_name];
              $property_value_class = $property_definitions[$property_name]->getClass();
              $expected_stored_data[$delta][$property_name] = $this->serializer->supportsDenormalization($property_value, $property_value_class, NULL, $denormalization_context)
                ? $this->serializer->denormalize($property_value, $property_value_class, NULL, $denormalization_context)
                : $property_value;
            }
          }
          // Fields are stored in the database, when read they are represented
          // as strings in PHP memory.
          $expected_stored_data = static::castToString($expected_stored_data);
        }
        $this->assertEntityArraySubset($expected_stored_data, $modified_entity->get($field_name)->getValue());
      }
    }
  }

  /**
   * Recursively asserts that the expected items are set in the tested entity.
   *
   * A response may include more properties, we only need to ensure that all
   * items in the request exist in the response.
   *
   * @param $expected
   *   An array of expected values, may contain further nested arrays.
   * @param $actual
   *   The object to test.
   */
  protected function assertEntityArraySubset($expected, $actual) {
    foreach ($expected as $key => $value) {
      if (is_array($value)) {
        $this->assertEntityArraySubset($value, $actual[$key]);
      }
      else {
        $this->assertSame($value, $actual[$key]);
      }
    }
  }

}
