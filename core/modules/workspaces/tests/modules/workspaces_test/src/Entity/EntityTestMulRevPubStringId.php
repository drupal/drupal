<?php

declare(strict_types=1);

namespace Drupal\workspaces_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTestMulRevPub;

/**
 * Defines the test entity class.
 */
#[ContentEntityType(
  id: 'entity_test_mulrevpub_string_id',
  label: new TranslatableMarkup('Test entity - revisions, data table, and published interface'),
  base_table: 'entity_test_mulrevpub_string_id',
  data_table: 'entity_test_mulrevpub_string_id_property_data',
  revision_table: 'entity_test_mulrevpub_string_id_revision',
  revision_data_table: 'entity_test_mulrevpub_string_id_property_revision',
  admin_permission: 'administer entity_test content',
  translatable: TRUE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'revision' => 'revision_id',
    'label' => 'name',
    'langcode' => 'langcode',
    'published' => 'status',
  ]
)]
class EntityTestMulRevPubStringId extends EntityTestMulRevPub {

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
