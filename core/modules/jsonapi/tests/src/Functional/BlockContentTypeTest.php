<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;

/**
 * JSON:API integration test for the "BlockContentType" config entity type.
 *
 * @group jsonapi
 * @group #slow
 */
class BlockContentTypeTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'block_content_type';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'block_content_type--block_content_type';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\block_content\BlockContentTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer block types']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $block_content_type = BlockContentType::create([
      'id' => 'pascal',
      'label' => 'Pascal',
      'revision' => FALSE,
      'description' => 'Provides a competitive alternative to the "basic" type',
    ]);

    $block_content_type->save();

    return $block_content_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/block_content_type/block_content_type/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'block_content_type--block_content_type',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'description' => 'Provides a competitive alternative to the "basic" type',
          'label' => 'Pascal',
          'langcode' => 'en',
          'revision' => 0,
          'status' => TRUE,
          'drupal_internal__id' => 'pascal',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
