<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListItemBase.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AllowedValuesInterface;

/**
 * Plugin base class inherited by the options field types.
 */
abstract class ListItemBase extends FieldItemBase implements AllowedValuesInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'allowed_values' => array(),
      'allowed_values_function' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    // Flatten options firstly, because Possible Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getPossibleOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    // Flatten options firstly, because Settable Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $allowed_options = options_allowed_values($this->getFieldDefinition(), $this->getEntity());
    return $allowed_options;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->value) && (string) $this->value !== '0';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $allowed_values = $this->getSetting('allowed_values');
    $allowed_values_function = $this->getSetting('allowed_values_function');

    $element['allowed_values'] = array(
      '#type' => 'textarea',
      '#title' => t('Allowed values list'),
      '#default_value' => $this->allowedValuesString($allowed_values),
      '#rows' => 10,
      '#access' => empty($allowed_values_function),
      '#element_validate' => array(array(get_class($this), 'validateAllowedValues')),
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#allowed_values' => $allowed_values,
    );

    $element['allowed_values']['#description'] = $this->allowedValuesDescription();

    $element['allowed_values_function'] = array(
      '#type' => 'item',
      '#title' => t('Allowed values list'),
      '#markup' => t('The value of this field is being determined by the %function function and may not be changed.', array('%function' => $allowed_values_function)),
      '#access' => !empty($allowed_values_function),
      '#value' => $allowed_values_function,
    );

    return $element;
  }

  /**
   * Provides the field type specific allowed values form element #description.
   *
   * @return string
   *   The field type allowed values form specific description.
   */
  abstract protected function allowedValuesDescription();

  /**
   * #element_validate callback for options field allowed values.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see form_process_pattern()
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $values = static::extractAllowedValues($element['#value'], $element['#field_has_data']);

    if (!is_array($values)) {
      $form_state->setError($element, t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if ($error = static::validateAllowedValue($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      // Prevent removing values currently in use.
      if ($element['#field_has_data']) {
        $lost_keys = array_diff(array_keys($element['#allowed_values']), array_keys($values));
        if (_options_values_in_use($element['#entity_type'], $element['#field_name'], $lost_keys)) {
          $form_state->setError($element, t('Allowed values list: some values are being removed while currently in use.'));
        }
      }

      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   * @param bool $has_data
   *   The current field already has data inserted or not.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListTextItem::allowedValuesString()
   */
  protected static function extractAllowedValues($string, $has_data) {
    $values = array();

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = array();
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can use the value as the key.
      elseif (!static::validateAllowedValue($text)) {
        $key = $value = $text;
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can generate a key from the position.
      elseif (!$has_data) {
        $key = (string) $position;
        $value = $text;
        $generated_keys = TRUE;
      }
      else {
        return;
      }

      $values[$key] = $value;
    }

    // We generate keys only if the list contains no explicit key at all.
    if ($explicit_keys && $generated_keys) {
      return;
    }

    return $values;
  }

  /**
   * Checks whether a candidate allowed value is valid.
   *
   * @param string $option
   *   The option value entered by the user.
   *
   * @return string
   *   The error message if the specified value is invalid, NULL otherwise.
   */
  protected static function validateAllowedValue($option) { }

  /**
   * Generates a string representation of an array of 'allowed values'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $values
   *   An array of values, where array keys are values and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "value|label" or "value".
   */
  protected function allowedValuesString($values) {
    $lines = array();
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * @inheritdoc.
   */
  public static function settingsToConfigData(array $settings) {
    if (isset($settings['allowed_values'])) {
      $settings['allowed_values'] = static::structureAllowedValues($settings['allowed_values']);
    }
    return $settings;
  }

  /**
   * @inheritdoc.
   */
  public static function settingsFromConfigData(array $settings) {
    if (isset($settings['allowed_values'])) {
      $settings['allowed_values'] = static::simplifyAllowedValues($settings['allowed_values']);
    }
    return $settings;
  }

  /**
   * Simplifies allowed values to a key-value array from the structured array.
   *
   * @param array $structured_values
   *   Array of items with a 'value' and 'label' key each for the allowed
   *   values.
   *
   * @return array
   *   Allowed values were the array key is the 'value' value, the value is
   *   the 'label' value.
   *
   * @see Drupal\options\Plugin\Field\FieldType\ListItemBase::structureAllowedValues()
   */
  protected static function simplifyAllowedValues(array $structured_values) {
    $values = array();
    foreach ($structured_values as $item) {
      if (is_array($item['label'])) {
        // Nested elements are embedded in the label.
        $item['label'] = static::simplifyAllowedValues($item['label']);
      }
      $values[$item['value']] = $item['label'];
    }
    return $values;
  }

  /**
   * Creates a structured array of allowed values from a key-value array.
   *
   * @param array $values
   *   Allowed values were the array key is the 'value' value, the value is
   *   the 'label' value.
   *
   * @return array
   *   Array of items with a 'value' and 'label' key each for the allowed
   *   values.
   *
   * @see Drupal\options\Plugin\Field\FieldType\ListItemBase::simplifyAllowedValues()
   */
  protected static function structureAllowedValues(array $values) {
    $structured_values = array();
    foreach ($values as $value => $label) {
      if (is_array($label)) {
        $label = static::structureAllowedValues($label);
      }
      $structured_values[] = array(
        'value' => $value,
        'label' => $label,
      );
    }
    return $structured_values;
  }

}
