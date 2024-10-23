<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;

/**
 * JSON:API integration test for the "ImageStyle" config entity type.
 *
 * @group jsonapi
 */
class ImageStyleTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'image_style';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'image_style--image_style';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $entity;

  /**
   * The effect UUID.
   *
   * @var string
   */
  protected $effectUuid;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['administer image styles']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" image style.
    $camelids = ImageStyle::create([
      'name' => 'camelids',
      'label' => 'Camelids',
    ]);

    // Add an image effect.
    $effect = [
      'id' => 'image_scale_and_crop',
      'data' => [
        'width' => 120,
        'height' => 121,
      ],
      'weight' => 0,
    ];
    $this->effectUuid = $camelids->addImageEffect($effect);

    $camelids->save();

    return $camelids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/image_style/image_style/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'image_style--image_style',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'effects' => [
            $this->effectUuid => [
              'uuid' => $this->effectUuid,
              'id' => 'image_scale_and_crop',
              'weight' => 0,
              'data' => [
                'anchor' => 'center-center',
                'width' => 120,
                'height' => 121,
              ],
            ],
          ],
          'label' => 'Camelids',
          'langcode' => 'en',
          'status' => TRUE,
          'drupal_internal__name' => 'camelids',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument(): array {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
