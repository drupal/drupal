<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;

/**
 * JSON:API integration test for the "BlockContent" content entity type.
 *
 * @group jsonapi
 */
class BlockContentTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'block_content';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'block_content--basic';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer blocks']);
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity() {
    if (!BlockContentType::load('basic')) {
      $block_content_type = BlockContentType::create([
        'id' => 'basic',
        'label' => 'basic',
        'revision' => FALSE,
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
      ->setUnpublished();
    $block_content->save();
    return $block_content;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/block_content/basic/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'block_content--basic',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'body' => [
            'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
            'format' => 'plain_text',
            'summary' => NULL,
            'processed' => "<p>The name &quot;llama&quot; was adopted by European settlers from native Peruvians.</p>\n",
          ],
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'info' => 'Llama',
          'revision_log' => NULL,
          'revision_created' => (new \DateTime())->setTimestamp($this->entity->getRevisionCreationTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'revision_translation_affected' => TRUE,
          'status' => FALSE,
          'langcode' => 'en',
          'default_langcode' => TRUE,
          'drupal_internal__id' => 1,
          'drupal_internal__revision_id' => 1,
          'reusable' => TRUE,
        ],
        'relationships' => [
          'block_content_type' => [
            'data' => [
              'id' => BlockContentType::load('basic')->uuid(),
              'type' => 'block_content_type--block_content_type',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/block_content_type'],
              'self' => ['href' => $self_url . '/relationships/block_content_type'],
            ],
          ],
          'revision_user' => [
            'data' => NULL,
            'links' => [
              'related' => ['href' => $self_url . '/revision_user'],
              'self' => ['href' => $self_url . '/relationships/revision_user'],
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
        'type' => 'block_content--basic',
        'attributes' => [
          'info' => 'Dramallama',
        ],
      ],
    ];
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
  protected function getExpectedCacheTags(array $sparse_fieldset = NULL) {
    $tags = parent::getExpectedCacheTags($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('body', $sparse_fieldset)) {
      $tags = Cache::mergeTags($tags, ['config:filter.format.plain_text']);
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $contexts = parent::getExpectedCacheContexts($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('body', $sparse_fieldset)) {
      $contexts = Cache::mergeContexts($contexts, ['languages:language_interface', 'theme']);
    }
    return $contexts;
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
  public function testCollectionFilterAccess() {
    $this->entity->setPublished()->save();
    $this->doTestCollectionFilterAccessForPublishableEntities('info', NULL, 'administer blocks');
  }

}
