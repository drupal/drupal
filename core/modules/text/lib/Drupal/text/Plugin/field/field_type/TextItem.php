<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\field_type\TextItem.
 */

namespace Drupal\text\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Core\Entity\Field;

/**
 * Plugin implementation of the 'text' field type.
 *
 * @FieldType(
 *   id = "text",
 *   label = @Translation("Text"),
 *   description = @Translation("This field stores varchar text in the database."),
 *   settings = {
 *     "max_length" = "255"
 *   },
 *   instance_settings = {
 *     "text_processing" = "0"
 *   },
 *   default_widget = "text_textfield",
 *   default_formatter = "text_default"
 * )
 */
class TextItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(Field $field) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => $field->settings['max_length'],
          'not null' => FALSE,
        ),
        'format' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'format' => array('format'),
      ),
      'foreign keys' => array(
        'format' => array(
          'table' => 'filter_format',
          'columns' => array('format' => 'format'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedData()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    if ($max_length = $this->getFieldDefinition()->getFieldSetting('max_length')) {
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'value' => array(
          'Length' => array(
            'max' => $max_length,
            'maxMessage' => t('%name: the text may not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getFieldLabel(), '@max' => $max_length)),
          )
        ),
      ));
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = array();
    $field = $this->getInstance()->getField();

    $element['max_length'] = array(
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $field->settings['max_length'],
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      // @todo: If $has_data, add a validate handler that only allows
      // max_length to increase.
      '#disabled' => $field->hasData(),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    $element['text_processing'] = array(
      '#type' => 'radios',
      '#title' => t('Text processing'),
      '#default_value' => $this->getInstance()->settings['text_processing'],
      '#options' => array(
        t('Plain text'),
        t('Filtered text (user selects text format)'),
      ),
    );

    return $element;
  }

}
