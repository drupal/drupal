<?php

namespace Drupal\Tests\image\Functional\Rest;

use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

/**
 * ResourceTestBase for ImageStyle entity.
 */
abstract class ImageStyleResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'image_style';

  /**
   * The ImageStyle entity.
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
  protected function setUpAuthorization($method) {
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
        'anchor' => 'center-center',
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
  protected function getExpectedNormalizedEntity() {
    return [
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
      'name' => 'camelids',
      'status' => TRUE,
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    return "The 'administer image styles' permission is required.";
  }

}
