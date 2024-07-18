<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Rest;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;

abstract class NodeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation', 'node', 'path'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'revision_timestamp' => NULL,
    'revision_uid' => NULL,
    'created' => "The 'administer nodes' permission is required.",
    'changed' => NULL,
    'promote' => "The 'administer nodes' permission is required.",
    'sticky' => "The 'administer nodes' permission is required.",
    'path' => "The following permissions are required: 'create url aliases' OR 'administer url aliases'.",
  ];

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * Marks some tests as skipped because XML cannot be deserialized.
   *
   * @before
   */
  public function nodeResourceTestBaseSkipTests(): void {
    if (static::$format === 'xml' && $this->name() === 'testPatchPath') {
      $this->markTestSkipped('Deserialization of the XML format is not supported.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['access content', 'create camelids content']);
        break;

      case 'PATCH':
        // Do not grant the 'create url aliases' permission to test the case
        // when the path field is protected/not accessible, see
        // \Drupal\Tests\rest\Functional\EntityResource\Term\TermResourceTestBase
        // for a positive test.
        $this->grantPermissionsToTestedRole(['access content', 'edit any camelids content']);
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['access content', 'delete any camelids content']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if (!NodeType::load('camelids')) {
      // Create a "Camelids" node type.
      NodeType::create([
        'name' => 'Camelids',
        'type' => 'camelids',
      ])->save();
    }

    // Create a "Llama" node.
    $node = Node::create(['type' => 'camelids']);
    $node->setTitle('Llama')
      ->setOwnerId(static::$auth ? $this->account->id() : 0)
      ->setPublished()
      ->setCreatedTime(123456789)
      ->setChangedTime(123456789)
      ->setRevisionCreationTime(123456789)
      ->set('path', '/llama')
      ->save();

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $author = User::load($this->entity->getOwnerId());
    return [
      'nid' => [
        ['value' => 1],
      ],
      'uuid' => [
        ['value' => $this->entity->uuid()],
      ],
      'vid' => [
        ['value' => 1],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'type' => [
        [
          'target_id' => 'camelids',
          'target_type' => 'node_type',
          'target_uuid' => NodeType::load('camelids')->uuid(),
        ],
      ],
      'title' => [
        [
          'value' => 'Llama',
        ],
      ],
      'status' => [
        [
          'value' => TRUE,
        ],
      ],
      'created' => [
        [
          'value' => (new \DateTime())->setTimestamp(123456789)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'changed' => [
        [
          'value' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'promote' => [
        [
          'value' => TRUE,
        ],
      ],
      'sticky' => [
        [
          'value' => FALSE,
        ],
      ],
      'revision_timestamp' => [
        [
          'value' => (new \DateTime())->setTimestamp(123456789)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'revision_translation_affected' => [
        [
          'value' => TRUE,
        ],
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
      'uid' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'revision_uid' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'path' => [
        [
          'alias' => '/llama',
          'pid' => 1,
          'langcode' => 'en',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'type' => [
        [
          'target_id' => 'camelids',
        ],
      ],
      'title' => [
        [
          'value' => 'Drama llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($method === 'GET' || $method == 'PATCH' || $method == 'DELETE' || $method == 'POST') {
      return "The 'access content' permission is required.";
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return [
      'languages:language_interface',
      'url.site',
      'user.permissions',
    ];
  }

  /**
   * Tests PATCHing a node's path with and without 'create url aliases'.
   *
   * For a positive test, see the similar test coverage for Term.
   *
   * @see \Drupal\Tests\rest\Functional\EntityResource\Term\TermResourceTestBase::testPatchPath()
   */
  public function testPatchPath(): void {
    $this->initAuthentication();
    $this->provisionEntityResource();
    $this->setUpAuthorization('GET');
    $this->setUpAuthorization('PATCH');

    $url = $this->getEntityResourceUrl()->setOption('query', ['_format' => static::$format]);

    // GET node's current normalization.
    $response = $this->request('GET', $url, $this->getAuthenticationRequestOptions('GET'));
    $normalization = $this->serializer->decode((string) $response->getBody(), static::$format);

    // Change node's path alias.
    $normalization['path'][0]['alias'] .= 's-rule-the-world';

    // Create node PATCH request.
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;
    $request_options = array_merge_recursive($request_options, $this->getAuthenticationRequestOptions('PATCH'));
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

    // PATCH request: 403 when creating URL aliases unauthorized. Before
    // asserting the 403 response, assert that the stored path alias remains
    // unchanged.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame('/llama', $this->entityStorage->loadUnchanged($this->entity->id())->get('path')->alias);
    $this->assertResourceErrorResponse(403, "Access denied on updating field 'path'. " . static::$patchProtectedFieldNames['path'], $response);

    // Make sure the role save below properly invalidates cache tags.
    $this->refreshVariables();

    // Grant permission to create URL aliases.
    $this->grantPermissionsToTestedRole(['create url aliases']);

    // Repeat PATCH request: 200.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $updated_normalization = $this->serializer->decode((string) $response->getBody(), static::$format);
    $this->assertSame($normalization['path'], $updated_normalization['path']);
  }

}
