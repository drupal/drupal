<?php

namespace Drupal\Core\Field;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\FocusFirstCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base class for 'Field widget' plugin implementations.
 *
 * @ingroup field_widget
 */
abstract class WidgetBase extends PluginSettingsBase implements WidgetInterface, ContainerFactoryPluginInterface {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The widget settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin ID for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct([], $plugin_id, $plugin_definition);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    if (!$field_state = static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = [
        'items_count' => count($items),
        'array_parents' => [],
      ];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    // Remove deleted items from the field item list.
    if (isset($field_state['deleted_item']) && $items->get($field_state['deleted_item'])) {
      $items->removeItem($field_state['deleted_item']);
      unset($field_state['deleted_item']);
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    // Collect widget elements.
    $elements = [];

    // If the widget is handling multiple values (e.g Options), or if we are
    // displaying an individual element, just get a single form element and make
    // it the $delta value.
    if ($this->handlesMultipleValues() || isset($get_delta)) {
      $delta = $get_delta ?? 0;
      $element = [
        '#title' => $this->fieldDefinition->getLabel(),
        '#description' => $this->getFilteredDescription(),
      ];
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        if (isset($get_delta)) {
          // If we are processing a specific delta value for a field where the
          // field module handles multiples, set the delta in the result.
          $elements[$delta] = $element;
        }
        else {
          // For fields that handle their own processing, we cannot make
          // assumptions about how the field is structured, just merge in the
          // returned element.
          $elements = $element;
        }
      }
    }
    // If the widget does not handle multiple values itself, (and we are not
    // displaying an individual element), process the multiple value form.
    else {
      $elements = $this->formMultipleElements($items, $form, $form_state);
    }

    // Populate the 'array_parents' information in $form_state->get('field')
    // after the form is built, so that we catch changes in the form structure
    // performed in alter() hooks.
    $elements['#after_build'][] = [static::class, 'afterBuild'];
    $elements['#field_name'] = $field_name;
    $elements['#field_parents'] = $parents;
    // Enforce the structure of submitted values.
    $elements['#parents'] = array_merge($parents, [$field_name]);
    // Most widgets need their internal structure preserved in submitted values.
    $elements += ['#tree' => TRUE];

    $field_widget_complete_form = [
      // Aid in theming of widgets by rendering a classified container.
      '#type' => 'container',
      // Assign a different parent, to keep the main id for the widget itself.
      '#parents' => array_merge($parents, [$field_name . '_wrapper']),
      '#attributes' => [
        'class' => [
          'field--type-' . Html::getClass($this->fieldDefinition->getType()),
          'field--name-' . Html::getClass($field_name),
          'field--widget-' . Html::getClass($this->getPluginId()),
        ],
      ],
      'widget' => $elements,
    ];

    // Allow modules to alter the field widget form element.
    $context = [
      'form' => $form,
      'widget' => $this,
      'items' => $items,
      'default' => $this->isDefaultValueWidget($form_state),
    ];
    \Drupal::moduleHandler()->alter(['field_widget_complete_form', 'field_widget_complete_' . $this->getPluginId() . '_form'], $field_widget_complete_form, $form_state, $context);

    return $field_widget_complete_form;
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $is_unlimited_not_programmed = FALSE;
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_unlimited_not_programmed = !$form_state->isProgrammed();
        break;

      default:
        $max = $cardinality - 1;
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = $this->getFilteredDescription();
    $id_prefix = implode('-', array_merge($parents, [$field_name]));
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];

          // Add 'remove' button, if not working with a programmed form.
          if ($is_unlimited_not_programmed) {
            $remove_button = [
              '#delta' => $delta,
              '#name' => str_replace('-', '_', $id_prefix) . "_{$delta}_remove_button",
              '#type' => 'submit',
              '#value' => $this->t('Remove'),
              '#validate' => [],
              '#submit' => [[static::class, 'deleteSubmit']],
              '#limit_validation_errors' => [],
              '#ajax' => [
                'callback' => [static::class, 'deleteAjax'],
                'wrapper' => $wrapper_id,
                'effect' => 'fade',
              ],
            ];

            $element['_actions'] = [
              'delete' => $remove_button,
              '#weight' => 101,
            ];
          }
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $is_multiple,
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($is_unlimited_not_programmed) {
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [],
          '#submit' => [[static::class, 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [static::class, 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * After-build handler for field elements in a form.
   *
   * This stores the final location of the field within the form structure so
   * that flagErrors() can assign validation errors to the right form element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $parents = $element['#field_parents'];
    $field_name = $element['#field_name'];

    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['array_parents'] = $element['#array_parents'];
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    return $element;
  }

  /**
   * Submission handler for the "Add another item" button.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    // Construct an attribute to add to div for use as selector to set the focus on.
    $button_parent = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $focus_attribute = 'data-drupal-selector="field-' . $button_parent['#field_name'] . '-more-focus-target"';
    $element[$delta]['#prefix'] = '<div class="ajax-new-content" ' . $focus_attribute . '>' . ($element[$delta]['#prefix'] ?? '');
    $element[$delta]['#suffix'] = ($element[$delta]['#suffix'] ?? '') . '</div>';

    // Turn render array into response with AJAX commands.
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $element));
    // Add command to set the focus on first focusable element within the div.
    $response->addCommand(new FocusFirstCommand("[$focus_attribute]"));
    return $response;
  }

  /**
   * Ajax submit callback for the "Remove" button.
   *
   * This re-numbers form elements and removes an item.
   *
   * @param array $form
   *   The form array to remove elements from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function deleteSubmit(&$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $delta = (int) $button['#delta'];
    $array_parents = array_slice($button['#array_parents'], 0, -4);
    $parent_element = NestedArray::getValue($form, array_merge($array_parents, ['widget']));
    $field_name = $parent_element['#field_name'];
    $parents = $parent_element['#field_parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $user_input = $form_state->getUserInput();
    $field_input = NestedArray::getValue($user_input, $parent_element['#parents'], $exists);
    if ($exists) {
      $field_values = [];
      foreach ($field_input as $key => $input) {
        if (is_numeric($key) && $key >= $delta) {
          if ((int) $key === $delta) {
            --$key;
            continue;
          }
        }
        $field_values[$key] = $input;
      }
      NestedArray::setValue($user_input, $parent_element['#parents'], $field_values);
      $form_state->setUserInput($user_input);
    }

    $field_state['deleted_item'] = $delta;

    unset($parent_element[$delta]);
    NestedArray::setValue($form, $array_parents, $parent_element);

    if ($field_state['items_count'] > 0) {
      $field_state['items_count']--;
    }

    $user_input = $form_state->getUserInput();
    $input = NestedArray::getValue($user_input, $parent_element['#parents'], $exists);
    $weight = -1 * $field_state['items_count'];
    foreach ($input as $key => $item) {
      if ($item) {
        $input[$key]['_weight'] = $weight++;
      }
    }
    // Reset indices.
    $input = array_values($input);

    $user_input = $form_state->getUserInput();
    NestedArray::setValue($user_input, $parent_element['#parents'], $input);
    $form_state->setUserInput($user_input);
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax refresh callback for the "Remove" button.
   *
   * This returns the new widget element content to replace
   * the previous content made obsolete by the form submission.
   *
   * @param array $form
   *   The form array to remove elements from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function deleteAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

  /**
   * Generates the form element for a single copy of the widget.
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#field_parents' => $form['#parents'],
      // Only the first widget should be required.
      '#required' => $delta == 0 && $this->fieldDefinition->isRequired(),
      '#delta' => $delta,
      '#weight' => $delta,
    ];

    $element = $this->formElement($items, $delta, $element, $form, $form_state);

    if ($element) {
      // Allow modules to alter the field widget form element.
      $context = [
        'form' => $form,
        'widget' => $this,
        'items' => $items,
        'delta' => $delta,
        'default' => $this->isDefaultValueWidget($form_state),
      ];
      \Drupal::moduleHandler()->alter(['field_widget_single_element_form', 'field_widget_single_element_' . $this->getPluginId() . '_form'], $element, $form_state, $context);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

        usort($values, function ($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
      }

      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);

      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $item->_original_delta ?? $delta;
        unset($item->_original_delta, $item->_weight, $item->_actions);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);

    if ($violations->count()) {
      // Locate the correct element in the form.
      $element = NestedArray::getValue($form_state->getCompleteForm(), $field_state['array_parents']);

      // Do not report entity-level validation errors if Form API errors have
      // already been reported for the field.
      // @todo Field validation should not be run on fields with FAPI errors to
      //   begin with. See https://www.drupal.org/node/2070429.
      $element_path = implode('][', $element['#parents']);
      if ($reported_errors = $form_state->getErrors()) {
        foreach (array_keys($reported_errors) as $error_path) {
          if (str_starts_with($error_path, $element_path)) {
            return;
          }
        }
      }

      // Only set errors if the element is visible.
      if (Element::isVisibleElement($element)) {
        $handles_multiple = $this->handlesMultipleValues();

        $violations_by_delta = $item_list_violations = [];
        foreach ($violations as $violation) {
          $violation = new InternalViolation($violation);
          // Separate violations by delta.
          $property_path = explode('.', $violation->getPropertyPath());
          $delta = array_shift($property_path);
          if (is_numeric($delta)) {
            $violations_by_delta[$delta][] = $violation;
          }
          // Violations at the ItemList level are not associated to any delta.
          else {
            $item_list_violations[] = $violation;
          }
          // @todo Remove BC layer https://www.drupal.org/i/3307859 on PHP 8.2.
          $violation->arrayPropertyPath = $property_path;
        }

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface[] $delta_violations */
        foreach ($violations_by_delta as $delta => $delta_violations) {
          // Pass violations to the main element if this is a multiple-value
          // widget.
          if ($handles_multiple) {
            $delta_element = $element;
          }
          // Otherwise, pass errors by delta to the corresponding sub-element.
          else {
            $original_delta = $field_state['original_deltas'][$delta];
            $delta_element = $element[$original_delta];
          }
          foreach ($delta_violations as $violation) {
            $error_element = $this->errorElement($delta_element, $violation, $form, $form_state);
            if ($error_element !== FALSE) {
              $form_state->setError($error_element, $violation->getMessage());
            }
          }
        }

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface[] $item_list_violations */
        // Pass violations to the main element without going through
        // errorElement() if the violations are at the ItemList level.
        foreach ($item_list_violations as $violation) {
          $form_state->setError($element, $violation->getMessage());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getWidgetState(array $parents, $field_name, FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name));
  }

  /**
   * {@inheritdoc}
   */
  public static function setWidgetState(array $parents, $field_name, FormStateInterface $form_state, array $field_state) {
    NestedArray::setValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name), $field_state);
  }

  /**
   * Returns the location of processing information within $form_state.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The location of processing information within $form_state.
   */
  protected static function getWidgetStateParents(array $parents, $field_name) {
    // Field processing data is placed at
    // $form_state->get(['field_storage', '#parents', ...$parents..., '#fields', $field_name]),
    // to avoid clashes between field names and $parents parts.
    return array_merge(['field_storage', '#parents'], $parents, ['#fields', $field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values;
  }

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings() {
    return $this->fieldDefinition->getSettings();
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getFieldSetting($setting_name) {
    return $this->fieldDefinition->getSetting($setting_name);
  }

  /**
   * Returns whether the widget handles multiple values.
   *
   * @return bool
   *   TRUE if a single copy of formElement() can handle multiple field values,
   *   FALSE if multiple values require separate copies of formElement().
   */
  protected function handlesMultipleValues() {
    $definition = $this->getPluginDefinition();
    return $definition['multiple_values'];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // By default, widgets are available for all fields.
    return TRUE;
  }

  /**
   * Returns whether the widget used for default value form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if a widget used to input default value, FALSE otherwise.
   */
  protected function isDefaultValueWidget(FormStateInterface $form_state) {
    return (bool) $form_state->get('default_value_widget');
  }

  /**
   * Returns the filtered field description.
   *
   * @return \Drupal\Core\Field\FieldFilteredMarkup
   *   The filtered field description, with tokens replaced.
   */
  protected function getFilteredDescription() {
    return FieldFilteredMarkup::create(\Drupal::token()->replace((string) $this->fieldDefinition->getDescription()));
  }

}
