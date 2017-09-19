<?php

namespace Drupal\Tests\rest\Functional\EntityResource\MediaType;

use Drupal\media\Entity\MediaType;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class MediaTypeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['media'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'media_type';

  /**
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer media types']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" media type.
    $camelids = MediaType::create([
      'name' => 'Camelids',
      'id' => 'camelids',
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
      'source' => 'file',
    ]);

    $camelids->save();

    return $camelids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
      'field_map' => [],
      'id' => 'camelids',
      'label' => NULL,
      'langcode' => 'en',
      'new_revision' => FALSE,
      'queue_thumbnail_downloads' => FALSE,
      'source' => 'file',
      'source_configuration' => [
        'source_field' => '',
      ],
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

}
