<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity class for testing default values.
 */
#[ContentEntityType(
  id: 'entity_test_default_value',
  label: new TranslatableMarkup('Test entity for default values'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'langcode' => 'langcode',
  ],
  base_table: 'entity_test_default_value',
)]
class EntityTestDefaultValue extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('shape')
      ->setLabel(t('Some custom description'))
      ->setDefaultValueCallback(static::class . '::descriptionDefaultValue');

    return $fields;
  }

  /**
   * Field default value callback.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being created.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return array
   *   An array of default values, in the same format as the $default_value
   *   property.
   *
   * @see \Drupal\field\Entity\FieldConfig::$default_value
   */
  public static function descriptionDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition): array {
    // Include the field name and entity language in the generated values to
    // check that they are correctly passed.
    $string = $definition->getName() . '_' . $entity->language()->getId();
    // Return a "default value" with multiple items.
    return [
      [
        'shape' => "shape:0:$string",
        'color' => "color:0:$string",
      ],
      [
        'shape' => "shape:1:$string",
        'color' => "color:1:$string",
      ],
    ];
  }

}
