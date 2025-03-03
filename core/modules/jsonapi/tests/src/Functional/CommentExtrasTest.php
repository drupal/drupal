<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "Comment" content entity type.
 *
 * @group jsonapi
 */
class CommentExtrasTest extends CommentTest {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    // Don't run any test methods from CommentTest because those will get run
    // for CommentTest itself.
    if (method_exists(parent::class, $this->name())) {
      $this->markTestSkipped();
    }
    parent::setUp();
  }

  /**
   * Tests POSTing a comment without critical base fields.
   *
   * Note that testPostIndividual() is testing with the most minimal
   * normalization possible: the one returned by ::getNormalizedPostEntity().
   *
   * But Comment entities have some very special edge cases:
   * - base fields that are not marked as required in
   *   \Drupal\comment\Entity\Comment::baseFieldDefinitions() yet in fact are
   *   required.
   * - base fields that are marked as required, but yet can still result in
   *   validation errors other than "missing required field".
   */
  public function testPostIndividualDxWithoutCriticalBaseFields(): void {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $url = Url::fromRoute(sprintf('jsonapi.%s.collection.post', static::$resourceTypeName));
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    $remove_field = function (array $normalization, $type, $attribute_name) {
      unset($normalization['data'][$type][$attribute_name]);
      return $normalization;
    };

    // DX: 422 when missing 'entity_type' field.
    $request_options[RequestOptions::BODY] = Json::encode($remove_field($this->getPostDocument(), 'attributes', 'entity_type'));
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'entity_type: This value should not be null.', NULL, $response, '/data/attributes/entity_type');

    // DX: 422 when missing 'entity_id' field.
    $request_options[RequestOptions::BODY] = Json::encode($remove_field($this->getPostDocument(), 'relationships', 'entity_id'));
    // @todo Remove the try/catch in https://www.drupal.org/node/2820364.
    try {
      $response = $this->request('POST', $url, $request_options);
      $this->assertResourceErrorResponse(422, 'entity_id: This value should not be null.', NULL, $response, '/data/attributes/entity_id');
    }
    catch (\Exception $e) {
      $this->assertSame("Error: Call to a member function get() on null\nDrupal\\comment\\Plugin\\Validation\\Constraint\\CommentNameConstraintValidator->getAnonymousContactDetailsSetting()() (Line: 96)\n", $e->getMessage());
    }

    // DX: 422 when missing 'field_name' field.
    $request_options[RequestOptions::BODY] = Json::encode($remove_field($this->getPostDocument(), 'attributes', 'field_name'));
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'field_name: This value should not be null.', NULL, $response, '/data/attributes/field_name');
  }

  /**
   * Tests POSTing a comment with and without 'skip comment approval'.
   */
  public function testPostIndividualSkipCommentApproval(): void {
    $this->setUpAuthorization('POST');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Create request.
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $request_options[RequestOptions::BODY] = Json::encode($this->getPostDocument());

    $url = Url::fromRoute('jsonapi.comment--comment.collection.post');

    // Status should be FALSE when posting as anonymous.
    $response = $this->request('POST', $url, $request_options);
    $document = $this->getDocumentFromResponse($response);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertFalse($document['data']['attributes']['status']);
    $this->assertFalse($this->entityStorage->loadUnchanged(2)->isPublished());

    // Grant anonymous permission to skip comment approval.
    $this->grantPermissionsToTestedRole(['skip comment approval']);

    // Status must be TRUE when posting as anonymous and skip comment approval.
    $response = $this->request('POST', $url, $request_options);
    $document = $this->getDocumentFromResponse($response);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertTrue($document['data']['attributes']['status']);
    $this->assertTrue($this->entityStorage->loadUnchanged(3)->isPublished());
  }

}
