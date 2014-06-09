<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\EmailItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'email' field type.
 *
 * @FieldType(
 *   id = "email",
 *   label = @Translation("Email"),
 *   description = @Translation("An entity field containing an email value."),
 *   default_widget = "email_default",
 *   default_formatter = "string"
 * )
 */
class EmailItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('email')
      ->setLabel(t('Email value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => EMAIL_MAX_LENGTH,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', array(
      'value' => array(
        'Length' => array(
          'max' => EMAIL_MAX_LENGTH,
          'maxMessage' => t('%name: the email address can not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@max' => EMAIL_MAX_LENGTH)),
        )
      ),
    ));

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || $this->value === '';
  }

}
