<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'email' field type.
 *
 * @FieldType(
 *   id = "email",
 *   label = @Translation("Email"),
 *   description = @Translation("An entity field containing an email value."),
 *   default_widget = "email_default",
 *   default_formatter = "basic_string"
 * )
 */
class EmailItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => Email::EMAIL_MAX_LENGTH,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'Length' => [
          'max' => Email::EMAIL_MAX_LENGTH,
          'maxMessage' => t('%name: the email address can not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => Email::EMAIL_MAX_LENGTH]),
        ]
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->name() . '@example.com';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || $this->value === '';
  }

}
