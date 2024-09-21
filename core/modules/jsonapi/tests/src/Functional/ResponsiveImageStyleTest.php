<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * JSON:API integration test for the "ResponsiveImageStyle" config entity type.
 *
 * @group jsonapi
 */
class ResponsiveImageStyleTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['responsive_image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'responsive_image_style';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'responsive_image_style--responsive_image_style';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\responsive_image\ResponsiveImageStyleInterface
   */
  protected $entity;

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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/responsive_image_style/responsive_image_style/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'responsive_image_style--responsive_image_style',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'breakpoint_group' => 'test_group',
          'dependencies' => [
            'config' => [
              'image.style.large',
              'image.style.medium',
            ],
          ],
          'fallback_image_style' => 'fallback',
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
          'drupal_internal__id' => 'camelids',
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
