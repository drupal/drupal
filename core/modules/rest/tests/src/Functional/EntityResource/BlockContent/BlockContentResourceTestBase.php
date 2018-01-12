<?php

namespace Drupal\Tests\rest\Functional\EntityResource\BlockContent;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Cache\Cache;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for BlockContent entity.
 */
abstract class BlockContentResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'block_content';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed',
  ];

  /**
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer blocks']);
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

    // Create a "Llama" custom block.
    $block_content = BlockContent::create([
      'info' => 'Llama',
      'type' => 'basic',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'plain_text',
      ],
    ])
      ->setPublished(FALSE);
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
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
      ],
      'revision_id' => [
        [
          'value' => 1,
        ],
      ],
      'revision_created' => [
        $this->formatExpectedTimestampItemValues((int) $this->entity->getRevisionCreationTime()),
      ],
      'revision_user' => [],
      'revision_translation_affected' => [
        [
          'value' => TRUE,
        ],
      ],
      'revision_default' => [
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

    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    // @see \Drupal\block_content\BlockContentAccessControlHandler()
    return parent::getExpectedUnauthorizedAccessCacheability()
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
