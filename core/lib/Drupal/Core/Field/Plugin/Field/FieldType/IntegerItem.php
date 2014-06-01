<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'integer' field type.
 *
 * @FieldType(
 *   id = "integer",
 *   label = @Translation("Number (integer)"),
 *   description = @Translation("This field stores a number in the database as an integer."),
 *   default_widget = "number",
 *   default_formatter = "number_integer"
 * )
 */
class IntegerItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'unsigned' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultInstanceSettings() {
    return array(
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
      // Valid size property values include: 'tiny', 'small', 'medium', 'normal'
      // and 'big'.
      'size' => 'normal',
    ) + parent::defaultInstanceSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'));

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
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Range' => array(
            'min' => 0,
            'minMessage' => t('%name: The integer must be larger or equal to %min.', array(
              '%name' => $this->getFieldDefinition()->getLabel(),
              '%min' => 0,
            )),
          ),
        ),
      ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
          // Expose the 'unsigned' setting in the field item schema.
          'unsigned' => $field_definition->getSetting('unsigned'),
          // Expose the 'size' setting in the field item schema. For instance,
          // supply 'big' as a value to produce a 'bigint' type.
          'size' => $field_definition->getSetting('size'),
        ),
      ),
    );
  }

}
