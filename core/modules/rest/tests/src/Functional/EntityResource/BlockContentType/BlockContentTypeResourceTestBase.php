<?php

namespace Drupal\Tests\rest\Functional\EntityResource\BlockContentType;

use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\block_content\Entity\BlockContentType;

abstract class BlockContentTypeResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'block_content_type';

  /**
   * @var \Drupal\block_content\Entity\BlockContentTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer blocks']);
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
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'description' => 'Provides a competitive alternative to the "basic" type',
      'id' => 'pascal',
      'label' => 'Pascal',
      'langcode' => 'en',
      'revision' => 0,
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
