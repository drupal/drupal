<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\StringItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'string' entity field type.
 *
 * @FieldType(
 *   id = "string",
 *   label = @Translation("Text (plain)"),
 *   description = @Translation("A field containing a plain string value."),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class StringItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'max_length' => 255,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // This is called very early by the user entity roles field. Prevent
    // early t() calls by using the TranslationWrapper.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslationWrapper('Text value'));

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
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Length' => array(
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length)),
          ),
        ),
      ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $max = $field_definition->getSetting('max_length');
    $values['value'] = $random->word(mt_rand(1, $max));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    $element['max_length'] = array(
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    );

    return $element;
  }

}
