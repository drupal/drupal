<?php

namespace Drupal\Core\Field;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base interface definition for "Field widget" plugins.
 *
 * This interface details base wrapping methods that most widget implementations
 * will want to directly inherit from Drupal\Core\Field\WidgetBase. See
 * Drupal\Core\Field\WidgetInterface for methods that will more likely be
 * overridden in actual widget implementations.
 */
interface WidgetBaseInterface extends PluginSettingsInterface {

  /**
   * Creates a form element for a field.
   *
   * If the entity associated with the form is new (i.e., $entity->isNew() is
   * TRUE), the 'default value', if any, is pre-populated. Also allows other
   * modules to alter the form element by implementing their own hooks.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   An array of the field values. When creating a new entity this may be NULL
   *   or an empty array to use default values.
   * @param array $form
   *   An array representing the form that the editing element will be attached
   *   to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $get_delta
   *   Used to get only a specific delta value of a multiple value field.
   *
   * @return array
   *   The form element array created for this field.
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL);

  /**
   * Extracts field values from submitted form values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values. This parameter is altered by reference to receive the
   *   incoming form values.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state);

  /**
   * Reports field-level validation errors against actual form elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   A list of constraint violations to flag.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state);

  /**
   * Retrieves processing information about the widget from $form_state.
   *
   * This method is static so that it can be used in static Form API callbacks.
   *
   * @param array $parents
   *   The array of #parents where the field lives in the form.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array with the following key/value pairs:
   *   - items_count: The number of widgets to display for the field.
   *   - array_parents: The location of the field's widgets within the $form
   *     structure. This entry is populated at '#after_build' time.
   */
  public static function getWidgetState(array $parents, $field_name, FormStateInterface $form_state);

  /**
   * Stores processing information about the widget in $form_state.
   *
   * This method is static so that it can be used in static Form API #callbacks.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $field_state
   *   The array of data to store. See getWidgetState() for the structure and
   *   content of the array.
   */
  public static function setWidgetState(array $parents, $field_name, FormStateInterface $form_state, array $field_state);

}
