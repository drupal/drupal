<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional\Rest;

use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

/**
 * ResourceTestBase for ImageStyle entity.
 */
abstract class ImageStyleResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

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
   * Marks some tests as skipped because XML cannot be deserialized.
   *
   * @before
   */
  public function imageStyleResourceTestBaseSkipTests(): void {
    if ($this->name() === 'testGet' && static::$format === 'xml') {
      // @todo Remove this method override in https://www.drupal.org/node/2905655
      $this->markTestSkipped('XML encoder does not support UUIDs as keys: makes ImageStyle config entity XML serialization crash');
    }
  }

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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer image styles' permission is required.";
  }

}
