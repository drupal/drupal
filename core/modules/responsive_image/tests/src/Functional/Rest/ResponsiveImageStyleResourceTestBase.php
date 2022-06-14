<?php

namespace Drupal\Tests\responsive_image\Functional\Rest;

use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

/**
 * ResourceTestBase for ResponsiveImageStyle entity.
 */
abstract class ResponsiveImageStyleResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['responsive_image'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'responsive_image_style';

  /**
   * The ResponsiveImageStyle entity.
   *
   * @var \Drupal\responsive_image\ResponsiveImageStyleInterface
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
    $this->grantPermissionsToTestedRole(['administer responsive images']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" responsive image style.
    $camelids = ResponsiveImageStyle::create([
      'id' => 'camelids',
      'label' => 'Camelids',
    ]);
    $camelids->setBreakpointGroup('test_group');
    $camelids->setFallbackImageStyle('fallback');
    $camelids->addImageStyleMapping('test_breakpoint', '1x', [
      'image_mapping_type' => 'image_style',
      'image_mapping' => 'small',
    ]);
    $camelids->addImageStyleMapping('test_breakpoint', '2x', [
      'image_mapping_type' => 'sizes',
      'image_mapping' => [
        'sizes' => '(min-width:700px) 700px, 100vw',
        'sizes_image_styles' => [
          'medium' => 'medium',
          'large' => 'large',
        ],
      ],
    ]);
    $camelids->save();

    return $camelids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'breakpoint_group' => 'test_group',
      'dependencies' => [
        'config' => [
          'image.style.large',
          'image.style.medium',
        ],
      ],
      'fallback_image_style' => 'fallback',
      'id' => 'camelids',
      'image_style_mappings' => [
        0 => [
          'breakpoint_id' => 'test_breakpoint',
          'image_mapping' => 'small',
          'image_mapping_type' => 'image_style',
          'multiplier' => '1x',
        ],
        1 => [
          'breakpoint_id' => 'test_breakpoint',
          'image_mapping' => [
            'sizes' => '(min-width:700px) 700px, 100vw',
            'sizes_image_styles' => [
              'large' => 'large',
              'medium' => 'medium',
            ],
          ],
          'image_mapping_type' => 'sizes',
          'multiplier' => '2x',
        ],
      ],
      'label' => 'Camelids',
      'langcode' => 'en',
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
    return "The 'administer responsive images' permission is required.";
  }

}
