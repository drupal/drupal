<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestForm;

/**
 * Defines a test entity class with UUIDs as IDs.
 */
#[ContentEntityType(
  id: 'entity_test_uuid_id',
  label: new TranslatableMarkup('Test entity with UUIDs as IDs'),
  entity_keys: [
    'id' => 'uuid',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'langcode' => 'langcode',
    'label' => 'name',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
    ],
    'route_provider' => ['html' => DefaultHtmlRouteProvider::class],
  ],
  links: [
    'canonical' => '/entity_test_uuid_id/manage/{entity_test_uuid_id}',
    'add-form' => '/entity_test_uuid_id/add/{type}',
    'add-page' => '/entity_test_uuid_id/add',
    'edit-form' => '/entity_test_uuid_id/manage/{entity_test_uuid_id}/edit',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_uuid_id',
  data_table: 'entity_test_uuid_id_data',
  translatable: TRUE,
)]
class EntityTestUuidId extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // Configure a string field to match the UUID field configuration and use it
    // for both the ID and the UUID key. The UUID field type cannot be used
    // because it would add a unique key to the data table.
    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('UUID'))
      /* @see \Drupal\Core\Field\Plugin\Field\FieldType\UuidItem::defaultStorageSettings() */
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE)
      /* @see \Drupal\Core\Field\Plugin\Field\FieldType\UuidItem::applyDefaultValue() */
      ->setDefaultValueCallback(static::class . '::generateUuid');
    return $fields;
  }

  /**
   * Statically generates a UUID.
   *
   * @return string
   *   A newly generated UUID.
   */
  public static function generateUuid(): string {
    $uuid = \Drupal::service('uuid');
    assert($uuid instanceof UuidInterface);
    return $uuid->generate();
  }

}
