<?php

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\FocusFirstCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Plugin base class inherited by the options field types.
 */
abstract class ListItemBase extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values' => [],
      'allowed_values_function' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(?AccountInterface $account = NULL) {
    // Flatten options firstly, because Possible Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getPossibleOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(?AccountInterface $account = NULL) {
    // Flatten options firstly, because Settable Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(?AccountInterface $account = NULL) {
    $allowed_options = options_allowed_values($this->getFieldDefinition()->getFieldStorageDefinition(), $this->getEntity());
    return $allowed_options;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $allowed_options = options_allowed_values($field_definition->getFieldStorageDefinition());
    if (empty($allowed_options)) {
      $values['value'] = NULL;
      return $values;
    }
    $values['value'] = array_rand($allowed_options);
    return $values;
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
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    if (!array_key_exists('allowed_values', $form_state->getStorage())) {
      $form_state->set('allowed_values', $this->getFieldDefinition()->getSetting('allowed_values'));
    }
    $form['field_storage_submit']['#submit'][] = [static::class, 'submitFieldStorageUpdate'];
    $form['field_storage_submit']['#limit_validation_errors'] = [];

    $allowed_values = $form_state->getStorage()['allowed_values'];
    $allowed_values_function = $this->getSetting('allowed_values_function');

    if (!$form_state->get('items_count')) {
      $form_state->set('items_count', max(count($allowed_values), 0));
    }

    $wrapper_id = Html::getUniqueId('allowed-values-wrapper');
    $element['allowed_values'] = [
      '#element_validate' => [[static::class, 'validateAllowedValues']],
      '#field_has_data' => $has_data,
      '#allowed_values' => $allowed_values,
      '#required' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#access' => empty($allowed_values_function),
      'help_text' => ['#markup' => $this->allowedValuesDescription()],
    ];
    $element['allowed_values']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Allowed values'),
        $this->t('Delete'),
        $this->t('Weight'),
      ],
      '#attributes' => [
        'id' => 'allowed-values-order',
        'data-field-list-table' => TRUE,
        'class' => ['allowed-values-table'],
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.fieldListKeyboardNavigation',
          'field_ui/drupal.field_ui',
        ],
      ],
    ];

    $max = $form_state->get('items_count');
    $entity_type_id = $this->getFieldDefinition()->getTargetEntityTypeId();
    $field_name = $this->getFieldDefinition()->getName();
    $current_keys = array_keys($allowed_values);
    for ($delta = 0; $delta <= $max; $delta++) {
      $element['allowed_values']['table'][$delta] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        '#weight' => $delta,
      ];
      $element['allowed_values']['table'][$delta]['item'] = [
        'label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Name'),
          '#weight' => -30,
          '#default_value' => isset($current_keys[$delta]) ? $allowed_values[$current_keys[$delta]] : '',
          '#required' => $delta === 0,
        ],
        'key' => [
          '#type' => 'textfield',
          '#maxlength' => 255,
          '#title' => $this->t('Value'),
          '#default_value' => $current_keys[$delta] ?? '',
          '#weight' => -20,
          '#required' => $delta === 0,
        ],
      ];
      $element['allowed_values']['table'][$delta]['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => "remove_row_button__$delta",
        '#id' => "remove_row_button__$delta",
        '#delta' => $delta,
        '#submit' => [[static::class, 'deleteSubmit']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [static::class, 'deleteAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];
      $element['allowed_values']['table'][$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => 0,
        '#attributes' => ['class' => ['weight']],
      ];
      // Disable the remove button if there is only one row in the table.
      if ($max === 0) {
        $element['allowed_values']['table'][0]['delete']['#attributes']['disabled'] = 'disabled';
      }
      if ($delta < count($allowed_values)) {
        $query = \Drupal::entityQuery($entity_type_id)
          ->accessCheck(FALSE)
          ->condition($field_name, $current_keys[$delta]);
        $entity_ids = $query->execute();
        if (!empty($entity_ids)) {
          $element['allowed_values']['table'][$delta]['item']['key']['#attributes']['disabled'] = 'disabled';
          $element['allowed_values']['table'][$delta]['delete']['#attributes']['disabled'] = 'disabled';
          $element['allowed_values']['table'][$delta]['delete'] += [
            'message' => [
              '#type' => 'item',
              '#markup' => $this->t('Cannot be removed: option in use.'),
            ],
          ];
        }
      }
    }
    $element['allowed_values']['table']['#max_delta'] = $max;
    $element['allowed_values']['add_more_allowed_values'] = [
      '#type' => 'submit',
      '#name' => 'add_more_allowed_values',
      '#value' => $this->t('Add another item'),
      '#attributes' => [
        'class' => ['field-add-more-submit'],
        'data-field-list-button' => TRUE,
      ],
      // Allow users to add another row without requiring existing rows to have
      // values.
      '#limit_validation_errors' => [],
      '#submit' => [[static::class, 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'addMoreAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Adding a new item...'),
        ],
      ],
    ];

    $element['allowed_values_function'] = [
      '#type' => 'item',
      '#title' => $this->t('Allowed values list'),
      '#markup' => $this->t('The value of this field is being determined by the %function function and may not be changed.', ['%function' => $allowed_values_function]),
      '#access' => !empty($allowed_values_function),
      '#value' => $allowed_values_function,
    ];

    return $element;
  }

  /**
   * Adds a new option.
   *
   * @param array $form
   *   The form array to add elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $form_state->set('items_count', $form_state->get('items_count') + 1);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $delta = $element['table']['#max_delta'];
    $element['table'][$delta]['item']['#prefix'] = '<div class="ajax-new-content" data-drupal-selector="field-list-add-more-focus-target">' . ($element['table'][$delta]['item']['#prefix'] ?? '');
    $element['table'][$delta]['item']['#suffix'] = ($element['table'][$delta]['item']['#suffix'] ?? '') . '</div>';
    // Enable the remove button for the first row if there are more rows.
    if ($delta > 0 && isset($element['table'][0]['delete']['#attributes']['disabled']) && !isset($element['table'][0]['item']['key']['#attributes']['disabled'])) {
      unset($element['table'][0]['delete']['#attributes']['disabled']);
    }

    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $element));
    $response->addCommand(new FocusFirstCommand('[data-drupal-selector="field-list-add-more-focus-target"]'));

    return $response;
  }

  /**
   * Deletes a row/option.
   *
   * @param array $form
   *   The form array to add elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function deleteSubmit(array $form, FormStateInterface $form_state) {
    $allowed_values = $form_state->getStorage()['allowed_values'];
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $item_to_be_removed = $element['item']['label']['#default_value'];
    $remaining_allowed_values = array_diff($allowed_values, [$item_to_be_removed]);
    $form_state->set('allowed_values', $remaining_allowed_values);

    // The user input is directly modified to preserve the rest of the data on
    // the page as it cannot be rebuilt from a fresh form state.
    $user_input = $form_state->getUserInput();
    NestedArray::unsetValue($user_input, $element['#parents']);

    // Reset the keys in the array.
    $table_parents = $element['#parents'];
    array_pop($table_parents);
    $new_values = array_values(NestedArray::getValue($user_input, $table_parents));
    NestedArray::setValue($user_input, $table_parents, $new_values);

    $form_state->setUserInput($user_input);
    $form_state->set('items_count', $form_state->get('items_count') - 1);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for per row delete button.
   */
  public static function deleteAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElementBase::processPattern()
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $items = array_filter(array_map(function ($item) use ($element) {
      $current_element = $element['table'][$item];
      if ($current_element['item']['key']['#value'] !== NULL && $current_element['item']['label']['#value']) {
        return $current_element['item']['key']['#value'] . '|' . $current_element['item']['label']['#value'];
      }
      elseif ($current_element['item']['key']['#value']) {
        return $current_element['item']['key']['#value'];
      }
      elseif ($current_element['item']['label']['#value']) {
        return $current_element['item']['label']['#value'];
      }

      return NULL;
    }, Element::children($element['table'])), function ($item) {
      return $item;
    });
    if ($reordered_items = $form_state->getValue([...$element['#parents'], 'table'])) {
      uksort($items, function ($a, $b) use ($reordered_items) {
        $a_weight = $reordered_items[$a]['weight'] ?? 0;
        $b_weight = $reordered_items[$b]['weight'] ?? 0;
        return $a_weight <=> $b_weight;
      });
    }
    $values = static::extractAllowedValues($items, $element['#field_has_data']);

    if (!is_array($values)) {
      $form_state->setError($element, new TranslatableMarkup('Allowed values list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($values as $key => $value) {
        if ($error = static::validateAllowedValue($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string|array $list
   *   The raw string or array to extract values from.
   * @param bool $has_data
   *   The current field already has data inserted or not.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString()
   */
  protected static function extractAllowedValues($list, $has_data) {
    $values = [];

    if (is_string($list)) {
      trigger_error('Passing a string to ' . __METHOD__ . '() is deprecated in drupal:10.2.0 and will cause an error from drupal:11.0.0. Use an array instead. See https://www.drupal.org/node/3376368', E_USER_DEPRECATED);
      $list = explode("\n", $list);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
    }

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
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
  protected static function validateAllowedValue($option) {}

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
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    if (isset($settings['allowed_values'])) {
      $settings['allowed_values'] = static::structureAllowedValues($settings['allowed_values']);
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsFromConfigData(array $settings) {
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
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::structureAllowedValues()
   */
  protected static function simplifyAllowedValues(array $structured_values) {
    $values = [];
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
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::simplifyAllowedValues()
   */
  protected static function structureAllowedValues(array $values) {
    $structured_values = [];
    foreach ($values as $value => $label) {
      if (is_array($label)) {
        $label = static::structureAllowedValues($label);
      }
      $structured_values[] = [
        'value' => static::castAllowedValue($value),
        'label' => $label,
      ];
    }
    return $structured_values;
  }

  /**
   * Converts a value to the correct type.
   *
   * @param mixed $value
   *   The value to cast.
   *
   * @return mixed
   *   The casted value.
   */
  protected static function castAllowedValue($value) {
    return $value;
  }

  /**
   * Resets the static variable on field storage update.
   */
  public static function submitFieldStorageUpdate() {
    drupal_static_reset('options_allowed_values');
  }

}
