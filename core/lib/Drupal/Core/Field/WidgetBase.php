<?php

/**
 * @file
 * Contains \Drupal\Core\Field\WidgetBase.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Component\Utility\String;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Base class for 'Field widget' plugin implementations.
 */
abstract class WidgetBase extends PluginSettingsBase implements WidgetInterface {

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
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   */
  public function __construct($plugin_id, array $plugin_definition, FieldDefinitionInterface $field_definition, array $settings) {
    parent::__construct(array(), $plugin_id, $plugin_definition);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, array &$form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Store field information in $form_state.
    if (!field_form_get_state($parents, $field_name, $form_state)) {
      $field_state = array(
        'items_count' => count($items),
        'array_parents' => array(),
        'constraint_violations' => array(),
      );
      field_form_set_state($parents, $field_name, $form_state, $field_state);
    }

    // Collect widget elements.
    $elements = array();

    // If the widget is handling multiple values (e.g Options), or if we are
    // displaying an individual element, just get a single form element and make
    // it the $delta value.
    if ($this->handlesMultipleValues() || isset($get_delta)) {
      $delta = isset($get_delta) ? $get_delta : 0;
      $element = array(
        '#title' => String::checkPlain($this->fieldDefinition->getLabel()),
        '#description' => field_filter_xss(\Drupal::token()->replace($this->fieldDefinition->getDescription())),
      );
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

    // Populate the 'array_parents' information in $form_state['field'] after
    // the form is built, so that we catch changes in the form structure performed
    // in alter() hooks.
    $elements['#after_build'][] = 'field_form_element_after_build';
    $elements['#field_name'] = $field_name;
    $elements['#field_parents'] = $parents;
    // Enforce the structure of submitted values.
    $elements['#parents'] = array_merge($parents, array($field_name));
    // Most widgets need their internal structure preserved in submitted values.
    $elements += array('#tree' => TRUE);

    return array(
      // Aid in theming of widgets by rendering a classified container.
      '#type' => 'container',
      // Assign a different parent, to keep the main id for the widget itself.
      '#parents' => array_merge($parents, array($field_name . '_wrapper')),
      '#attributes' => array(
        'class' => array(
          'field-type-' . drupal_html_class($this->fieldDefinition->getType()),
          'field-name-' . drupal_html_class($field_name),
          'field-widget-' . drupal_html_class($this->getPluginId()),
        ),
      ),
      '#access' => $items->access('edit'),
      'widget' => $elements,
    );
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, array &$form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = field_form_get_state($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $id_prefix = implode('-', array_merge($parents, array($field_name)));
    $wrapper_id = drupal_html_id($id_prefix . '-add-more-wrapper');

    $title = String::checkPlain($this->fieldDefinition->getLabel());
    $description = field_filter_xss(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();

    for ($delta = 0; $delta <= $max; $delta++) {
      // For multiple fields, title and description are handled by the wrapping
      // table.
      $element = array(
        '#title' => $is_multiple ? '' : $title,
        '#description' => $is_multiple ? '' : $description,
      );
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
            '#title' => t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldDefinitionInterface::CARDINALITY_UNLIMITED && empty($form_state['programmed'])) {
        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array(array(get_class($this), 'addMoreSubmit')),
          '#ajax' => array(
            'callback' => array(get_class($this), 'addMoreAjax'),
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ),
        );
      }
    }

    return $elements;
  }

  /**
   * Submission handler for the "Add another item" button.
   */
  public static function addMoreSubmit(array $form, array &$form_state) {
    $button = $form_state['triggering_element'];

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = field_form_get_state($parents, $field_name, $form_state);
    $field_state['items_count']++;
    field_form_set_state($parents, $field_name, $form_state, $field_state);

    $form_state['rebuild'] = TRUE;
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMoreAjax(array $form, array $form_state) {
    $button = $form_state['triggering_element'];

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] != FieldDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Generates the form element for a single copy of the widget.
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $entity = $items->getEntity();

    $element += array(
      '#entity_type' => $entity->getEntityTypeId(),
      '#bundle' => $entity->bundle(),
      '#entity' => $entity,
      '#field_name' => $this->fieldDefinition->getName(),
      '#language' => $items->getLangcode(),
      '#field_parents' => $form['#parents'],
      // Only the first widget should be required.
      '#required' => $delta == 0 && $this->fieldDefinition->isRequired(),
      '#delta' => $delta,
      '#weight' => $delta,
    );

    $element = $this->formElement($items, $delta, $element, $form, $form_state);

    if ($element) {
      // Allow modules to alter the field widget form element.
      $context = array(
        'form' => $form,
        'widget' => $this,
        'items' => $items,
        'delta' => $delta,
        'default' => !empty($entity->field_ui_default_value),
      );
      \Drupal::moduleHandler()->alter(array('field_widget_form', 'field_widget_' . $this->getPluginId() . '_form'), $element, $form_state, $context);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, array &$form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state['values'].
    $path = array_merge($form['#parents'], array($field_name));
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state['values'], $path, $key_exists);

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the corect form element.
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
      $field_state = field_form_get_state($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
      }
      field_form_set_state($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, array $form, array &$form_state) {
    $field_name = $this->fieldDefinition->getName();

    $field_state = field_form_get_state($form['#parents'], $field_name, $form_state);

    if (!empty($field_state['constraint_violations'])) {
      $form_builder = \Drupal::formBuilder();

      // Locate the correct element in the the form.
      $element = NestedArray::getValue($form_state['complete_form'], $field_state['array_parents']);

      // Do not report entity-level validation errors if Form API errors have
      // already been reported for the field.
      // @todo Field validation should not be run on fields with FAPI errors to
      //   begin with. See https://drupal.org/node/2070429.
      $element_path = implode('][', $element['#parents']);
      if ($reported_errors = $form_builder->getErrors($form_state)) {
        foreach (array_keys($reported_errors) as $error_path) {
          if (strpos($error_path, $element_path) === 0) {
            return;
          }
        }
      }

      // Only set errors if the element is accessible.
      if (!isset($element['#access']) || $element['#access']) {
        $handles_multiple = $this->handlesMultipleValues();

        $violations_by_delta = array();
        foreach ($field_state['constraint_violations'] as $violation) {
          // Separate violations by delta.
          $property_path = explode('.', $violation->getPropertyPath());
          $delta = array_shift($property_path);
          // Violations at the ItemList level are not associated to any delta,
          // we file them under $delta NULL.
          $delta = is_numeric($delta) ? $delta : NULL;

          $violations_by_delta[$delta][] = $violation;
          $violation->arrayPropertyPath = $property_path;
        }

        foreach ($violations_by_delta as $delta => $delta_violations) {
          // Pass violations to the main element:
          // - if this is a multiple-value widget,
          // - or if the violations are at the ItemList level.
          if ($handles_multiple || $delta === NULL) {
            $delta_element = $element;
          }
          // Otherwise, pass errors by delta to the corresponding sub-element.
          else {
            $original_delta = $field_state['original_deltas'][$delta];
            $delta_element = $element[$original_delta];
          }
          foreach ($delta_violations as $violation) {
            // @todo: Pass $violation->arrayPropertyPath as property path.
            $error_element = $this->errorElement($delta_element, $violation, $form, $form_state);
            if ($error_element !== FALSE) {
              $form_builder->setError($error_element, $form_state, $violation->getMessage());
            }
          }
        }
        // Reinitialize the errors list for the next submit.
        $field_state['constraint_violations'] = array();
        field_form_set_state($form['#parents'], $field_name, $form_state, $field_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, array &$form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
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

}
