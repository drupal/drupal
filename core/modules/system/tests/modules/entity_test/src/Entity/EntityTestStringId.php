<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestForm;

/**
 * Defines a test entity class with a string ID.
 */
#[ContentEntityType(
  id: 'entity_test_string_id',
  label: new TranslatableMarkup('Test entity with string_id'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
  ],
  handlers: [
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'canonical' => '/entity_test_string_id/manage/{entity_test_string_id}',
    'add-form' => '/entity_test_string_id/add',
    'edit-form' => '/entity_test_string_id/manage/{entity_test_string_id}',
  ],
  admin_permission: 'administer entity_test content',
  base_table: 'entity_test_string',
  field_ui_base_route: 'entity.entity_test_string_id.admin_form',
)]
class EntityTestStringId extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the test entity.'))
      ->setReadOnly(TRUE)
      // In order to work around the InnoDB 191 character limit on utf8mb4
      // primary keys, we set the character set for the field to ASCII.
      ->setSetting('is_ascii', TRUE);
    return $fields;
  }

}
