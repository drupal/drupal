<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Comment;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;

abstract class CommentResourceTestBase extends EntityResourceTestBase {

  use CommentTestTrait;

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
  protected static $patchProtectedFieldNames = [
    'pid',
    'entity_id',
    'uid',
    'name',
    'homepage',
    'created',
    'changed',
    'status',
    'thread',
    'entity_type',
    'field_name',
  ];

  /**
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
        // Anononymous users are not ever allowed to edit their own comments. To
        // be able to test PATCHing comments as the anonymous user, the more
        // permissive 'administer comments' permission must be granted.
        // @see \Drupal\comment\CommentAccessControlHandler::checkAccess
        if (static::$auth) {
          $this->grantPermissionsToTestedRole(['edit own comments']);
        }
        else {
          $this->grantPermissionsToTestedRole(['administer comments']);
        }
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
    $commented_entity = EntityTest::create(array(
      'name' => 'Camelids',
      'type' => 'bar',
    ));
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
      ->setOwnerId(static::$auth ? $this->account->id() : 0)
      ->setPublished(TRUE)
      ->setCreatedTime(123456789)
      ->setChangedTime(123456789);
    $comment->save();

    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $author = User::load($this->entity->getOwnerId());
    return [
      'cid' => [
        ['value' => 1],
      ],
      'uuid' => [
        ['value' => $this->entity->uuid()],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'comment_type' => [
        [
          'target_id' => 'comment',
          'target_type' => 'comment_type',
          'target_uuid' => CommentType::load('comment')->uuid(),
        ],
      ],
      'subject' => [
        [
          'value' => 'Llama',
        ],
      ],
      'status' => [
        [
          'value' => 1,
        ],
      ],
      'created' => [
        [
          'value' => '123456789',
        ],
      ],
      'changed' => [
        [
          'value' => '123456789',
        ],
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
      'uid' => [
        [
          'target_id' => $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'pid' => [],
      'entity_type' => [
        [
          'value' => 'entity_test',
        ],
      ],
      'entity_id' => [
        [
          'target_id' => '1',
          'target_type' => 'entity_test',
          'target_uuid' => EntityTest::load(1)->uuid(),
          'url' => base_path() . 'entity_test/1',
        ],
      ],
      'field_name' => [
        [
          'value' => 'comment',
        ],
      ],
      'name' => [],
      'homepage' => [],
      'thread' => [
        [
          'value' => '01/',
        ],
      ],
      'comment_body' => [
        [
          'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
          'format' => 'plain_text',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'comment_type' => [
        [
          'target_id' => 'comment',
        ],
      ],
      'entity_type' => [
        [
          'value' => 'entity_test',
        ],
      ],
      'entity_id' => [
        [
          'target_id' => EntityTest::load(1)->id(),
        ],
      ],
      'field_name' => [
        [
          'value' => 'comment',
        ],
      ],
      'subject' => [
        [
          'value' => 'Dramallama',
        ],
      ],
      'comment_body' => [
        [
          'value' => 'Llamas are awesome.',
          'format' => 'plain_text',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPatchEntity() {
    return array_diff_key($this->getNormalizedPostEntity(), ['entity_type' => TRUE, 'entity_id' => TRUE, 'field_name' => TRUE]);
  }

  /**
   * Tests POSTing a comment without critical base fields.
   *
   * testPost() is testing with the most minimal normalization possible: the one
   * returned by ::getNormalizedPostEntity().
   *
   * But Comment entities have some very special edge cases:
   * - base fields that are not marked as required in
   *   \Drupal\comment\Entity\Comment::baseFieldDefinitions() yet in fact are
   *   required.
   * - base fields that are marked as required, but yet can still result in
   *   validation errors other than "missing required field".
   */
  public function testPostDxWithoutCriticalBaseFields() {
    $this->initAuthentication();
    $this->provisionEntityResource();
    $this->setUpAuthorization('POST');

    $url = $this->getPostUrl()->setOption('query', ['_format' => static::$format]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = static::$mimeType;
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;
    $request_options = array_merge_recursive($request_options, $this->getAuthenticationRequestOptions('POST'));

    // DX: 422 when missing 'entity_type' field.
    $request_options[RequestOptions::BODY] = $this->serializer->encode(array_diff_key($this->getNormalizedPostEntity(), ['entity_type' => TRUE]), static::$format);
    $response = $this->request('POST', $url, $request_options);
    // @todo Uncomment, remove next line in https://www.drupal.org/node/2820364.
    $this->assertResourceErrorResponse(500, 'A fatal error occurred: Internal Server Error', $response);
    //$this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nentity_type: This value should not be null.\n", $response);

    // DX: 422 when missing 'entity_id' field.
    $request_options[RequestOptions::BODY] = $this->serializer->encode(array_diff_key($this->getNormalizedPostEntity(), ['entity_id' => TRUE]), static::$format);
    // @todo Remove the try/catch in favor of the two commented lines in
    // https://www.drupal.org/node/2820364.
    try {
      $response = $this->request('POST', $url, $request_options);
      // This happens on DrupalCI.
      //$this->assertSame(500, $response->getStatusCode());
    }
    catch (\Exception $e) {
      // This happens on Wim's local machine.
      //$this->assertSame("Error: Call to a member function get() on null\nDrupal\\comment\\Plugin\\Validation\\Constraint\\CommentNameConstraintValidator->getAnonymousContactDetailsSetting()() (Line: 96)\n", $e->getMessage());
    }
    //$response = $this->request('POST', $url, $request_options);
    //$this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nentity_type: This value should not be null.\n", $response);

    // DX: 422 when missing 'entity_type' field.
    $request_options[RequestOptions::BODY] = $this->serializer->encode(array_diff_key($this->getNormalizedPostEntity(), ['field_name' => TRUE]), static::$format);
    $response = $this->request('POST', $url, $request_options);
    // @todo Uncomment, remove next line in https://www.drupal.org/node/2820364.
    $this->assertResourceErrorResponse(500, 'A fatal error occurred: Field  is unknown.', $response);
    //$this->assertResourceErrorResponse(422, "Unprocessable Entity: validation failed.\nfield_name: This value should not be null.\n", $response);
  }

}
