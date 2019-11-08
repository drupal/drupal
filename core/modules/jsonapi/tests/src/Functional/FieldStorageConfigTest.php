<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * JSON:API integration test for the "FieldStorageConfig" config entity type.
 *
 * @group jsonapi
 */
class FieldStorageConfigTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'field_storage_config';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'field_storage_config--field_storage_config';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\field\FieldConfigStorage
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
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'true_llama',
      'entity_type' => 'node',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    return $field_storage;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/field_storage_config/field_storage_config/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'field_storage_config--field_storage_config',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'cardinality' => 1,
          'custom_storage' => FALSE,
          'dependencies' => [
            'module' => [
              'node',
            ],
          ],
          'entity_type' => 'node',
          'field_name' => 'true_llama',
          'indexes' => [],
          'langcode' => 'en',
          'locked' => FALSE,
          'module' => 'core',
          'persist_with_no_fields' => FALSE,
          'settings' => [],
          'status' => TRUE,
          'translatable' => TRUE,
          'field_storage_config_type' => 'boolean',
          'drupal_internal__id' => 'node.true_llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer node fields' permission is required.";
  }

}
