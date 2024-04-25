<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'string' entity field type.
 */
#[FieldType(
  id: "string",
  label: new TranslatableMarkup("Text (plain)"),
  description: [
    new TranslatableMarkup("Ideal for titles and names"),
    new TranslatableMarkup("Efficient storage for short text"),
    new TranslatableMarkup("Requires specifying a maximum length"),
    new TranslatableMarkup("Good for fields with known or predictable length"),
  ],
  category: "plain_text",
  default_widget: "string_textfield",
  default_formatter: "string"
)]
class StringItem extends StringItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'is_ascii' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => $this->t('%name: may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $max_length = $field_definition->getSetting('max_length');

    // When the maximum length is less than 15, or the field needs to be unique,
    // generate a random word using the maximum length.
    if ($max_length <= 15 || $field_definition->getConstraint('UniqueField')) {
      $values['value'] = ucfirst($random->word($max_length));

      return $values;
    }

    // The minimum length is either 10% of the maximum length, or 15 characters
    // long, whichever is greater.
    $min_length = max(ceil($max_length * 0.10), 15);

    // Reduce the max length to allow us to add a period.
    $max_length -= 1;

    // The random value is generated multiple times to create a slight
    // preference towards values that are closer to the minimum length of the
    // string. For values larger than 255 (which is the default maximum value),
    // the bias towards minimum length is increased. This is because the default
    // maximum length of 255 is often used for fields that include shorter
    // values (i.e. title).
    $length = mt_rand($min_length, mt_rand($min_length, $max_length >= 255 ? mt_rand($min_length, $max_length) : $max_length));

    $string = $random->sentences(1);
    while (mb_strlen($string) < $length) {
      $string .= " {$random->sentences(1)}";
    }

    if (mb_strlen($string) > $max_length) {
      $string = substr($string, 0, $length);
      $string = substr($string, 0, strrpos($string, ' '));
    }

    $string = rtrim($string, ' .');

    // Ensure that the string ends with a full stop if there are multiple
    // sentences.
    $values['value'] = $string . (str_contains($string, '.') ? '.' : '');

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => $this->t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $element;
  }

}
