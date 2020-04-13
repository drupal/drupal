<?php

namespace Drupal\Tests\tour\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\tour\Entity\Tour;

abstract class TourResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tour'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'tour';

  /**
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
          'attributes' => [
            'data-id' => 'tour-llama-1',
          ],
        ],
      ],
    ]);
    $tour->save();

    return $tour;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'id' => 'tour-llama',
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
          'attributes' => [
            'data-id' => 'tour-llama-1',
          ],
        ],
      ],
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
  protected function getExpectedCacheContexts() {
    return [
      'user.permissions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The following permissions are required: 'access tour' OR 'administer site configuration'.";
  }

}
