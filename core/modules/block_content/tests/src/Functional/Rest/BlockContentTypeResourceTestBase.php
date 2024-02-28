<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;
use Drupal\block_content\Entity\BlockContentType;

abstract class BlockContentTypeResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'block_content_type';

  /**
   * @var \Drupal\block_content\BlockContentTypeInterface
   */
  protected $entity;

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
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'description' => 'Provides a competitive alternative to the "basic" type',
      'id' => 'pascal',
      'label' => 'Pascal',
      'langcode' => 'en',
      'revision' => FALSE,
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

}
