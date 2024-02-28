<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional\Rest;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;

abstract class FieldConfigResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'field_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'field_config';

  /**
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer node fields']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);
    $camelids->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_llama',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->save();

    $entity = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'camelids',
    ]);
    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'bundle' => 'camelids',
      'default_value' => [],
      'default_value_callback' => '',
      'dependencies' => [
        'config' => [
          'field.storage.node.field_llama',
          'node.type.camelids',
        ],
        'module' => [
          'text',
        ],
      ],
      'description' => '',
      'entity_type' => 'node',
      'field_name' => 'field_llama',
      'field_type' => 'text',
      'id' => 'node.camelids.field_llama',
      'label' => 'field_llama',
      'langcode' => 'en',
      'required' => FALSE,
      'settings' => [
        'allowed_formats' => [],
      ],
      'status' => TRUE,
      'translatable' => TRUE,
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
  protected function getExpectedCacheContexts() {
    return [
      'user.permissions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer node fields' permission is required.";
  }

}
