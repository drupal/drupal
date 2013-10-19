<?php

/**
 * @file
 * Contains \Drupal\Core\Field\WidgetBaseInterface.
 */

namespace Drupal\Core\Field;

/**
 * Base interface definition for "Field widget" plugins.
 *
 * This interface details base wrapping methods that most widget implementations
 * will want to directly inherit from Drupal\Core\Field\WidgetBase.
 * See Drupal\Core\Field\WidgetInterface for methods that will more
 * likely be overriden.
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
   * @param array $form_state
   *   An array containing the current state of the form.
   * @param int $get_delta
   *   Used to get only a specific delta value of a multiple value field.
   *
   * @return array
   *   The form element array created for this field.
   */
  public function form(FieldItemListInterface $items, array &$form, array &$form_state, $get_delta = NULL);

  /**
   * Extracts field values from submitted form values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values. This parameter is altered by reference to receive the
   *   incoming form values.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param array $form_state
   *   The form state.
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, array &$form_state);

  /**
   * Reports field-level validation errors against actual form elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param array $form_state
   *   The form state.
   */
  public function flagErrors(FieldItemListInterface $items, array $form, array &$form_state);

}
