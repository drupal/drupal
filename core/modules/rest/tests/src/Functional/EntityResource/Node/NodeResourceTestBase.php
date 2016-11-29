<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Node;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;

abstract class NodeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'revision_timestamp',
    'revision_uid',
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
      'promote' => [
        [
          'value' => 1,
        ],
      ],
      'sticky' => [
        [
          'value' => '0',
        ],
      ],
      'revision_timestamp' => [
        [
          'value' => '123456789',
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
          'target_id' => $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'revision_uid' => [
        [
          'target_id' => $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'revision_log' => [
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

}
