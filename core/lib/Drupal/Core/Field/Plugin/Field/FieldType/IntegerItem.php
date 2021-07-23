<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;

/**
 * Defines the 'integer' field type.
 *
 * @FieldType(
 *   id = "integer",
 *   label = @Translation("Number (integer)"),
 *   description = @Translation("This field stores a number in the database as an integer."),
 *   category = @Translation("Number"),
 *   default_widget = "number",
 *   default_formatter = "number_integer"
 * )
 */
class IntegerItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'unsigned' => FALSE,
      // Valid size property values include: 'tiny', 'small', 'medium', 'normal'
      // and 'big'.
      'size' => 'normal',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'))
      ->setRequired(TRUE);

    // Add reverse references for content entities.
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    if ($entity_type_id && $entity_type_manager->hasDefinition($entity_type_id)) {
      $id_key = $entity_type_manager->getDefinition($entity_type_id)
        ->getKey('id');
      if ($id_key && $id_key == $field_definition->getName()) {
        $properties['referenced_by'] = DataReferenceDefinition::create('entity')
          ->setLabel(new TranslatableMarkup('Referenced by'))
          ->setDescription(new TranslatableMarkup('The referencing entity'))
          ->setComputed(TRUE)
          ->setReadOnly(TRUE)
          ->setTargetDefinition(EntityDataDefinition::create($entity_type_id))
          ->addConstraint('EntityType', $entity_type_id);
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    // If this is an unsigned integer, add a validation constraint for the
    // integer to be positive.
    if ($this->getSetting('unsigned')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'min' => 0,
            'minMessage' => t('%name: The integer must be larger or equal to %min.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '%min' => 0,
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          // Expose the 'unsigned' setting in the field item schema.
          'unsigned' => $field_definition->getSetting('unsigned'),
          // Expose the 'size' setting in the field item schema. For instance,
          // supply 'big' as a value to produce a 'bigint' type.
          'size' => $field_definition->getSetting('size'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $min = $field_definition->getSetting('min') ?: 0;
    $max = $field_definition->getSetting('max') ?: 999;
    $values['value'] = mt_rand($min, $max);
    return $values;
  }

}
