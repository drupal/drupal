<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Widget\WidgetInterface.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;

/**
 * Interface definition for field widget plugins.
 *
 * This interface details the methods that most plugin implementations will want
 * to override. See Drupal\field\Plugin\Type\Widget\WidgetBaseInterface for base
 * wrapping methods that should most likely be inherited directly from
 * Drupal\field\Plugin\Type\Widget\WidgetBase..
 */
interface WidgetInterface extends WidgetBaseInterface {

  /**
   * Returns a form to configure settings for the widget.
   *
   * Invoked from field_ui_field_edit_form() to allow administrators to
   * configure the widget. The field_ui module takes care of handling submitted
   * form values.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The form definition for the widget settings.
   */
  public function settingsForm(array $form, array &$form_state);

  /**
   * Returns the form for a single field widget.
   *
   * Field widget form elements should be based on the passed-in $element, which
   * contains the base form element properties derived from the field
   * configuration.
   *
   * The BaseWidget methods will set the weight, field name and delta values for
   * each form element. If there are multiple values for this field, the
   * formElement() method will be called as many times as needed.
   *
   * Note that, depending on the context in which the widget is being included
   * (regular entity form, field configuration form, advanced search form...),
   * the values for $field and $instance might be different from the "official"
   * definitions returned by field_info_field() and field_info_instance().
   * Examples: mono-value widget even if the field is multi-valued, non-required
   * widget even if the field is 'required'...
   *
   * Therefore, the FAPI element callbacks (such as #process, #element_validate,
   * #value_callback...) used by the widget cannot use the field_info_field()
   * or field_info_instance() functions to retrieve the $field or $instance
   * definitions they should operate on. The field_widget_field() and
   * field_widget_instance() functions should be used instead to fetch the
   * current working definitions from $form_state, where Field API stores them.
   *
   * Alternatively, hook_field_widget_form() can extract the needed specific
   * properties from $field and $instance and set them as ad-hoc
   * $element['#custom'] properties, for later use by its element callbacks.
   *
   * Other modules may alter the form element provided by this function using
   * hook_field_widget_form_alter().
   *
   * @param array $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of subelements (0, 1, 2, etc).
   * @param array $element
   *   A form element array containing basic properties for the widget:
   *   - #entity_type: The name of the entity the field is attached to.
   *   - #bundle: The name of the field bundle the field is contained in.
   *   - #entity: The entity the field is attached to.
   *   - #field_name: The name of the field.
   *   - #language: The language the field is being edited in.
   *   - #field_parents: The 'parents' space for the field in the form. Most
   *       widgets can simply overlook this property. This identifies the
   *       location where the field values are placed within
   *       $form_state['values'], and is used to access processing information
   *       for the field through the field_form_get_state() and
   *       field_form_set_state() functions.
   *   - #columns: A list of field storage columns of the field.
   *   - #title: The sanitized element label for the field instance, ready for
   *     output.
   *   - #description: The sanitized element description for the field instance,
   *     ready for output.
   *   - #required: A Boolean indicating whether the element value is required;
   *     for required multiple value fields, only the first widget's values are
   *     required.
   *   - #delta: The order of this item in the array of subelements; see $delta
   *     above.
   * @param string $langcode
   *   The language associated with $items.
   * @param string $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param string $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The form elements for a single widget for this field.
   *
   * @see field_widget_field()
   * @see field_widget_instance()
   * @see hook_field_widget_form_alter()
   * @see hook_field_widget_WIDGET_TYPE_form_alter()
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state);

  /**
   * Assigns a field-level validation error to the right widget sub-element.
   *
   * Depending on the widget's internal structure, a field-level validation
   * error needs to be flagged on the right sub-element.
   *
   * @param array $element
   *   An array containing the form element for the widget, as generated by
   *   formElement().
   * @param array $error
   *   An associative array with the following key-value pairs, as returned by
   *   hook_field_validate():
   *   - error: the error code. Complex widgets might need to report different
   *     errors to different form elements inside the widget.
   *   - message: the human readable message to be displayed.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   The element on which the error should be flagged.
   */
  public function errorElement(array $element, array $error, array $form, array &$form_state);

  /**
   * Massages the form values into the format expected for field values.
   *
   * @param array $values
   *   The submitted form values produced by the widget.
   *   - If the widget does not manage multiple values itself, the array holds
   *     the values generated by the multiple copies of the $element generated
   *     by the formElement() method, keyed by delta.
   *   - If the widget manages multiple values, the array holds the values
   *     of the form element generated by the formElement() method.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param array $form_state
   *   The form state.
   *
   * @return array
   *   An array of field values, keyed by delta.
   */
  public function massageFormValues(array $values, array $form, array &$form_state);

}
