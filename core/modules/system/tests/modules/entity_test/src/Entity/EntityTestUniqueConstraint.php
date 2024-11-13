<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\EntityTestAccessControlHandler;
use Drupal\entity_test\EntityTestForm;
use Drupal\entity_test\EntityTestViewBuilder as TestViewBuilder;

/**
 * Defines a test entity class for unique constraint.
 */
#[ContentEntityType(
  id: 'entity_test_unique_constraint',
  label: new TranslatableMarkup('unique field entity'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
  ],
  handlers: [
    'view_builder' => TestViewBuilder::class,
    'access' => EntityTestAccessControlHandler::class,
    'form' => [
      'default' => EntityTestForm::class,
    ],
    'translation' => ContentTranslationHandler::class,
  ],
  base_table: 'entity_test_unique_constraint',
  data_table: 'entity_test_unique_constraint_data',
)]
class EntityTestUniqueConstraint extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['field_test_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('unique_field_test'))
      ->setCardinality(3)
      ->addConstraint('UniqueField');

    $fields['field_test_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('unique_reference_test'))
      ->setCardinality(2)
      ->addConstraint('UniqueField')
      ->setSetting('target_type', 'user');
    return $fields;
  }

}
