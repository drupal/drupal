<?php

/**
 * @file
 * Contains \Drupal\Core\Field\WidgetBase.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base class for 'Field widget' plugin implementations.
 *
 * @ingroup field_widget
 */
abstract class WidgetBase extends PluginSettingsBase implements WidgetInterface {

  use AllowedTagsXssTrait;

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
    parent::__construct(array(), $plugin_id, $plugin_definition);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Store field information in $form_state.
    if (!static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = array(
        'items_count' => count($items),
        'array_parents' => array(),
      );
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
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
        '#description' => $this->fieldFilterXss(\Drupal::token()->replace($this->fieldDefinition->getDescription())),
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

    // Populate the 'array_parents' information in $form_state->get('field')
    // after the form is built, so that we catch changes in the form structure
    // performed in alter() hooks.
    $elements['#after_build'][] = array(get_class($this), 'afterBuild');
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
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = String::checkPlain($this->fieldDefinition->getLabel());
    $description = $this->fieldFilterXss(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

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
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, array($field_name)));
        $wrapper_id = drupal_html_id($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

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
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  /**
   * Generates the form element for a single copy of the widget.
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element += array(
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
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], array($field_name));
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
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
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
      $form_builder = \Drupal::formBuilder();

      // Locate the correct element in the the form.
      $element = NestedArray::getValue($form_state->getCompleteForm(), $field_state['array_parents']);

      // Do not report entity-level validation errors if Form API errors have
      // already been reported for the field.
      // @todo Field validation should not be run on fields with FAPI errors to
      //   begin with. See https://drupal.org/node/2070429.
      $element_path = implode('][', $element['#parents']);
      if ($reported_errors = $form_state->getErrors()) {
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
        foreach ($violations as $violation) {
          // Separate violations by delta.
          $property_path = explode('.', $violation->getPropertyPath());
          $delta = array_shift($property_path);
          $violations_by_delta[$delta][] = $violation;
          $violation->arrayPropertyPath = $property_path;
        }

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface[] $delta_violations */
        foreach ($violations_by_delta as $delta => $delta_violations) {
          // Pass violations to the main element:
          // - if this is a multiple-value widget,
          // - or if the violations are at the ItemList level.
          if ($handles_multiple || !is_numeric($delta)) {
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
              $form_state->setError($error_element, $violation->getMessage());
            }
          }
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
    return array_merge(array('field_storage', '#parents'), $parents, array('#fields', $field_name));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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

}
