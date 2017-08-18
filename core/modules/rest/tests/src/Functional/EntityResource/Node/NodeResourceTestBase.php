<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Node;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;

abstract class NodeResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'path'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'revision_timestamp',
    'revision_uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'path',
  ];

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

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
      ->setPublished(TRUE)
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
        $this->formatExpectedTimestampItemValues(123456789),
      ],
      'changed' => [
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
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
        $this->formatExpectedTimestampItemValues(123456789),
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
      'revision_log' => [],
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
          'value' => 'Dramallama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    if ($method === 'GET' || $method == 'PATCH' || $method == 'DELETE') {
      return "The 'access content' permission is required.";
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

}
