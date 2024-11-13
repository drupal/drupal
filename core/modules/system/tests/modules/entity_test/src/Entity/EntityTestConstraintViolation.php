<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\EntityTestForm;

/**
 * Defines the test entity class for testing entity constraint violations.
 */
#[ContentEntityType(
  id: 'entity_test_constraint_violation',
  label: new TranslatableMarkup('Test entity constraint violation'),
  persistent_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'label' => 'name',
  ],
  handlers: [
    'form' => [
      'default' => EntityTestForm::class,
    ],
  ],
  base_table: 'entity_test_constraint_violation',
)]
class EntityTestConstraintViolation extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name']->setDisplayOptions('form', [
      'type' => 'string',
      'weight' => 0,
    ]);
    $fields['name']->addConstraint('FieldWidgetConstraint', []);

    // Add a field that uses a widget with a custom implementation for
    // \Drupal\Core\Field\WidgetInterface::errorElement().
    $fields['test_field'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Test field'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->addConstraint('FieldWidgetConstraint', []);

    return $fields;
  }

}
