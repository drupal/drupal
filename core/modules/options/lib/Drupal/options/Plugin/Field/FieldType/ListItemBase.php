<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListItemBase.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AllowedValuesInterface;

/**
 * Plugin base class inherited by the options field types.
 */
abstract class ListItemBase extends FieldItemBase implements AllowedValuesInterface {

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    // Flatten options firstly, because Possible Options may contain group
    // arrays.
    $flatten_options = \Drupal::formBuilder()->flattenOptions($this->getPossibleOptions($account));
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
    $flatten_options = \Drupal::formBuilder()->flattenOptions($this->getSettableOptions($account));
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
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
     'indexes' => array(
       'value' => array('value'),
     ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    // @todo Move type-specific logic to the type-specific subclass:
    //   https://drupal.org/node/2169983.
    $field_type = $this->getFieldDefinition()->getType();

    $allowed_values = $this->getSetting('allowed_values');
    $allowed_values_function = $this->getSetting('allowed_values_function');

    if (in_array($field_type, array('list_integer', 'list_float', 'list_text'))) {
      $element['allowed_values'] = array(
        '#type' => 'textarea',
        '#title' => t('Allowed values list'),
        '#default_value' => $this->allowedValuesString($allowed_values),
        '#rows' => 10,
        '#element_validate' => array(array($this, 'validateAllowedValues')),
        '#field_has_data' => $has_data,
        '#access' => empty($allowed_values_function),
      );

      $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
      if ($field_type == 'list_integer' || $field_type == 'list_float') {
        $description .= '<br/>' . t('The key is the stored value, and must be numeric. The label will be used in displayed values and edit forms.');
        $description .= '<br/>' . t('The label is optional: if a line contains a single number, it will be used as key and label.');
        $description .= '<br/>' . t('Lists of labels are also accepted (one label per line), only if the field does not hold any values yet. Numeric keys will be automatically generated from the positions in the list.');
      }
      else {
        $description .= '<br/>' . t('The key is the stored value. The label will be used in displayed values and edit forms.');
        $description .= '<br/>' . t('The label is optional: if a line contains a single string, it will be used as key and label.');
      }
      $description .= '</p>';
      $element['allowed_values']['#description'] = $description;
    }
    elseif ($field_type == 'list_boolean') {
      $values = $allowed_values;
      $off_value = array_shift($values);
      $on_value = array_shift($values);

      $element['allowed_values'] = array(
        '#type' => 'value',
        '#description' => '',
        '#value_callback' => 'options_field_settings_form_value_boolean_allowed_values',
        '#access' => empty($allowed_values_function),
      );
      $element['allowed_values']['on'] = array(
        '#type' => 'textfield',
        '#title' => t('On value'),
        '#default_value' => $on_value,
        '#required' => FALSE,
        '#description' => t('If left empty, "1" will be used.'),
        // Change #parents to make sure the element is not saved into field
        // settings.
        '#parents' => array('on'),
      );
      $element['allowed_values']['off'] = array(
        '#type' => 'textfield',
        '#title' => t('Off value'),
        '#default_value' => $off_value,
        '#required' => FALSE,
        '#description' => t('If left empty, "0" will be used.'),
        // Change #parents to make sure the element is not saved into field
        // settings.
        '#parents' => array('off'),
      );

      // Link the allowed value to the on / off elements to prepare for the rare
      // case of an alter changing #parents.
      $element['allowed_values']['#on_parents'] = &$element['allowed_values']['on']['#parents'];
      $element['allowed_values']['#off_parents'] = &$element['allowed_values']['off']['#parents'];

      // Provide additional information about how to format allowed_values
      // of a boolean field for use by a single on/off checkbox widget. Since
      // the widget might not have been selected yet, can be changed independently
      // of this form, and can vary by form mode, we display this information
      // regardless of current widget selection.
      $element['allowed_values']['#description'] .= '<p>' . t("For a 'single on/off checkbox' widget, define the 'off' value first, then the 'on' value in the <strong>Allowed values</strong> section. Note that the checkbox will be labeled with the label of the 'on' value.") . '</p>';
    }

    $element['allowed_values']['#description'] .= '<p>' . t('Allowed HTML tags in labels: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '</p>';

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
   * Element validate callback; check that the entered values are valid.
   */
  public function validateAllowedValues($element, &$form_state) {
    // @todo Move type-specific logic to the type-specific subclass:
    //   https://drupal.org/node/2169983.
    $field_type = $this->getFieldDefinition()->getType();

    $has_data = $element['#field_has_data'];
    $generate_keys = ($field_type == 'list_integer' || $field_type == 'list_float') && !$has_data;

    $values = $this->extractAllowedValues($element['#value'], $generate_keys);

    if (!is_array($values)) {
      \Drupal::formBuilder()->setError($element, $form_state, t('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if ($field_type == 'list_integer' && !preg_match('/^-?\d+$/', $key)) {
          \Drupal::formBuilder()->setError($element, $form_state, t('Allowed values list: keys must be integers.'));
          break;
        }
        if ($field_type == 'list_float' && !is_numeric($key)) {
          \Drupal::formBuilder()->setError($element, $form_state, t('Allowed values list: each key must be a valid integer or decimal.'));
          break;
        }
        elseif ($field_type == 'list_text' && drupal_strlen($key) > 255) {
          \Drupal::formBuilder()->setError($element, $form_state, t('Allowed values list: each key must be a string at most 255 characters long.'));
          break;
        }
      }

      // Prevent removing values currently in use.
      if ($has_data) {
        $lost_keys = array_diff(array_keys($this->getSetting('allowed_values')), array_keys($values));
        if (_options_values_in_use($this->getEntity()->getEntityTypeId(), $this->getFieldDefinition()->getName(), $lost_keys)) {
          \Drupal::formBuilder()->setError($element, $form_state, t('Allowed values list: some values are being removed while currently in use.'));
        }
      }

      form_set_value($element, $values, $form_state);
    }
  }

  /**
   * Parses a string of 'allowed values' into an array.
   *
   * @param string $string
   *   The list of allowed values in string format described in
   *   \Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString().
   * @param bool $generate_keys
   *   Boolean value indicating whether to generate keys based on the position
   *   of the value if a key is not manually specified, and if the value cannot
   *   be used as a key. This should only be TRUE for fields of type
   *   'list_number'.
   *
   * @return array
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString()
   */
  protected function extractAllowedValues($string, $generate_keys) {
    // @todo Move type-specific logic to the type-specific subclass:
    //   https://drupal.org/node/2169983.
    $field_type = $this->getFieldDefinition()->getType();

    $values = array();

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $position => $text) {
      $value = $key = FALSE;

      // Check for an explicit key.
      $matches = array();
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can use the value as the key. Detecting true integer
      // strings takes a little trick.
      elseif ($field_type == 'list_text'
      || ($field_type == 'list_float' && is_numeric($text))
      || ($field_type == 'list_integer' && is_numeric($text) && (float) $text == intval($text))) {
        $key = $value = $text;
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can generate a key from the position.
      elseif ($generate_keys) {
        $key = (string) $position;
        $value = $text;
        $generated_keys = TRUE;
      }
      else {
        return;
      }

      // Float keys are represented as strings and need to be disambiguated
      // ('.5' is '0.5').
      if ($field_type == 'list_float' && is_numeric($key)) {
        $key = (string) (float) $key;
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
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value) && (string) $value !== '0';
  }

}
