<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "Comment" content entity type.
 *
 * @group jsonapi
 */
class CommentTest extends ResourceTestBase {

  use CommentTestTrait;
  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'comment';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'comment--comment';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'status' => "The 'administer comments' permission is required.",
    'name' => "The 'administer comments' permission is required.",
    'homepage' => "The 'administer comments' permission is required.",
    'created' => "The 'administer comments' permission is required.",
    'changed' => NULL,
    'thread' => NULL,
    'entity_type' => NULL,
    'field_name' => NULL,
    // @todo Uncomment this after https://www.drupal.org/project/drupal/issues/1847608 lands. Until then, it's impossible to test this.
    // 'pid' => NULL,
    'uid' => "The 'administer comments' permission is required.",
    'entity_id' => NULL,
  ];

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access comments', 'view test entity']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['post comments']);
        break;

      case 'PATCH':
        $this->grantPermissionsToTestedRole(['edit own comments']);
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer comments']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "bar" bundle for the "entity_test" entity type and create.
    $bundle = 'bar';
    entity_test_create_bundle($bundle, NULL, 'entity_test');

    // Create a comment field on this bundle.
    $this->addDefaultCommentField('entity_test', 'bar', 'comment');

    // Create a "Camelids" test entity that the comment will be assigned to.
    $commented_entity = EntityTest::create([
      'name' => 'Camelids',
      'type' => 'bar',
    ]);
    $commented_entity->save();

    // Create a "Llama" comment.
    $comment = Comment::create([
      'comment_body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
      'entity_id' => $commented_entity->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
    ]);
    $comment->setSubject('Llama')
      ->setOwnerId($this->account->id())
      ->setPublished()
      ->setCreatedTime(123456789)
      ->setChangedTime(123456789);
    $comment->save();

    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/comment/comment/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $author = User::load($this->entity->getOwnerId());
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'comment--comment',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => '1973-11-29T21:33:09+00:00',
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'comment_body' => [
            'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
            'format' => 'plain_text',
            'processed' => "<p>The name &quot;llama&quot; was adopted by European settlers from native Peruvians.</p>\n",
          ],
          'default_langcode' => TRUE,
          'entity_type' => 'entity_test',
          'field_name' => 'comment',
          'homepage' => NULL,
          'langcode' => 'en',
          'name' => NULL,
          'status' => TRUE,
          'subject' => 'Llama',
          'thread' => '01/',
          'drupal_internal__cid' => 1,
        ],
        'relationships' => [
          'uid' => [
            'data' => [
              'id' => $author->uuid(),
              'type' => 'user--user',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/uid'],
              'self' => ['href' => $self_url . '/relationships/uid'],
            ],
          ],
          'comment_type' => [
            'data' => [
              'id' => CommentType::load('comment')->uuid(),
              'type' => 'comment_type--comment_type',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/comment_type'],
              'self' => ['href' => $self_url . '/relationships/comment_type'],
            ],
          ],
          'entity_id' => [
            'data' => [
              'id' => EntityTest::load(1)->uuid(),
              'type' => 'entity_test--bar',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/entity_id'],
              'self' => ['href' => $self_url . '/relationships/entity_id'],
            ],
          ],
          'pid' => [
            'data' => NULL,
            'links' => [
              'related' => ['href' => $self_url . '/pid'],
              'self' => ['href' => $self_url . '/relationships/pid'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'comment--comment',
        'attributes' => [
          'entity_type' => 'entity_test',
          'field_name' => 'comment',
          'subject' => 'Dramallama',
          'comment_body' => [
            'value' => 'Llamas are awesome.',
            'format' => 'plain_text',
          ],
        ],
        'relationships' => [
          'entity_id' => [
            'data' => [
              'type' => 'entity_test--bar',
              'id' => EntityTest::load(1)->uuid(),
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags(array $sparse_fieldset = NULL) {
    $tags = parent::getExpectedCacheTags($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('comment_body', $sparse_fieldset)) {
      $tags = Cache::mergeTags($tags, ['config:filter.format.plain_text']);
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $contexts = parent::getExpectedCacheContexts($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('comment_body', $sparse_fieldset)) {
      $contexts = Cache::mergeContexts($contexts, ['languages:language_interface', 'theme']);
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET';
        return "The 'access comments' permission is required and the comment must be published.";

      case 'POST';
        return "The 'post comments' permission is required.";

      case 'PATCH':
        return "The 'edit own comments' permission is required, the user must be the comment author, and the comment must be published.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
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
  public function testPostIndividualDxWithoutCriticalBaseFields() {
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
      if (version_compare(phpversion(), '7.0') >= 0) {
        $this->assertSame("Error: Call to a member function get() on null\nDrupal\\comment\\Plugin\\Validation\\Constraint\\CommentNameConstraintValidator->getAnonymousContactDetailsSetting()() (Line: 96)\n", $e->getMessage());
      }
      else {
        $this->assertSame(500, $response->getStatusCode());
      }
    }

    // DX: 422 when missing 'field_name' field.
    $request_options[RequestOptions::BODY] = Json::encode($remove_field($this->getPostDocument(), 'attributes', 'field_name'));
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'field_name: This value should not be null.', NULL, $response, '/data/attributes/field_name');
  }

  /**
   * Tests POSTing a comment with and without 'skip comment approval'.
   */
  public function testPostIndividualSkipCommentApproval() {
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
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertFalse(Json::decode((string) $response->getBody())['data']['attributes']['status']);
    $this->assertFalse($this->entityStorage->loadUnchanged(2)->isPublished());

    // Grant anonymous permission to skip comment approval.
    $this->grantPermissionsToTestedRole(['skip comment approval']);

    // Status must be TRUE when posting as anonymous and skip comment approval.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    $this->assertTrue(Json::decode((string) $response->getBody())['data']['attributes']['status']);
    $this->assertTrue($this->entityStorage->loadUnchanged(3)->isPublished());
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    // @see \Drupal\comment\CommentAccessControlHandler::checkAccess()
    return parent::getExpectedUnauthorizedAccessCacheability()
      ->addCacheTags(['comment:1']);
  }

  /**
   * {@inheritdoc}
   */
  protected static function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Also reset the 'entity_test' entity access control handler because
    // comment access also depends on access to the commented entity type.
    \Drupal::entityTypeManager()->getAccessControlHandler('entity_test')->resetCache();
    return parent::entityAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function testRelated() {
    $this->markTestSkipped('Remove this in https://www.drupal.org/project/jsonapi/issues/2940339');
  }

  /**
   * {@inheritdoc}
   */
  protected static function getIncludePermissions() {
    return [
      'type' => ['administer comment types'],
      'uid' => ['access user profiles'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess() {
    // Verify the expected behavior in the common case.
    $this->doTestCollectionFilterAccessForPublishableEntities('subject', 'access comments', 'administer comments');

    $collection_url = Url::fromRoute('jsonapi.entity_test--bar.collection');
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Go back to a simpler scenario: revoke the admin permission, publish the
    // comment and uninstall the query access test module.
    $this->revokePermissionsFromTestedRole(['administer comments']);
    $this->entity->setPublished()->save();
    $this->assertTrue($this->container->get('module_installer')->uninstall(['jsonapi_test_field_filter_access'], TRUE), 'Uninstalled modules.');
    // ?filter[spotlight.LABEL]: 1 result. Just as already tested above in
    // ::doTestCollectionFilterAccessForPublishableEntities().
    $collection_filter_url = $collection_url->setOption('query', ["filter[spotlight.subject]" => $this->entity->label()]);
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    // Mark the commented entity as inaccessible.
    \Drupal::state()->set('jsonapi__entity_test_filter_access_blacklist', [$this->entity->getCommentedEntityId()]);
    Cache::invalidateTags(['state:jsonapi__entity_test_filter_access_blacklist']);
    // ?filter[spotlight.LABEL]: 0 results.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
  }

  /**
   * {@inheritdoc}
   */
  protected static function getExpectedCollectionCacheability(AccountInterface $account, array $collection, array $sparse_fieldset = NULL, $filtered = FALSE) {
    $cacheability = parent::getExpectedCollectionCacheability($account, $collection, $sparse_fieldset, $filtered);
    if ($filtered) {
      $cacheability->addCacheTags(['state:jsonapi__entity_test_filter_access_blacklist']);
    }
    return $cacheability;
  }

  /**
   * {@inheritdoc}
   */
  public function testPatchIndividual() {
    // Ensure ::getModifiedEntityForPatchTesting() can pick an alternative value
    // for the 'entity_id' field.
    EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'bar',
    ])->save();

    return parent::testPatchIndividual();
  }

}
