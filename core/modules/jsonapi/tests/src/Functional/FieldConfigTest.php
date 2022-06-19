<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * JSON:API integration test for the "FieldConfig" config entity type.
 *
 * @group jsonapi
 */
class FieldConfigTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'field_config';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'field_config--field_config';

  /**
   * {@inheritdoc}
   *
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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/field_config/field_config/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'field_config--field_config',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
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
          'label' => 'field_llama',
          'langcode' => 'en',
          'required' => FALSE,
          'settings' => [],
          'status' => TRUE,
          'translatable' => TRUE,
          'drupal_internal__id' => 'node.camelids.field_llama',
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
    return "The 'administer node fields' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    NodeType::create([
      'name' => 'Pachyderms',
      'type' => 'pachyderms',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_pachyderm',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->save();

    $entity = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'pachyderms',
    ]);
    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected static function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Also clear the 'field_storage_config' entity access handler cache because
    // the 'field_config' access handler delegates access to it.
    // @see \Drupal\field\FieldConfigAccessControlHandler::checkAccess()
    \Drupal::entityTypeManager()->getAccessControlHandler('field_storage_config')->resetCache();
    return parent::entityAccess($entity, $operation, $account);
  }

}
