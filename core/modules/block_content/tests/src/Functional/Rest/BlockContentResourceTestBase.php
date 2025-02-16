<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Rest;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Cache\Cache;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * Resource test base for BlockContent entity.
 */
abstract class BlockContentResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'block_content';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
      case 'PATCH':
        $this->grantPermissionsToTestedRole(['access block library', 'edit any basic block content']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['create basic block content']);
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete any basic block content']);
        break;

      default:
        $this->grantPermissionsToTestedRole(['administer block content']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if (!BlockContentType::load('basic')) {
      $block_content_type = BlockContentType::create([
        'id' => 'basic',
        'label' => 'basic',
        'revision' => TRUE,
      ]);
      $block_content_type->save();
      block_content_add_body_field($block_content_type->id());
    }

    // Create a "Llama" content block.
    $block_content = BlockContent::create([
      'info' => 'Llama',
      'type' => 'basic',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
    ])
      ->setUnpublished();
    $block_content->save();
    return $block_content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'id' => [
        [
          'value' => 1,
        ],
      ],
      'uuid' => [
        [
          'value' => $this->entity->uuid(),
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'reusable' => [
        [
          'value' => TRUE,
        ],
      ],
      'type' => [
        [
          'target_id' => 'basic',
          'target_type' => 'block_content_type',
          'target_uuid' => BlockContentType::load('basic')->uuid(),
        ],
      ],
      'info' => [
        [
          'value' => 'Llama',
        ],
      ],
      'revision_log' => [],
      'changed' => [
        [
          'value' => (new \DateTime())->setTimestamp((int) $this->entity->getChangedTime())
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'revision_id' => [
        [
          'value' => 1,
        ],
      ],
      'revision_created' => [
        [
          'value' => (new \DateTime())->setTimestamp((int) $this->entity->getRevisionCreationTime())
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'revision_user' => [],
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
      'body' => [
        [
          'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
          'format' => 'plain_text',
          'summary' => NULL,
          'processed' => "<p>The name &quot;llama&quot; was adopted by European settlers from native Peruvians.</p>\n",
        ],
      ],
      'status' => [
        [
          'value' => FALSE,
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
          'target_id' => 'basic',
        ],
      ],
      'info' => [
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
    if (!$this->resourceConfigStorage->load(static::$resourceConfigId)) {
      return match ($method) {
        'GET', 'PATCH' => "The 'edit any basic block content' permission is required.",
        'POST' => "The following permissions are required: 'create basic block content' OR 'administer block content'.",
        'DELETE' => "The 'delete any basic block content' permission is required.",
        default => parent::getExpectedUnauthorizedAccessMessage($method),
      };
    }
    return match ($method) {
      'GET' => "The 'access block library' permission is required.",
      'PATCH' => "The 'edit any basic block content' permission is required.",
      'POST' => "The following permissions are required: 'create basic block content' OR 'administer block content'.",
      'DELETE' => "The 'delete any basic block content' permission is required.",
      default => parent::getExpectedUnauthorizedAccessMessage($method),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedEntityAccessCacheability($is_authenticated) {
    // @see \Drupal\block_content\BlockContentAccessControlHandler()
    return parent::getExpectedUnauthorizedEntityAccessCacheability($is_authenticated)
      ->addCacheTags(['block_content:1']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['config:filter.format.plain_text']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return Cache::mergeContexts(['url.site'], $this->container->getParameter('renderer.config')['required_cache_contexts']);
  }

}
