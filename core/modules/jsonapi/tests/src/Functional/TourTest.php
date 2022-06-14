<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\tour\Entity\Tour;

/**
 * JSON:API integration test for the "Tour" config entity type.
 *
 * @group jsonapi
 */
class TourTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tour'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'tour';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'tour--tour';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\tour\TourInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['access tour']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $tour = Tour::create([
      'id' => 'tour-llama',
      'label' => 'Llama tour',
      'langcode' => 'en',
      'module' => 'tour',
      'routes' => [
        [
          'route_name' => '<front>',
        ],
      ],
      'tips' => [
        'tour-llama-1' => [
          'id' => 'tour-llama-1',
          'plugin' => 'text',
          'label' => 'Llama',
          'body' => 'Who handle the awesomeness of llamas?',
          'weight' => 100,
          'selector' => '#tour-llama-1',
        ],
      ],
    ]);
    $tour->save();

    return $tour;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/tour/tour/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'tour--tour',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'label' => 'Llama tour',
          'langcode' => 'en',
          'module' => 'tour',
          'routes' => [
            [
              'route_name' => '<front>',
            ],
          ],
          'status' => TRUE,
          'tips' => [
            'tour-llama-1' => [
              'id' => 'tour-llama-1',
              'plugin' => 'text',
              'label' => 'Llama',
              'body' => 'Who handle the awesomeness of llamas?',
              'weight' => 100,
              'selector' => '#tour-llama-1',
            ],
          ],
          'drupal_internal__id' => 'tour-llama',
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

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The following permissions are required: 'access tour' OR 'administer site configuration'.";
  }

}
