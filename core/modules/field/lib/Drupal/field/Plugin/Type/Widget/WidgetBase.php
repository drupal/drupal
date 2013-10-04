<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\Widget\WidgetBase.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\field\FieldInstanceInterface;
use Drupal\field\Plugin\PluginSettingsBase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Base class for 'Field widget' plugin implementations.
 */
abstract class WidgetBase extends PluginSettingsBase implements WidgetInterface {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Entity\Field\FieldDefinitionInterface
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
   * @param \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition
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
    $field_name = $this->fieldDefinition->getFieldName();
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
    $definition = $this->getPluginDefinition();
    if (isset($get_delta) || $definition['multiple_values']) {
      $delta = isset($get_delta) ? $get_delta : 0;
      $element = array(
        '#title' => check_plain($this->fieldDefinition->getFieldLabel()),
        '#description' => field_filter_xss(\Drupal::token()->replace($this->fieldDefinition->getFieldDescription())),
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

    $return = array(
      $field_name => array(
        // Aid in theming of widgets by rendering a classified container.
        '#type' => 'container',
        // Assign a different parent, to keep the main id for the widget itself.
        '#parents' => array_merge($parents, array($field_name . '_wrapper')),
        '#attributes' => array(
          'class' => array(
            'field-type-' . drupal_html_class($this->fieldDefinition->getFieldType()),
            'field-name-' . drupal_html_class($field_name),
            'field-widget-' . drupal_html_class($this->getPluginId()),
          ),
        ),
        '#access' => $items->access('edit'),
        'widget' => $elements,
      ),
    );

    return $return;
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
    $field_name = $this->fieldDefinition->getFieldName();
    $cardinality = $this->fieldDefinition->getFieldCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FIELD_CARDINALITY_UNLIMITED:
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

    $title = check_plain($this->fieldDefinition->getFieldLabel());
    $description = field_filter_xss(\Drupal::token()->replace($this->fieldDefinition->getFieldDescription()));

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
        '#required' => $this->fieldDefinition->isFieldRequired(),
        '#title' => $title,
        '#description' => $description,
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FIELD_CARDINALITY_UNLIMITED && empty($form_state['programmed'])) {
        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array('field_add_more_submit'),
          '#ajax' => array(
              'callback' => 'field_add_more_js',
              'wrapper' => $wrapper_id,
              'effect' => 'fade',
          ),
        );
      }
    }

    return $elements;
  }

  /**
   * Generates the form element for a single copy of the widget.
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $entity = $items->getEntity();

    $element += array(
      '#entity_type' => $entity->entityType(),
      '#bundle' => $entity->bundle(),
      '#entity' => $entity,
      '#field_name' => $this->fieldDefinition->getFieldName(),
      '#language' => $items->getLangcode(),
      '#field_parents' => $form['#parents'],
      // Only the first widget should be required.
      '#required' => $delta == 0 && $this->fieldDefinition->isFieldRequired(),
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
      drupal_alter(array('field_widget_form', 'field_widget_' . $this->getPluginId() . '_form'), $element, $form_state, $context);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, array &$form_state) {
    $field_name = $this->fieldDefinition->getFieldName();

    // Extract the values from $form_state['values'].
    $path = array_merge($form['#parents'], array($field_name));
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state['values'], $path, $key_exists);

    if ($key_exists) {
      // Remove the 'value' of the 'add more' button.
      unset($values['add_more']);

      // Let the widget turn the submitted values into actual field values.
      // Make sure the '_weight' entries are persisted in the process.
      $weights = array();
      // Check that $values[0] is an array, because if it's a string, then in
      // PHP 5.3, ['_weight'] returns the first character.
      if (isset($values[0]) && is_array($values[0]) && isset($values[0]['_weight'])) {
        foreach ($values as $delta => $value) {
          $weights[$delta] = $value['_weight'];
        }
      }
      $items->setValue($this->massageFormValues($values, $form, $form_state));

      foreach ($items as $delta => $item) {
        // Put back the weight.
        if (isset($weights[$delta])) {
          $item->_weight = $weights[$delta];
        }
        // The tasks below are going to reshuffle deltas. Keep track of the
        // original deltas for correct reporting of errors in flagErrors().
        $item->_original_delta = $delta;
      }

      // Account for drag-n-drop reordering.
      $this->sortItems($items);

      // Remove empty values.
      $items->filterEmptyValues();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = field_form_get_state($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $item->_original_delta;
        unset($item->_original_delta);
      }
      field_form_set_state($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, array $form, array &$form_state) {
    $field_name = $this->fieldDefinition->getFieldName();

    $field_state = field_form_get_state($form['#parents'], $field_name, $form_state);

    if (!empty($field_state['constraint_violations'])) {
      // Locate the correct element in the the form.
      $element = NestedArray::getValue($form_state['complete_form'], $field_state['array_parents']);

      // Only set errors if the element is accessible.
      if (!isset($element['#access']) || $element['#access']) {
        $definition = $this->getPluginDefinition();
        $is_multiple = $definition['multiple_values'];

        $violations_by_delta = array();
        foreach ($field_state['constraint_violations'] as $violation) {
          // Separate violations by delta.
          $property_path = explode('.', $violation->getPropertyPath());
          $delta = array_shift($property_path);
          $violation->arrayPropertyPath = $property_path;
          $violations_by_delta[$delta][] = $violation;
        }

        foreach ($violations_by_delta as $delta => $delta_violations) {
          // For a multiple-value widget, pass all errors to the main widget.
          // For single-value widgets, pass errors by delta.
          if ($is_multiple) {
            $delta_element = $element;
          }
          else {
            $original_delta = $field_state['original_deltas'][$delta];
            $delta_element = $element[$original_delta];
          }
          foreach ($delta_violations as $violation) {
            // @todo: Pass $violation->arrayPropertyPath as property path.
            $error_element = $this->errorElement($delta_element, $violation, $form, $form_state);
            if ($error_element !== FALSE) {
              form_error($error_element, $violation->getMessage());
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
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
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
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::errorElement().
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, array &$form_state) {
    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::massageFormValues()
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
    return $values;
  }

  /**
   * Sorts submitted field values according to drag-n-drop reordering.
   *
   * @param \Drupal\Core\Entity\Field\FieldItemListInterface $items
   *   The field values.
   */
  protected function sortItems(FieldItemListInterface $items) {
    $cardinality = $this->fieldDefinition->getFieldCardinality();
    $is_multiple = ($cardinality == FIELD_CARDINALITY_UNLIMITED) || ($cardinality > 1);
    if ($is_multiple && isset($items[0]->_weight)) {
      $itemValues = $items->getValue(TRUE);
      usort($itemValues, function ($a, $b) {
        $a_weight = (is_array($a) ? $a['_weight'] : 0);
        $b_weight = (is_array($b) ? $b['_weight'] : 0);
        return $a_weight - $b_weight;
      });
      $items->setValue($itemValues);
      // Remove the '_weight' entries.
      foreach ($items as $item) {
        unset($item->_weight);
      }
    }
  }

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings() {
    return $this->fieldDefinition->getFieldSettings();
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
    return $this->fieldDefinition->getFieldSetting($setting_name);
  }

}
