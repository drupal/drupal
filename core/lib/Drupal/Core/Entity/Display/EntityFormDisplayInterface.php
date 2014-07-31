<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Display\EntityFormDisplayInterface.
 */

namespace Drupal\Core\Entity\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a common interface for entity form displays.
 */
interface EntityFormDisplayInterface extends EntityDisplayInterface {

  /**
   * Adds field widgets to an entity form.
   *
   * The form elements for the entity's fields are added by reference as direct
   * children in the $form parameter. This parameter can be a full form
   * structure (most common case for entity edit forms), or a sub-element of a
   * larger form.
   *
   * By default, submitted field values appear at the top-level of
   * $form_state['values']. A different location within $form_state['values']
   * can be specified by setting the '#parents' property on the incoming $form
   * parameter. Because of name clashes, two instances of the same field cannot
   * appear within the same $form element, or within the same '#parents' space.
   *
   * Sample resulting structure in $form:
   * @code
   *   '#parents' => The location of field values in $form_state['values'],
   *   '#entity_type' => The name of the entity type,
   *   '#bundle' => The name of the bundle,
   *   // One sub-array per field appearing in the entity, keyed by field name.
   *   // The structure of the array differs slightly depending on whether the
   *   // widget is 'single-value' (provides the input for one field value,
   *   // most common case), and will therefore be repeated as many times as
   *   // needed, or 'multiple-values' (one single widget allows the input of
   *   // several values, e.g checkboxes, select box...).
   *   'field_foo' => array(
   *     '#access' => TRUE if the current user has 'edit' grants for the field,
   *       FALSE if not.
   *     'widget' => array(
   *       '#field_name' => The name of the field,
   *       '#title' => The label of the field,
   *       '#description' => The description text for the field,
   *       '#required' => Whether or not the field is required,
   *       '#field_parents' => The 'parents' space for the field in the form,
   *          equal to the #parents property of the $form parameter received by
   *          this method,
   *
   *       // For 'multiple-value' widgets, the remaining elements in the
   *       // sub-array depend on the widget.
   *
   *       // For 'single-value' widgets:
   *       '#theme' => 'field_multiple_value_form',
   *       '#cardinality' => The field cardinality,
   *       '#cardinality_multiple => TRUE if the field can contain multiple
   *         items, FALSE otherwise.
   *       // One sub-array per copy of the widget, keyed by delta.
   *       0 => array(
   *         '#title' => The title to be displayed by the widget,
   *         '#description' => The description text for the field instance,
   *         '#required' => Whether the widget should be marked required,
   *         '#delta' => 0,
   *         '#weight' => 0,
   *         '#field_parents' => Same as above,
   *         // The remaining elements in the sub-array depend on the widget.
   *         ...
   *       ),
   *       1 => array(
   *         ...
   *       ),
   *       ...
   *     ),
   *     ...
   *   ),
   * )
   * @endcode
   *
   * Additionally, some processing data is placed in $form_state, and can be
   * accessed by \Drupal\Core\Field\WidgetBaseInterface::getWidgetState() and
   * \Drupal\Core\Field\WidgetBaseInterface::setWidgetState().
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $form
   *   The form structure to fill in. This can be a full form structure, or a
   *   sub-element of a larger form. The #parents property can be set to
   *   control the location of submitted field values within
   *   $form_state['values']. If not specified, $form['#parents'] is set to an
   *   empty array, which results in field values located at the top-level of
   *   $form_state['values'].
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildForm(ContentEntityInterface $entity, array &$form, FormStateInterface $form_state);

  /**
   * Validates submitted widget values and sets the corresponding form errors.
   *
   * There are two levels of validation for fields in forms: widget validation
   * and field validation.
   * - Widget validation steps are specific to a given widget's own form
   *   structure and UI metaphors. They are executed during normal form
   *   validation, usually through Form API's #element_validate property.
   *   Errors reported at this level are typically those that prevent the
   *   extraction of proper field values from the submitted form input.
   * - If no form / widget errors were reported for the field, field validation
   *   steps are performed according to the "constraints" specified by the
   *   field definition. Those are independent of the specific widget being
   *   used in a given form, and are also performed on REST entity submissions.
   *
   * This function performs field validation in the context of a form submission.
   * It reports field constraint violations as form errors on the correct form
   * elements.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFormValues(ContentEntityInterface $entity, array &$form, FormStateInterface $form_state);

  /**
   * Extracts field values from the submitted widget values into the entity.
   *
   * This accounts for drag-and-drop reordering of field values, and filtering
   * of empty values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array whose keys and values are the keys of the top-level entries in
   *   $form_state['values'] that have been processed. The remaining entries, if
   *   any, do not correspond to widgets and should be extracted manually by
   *   the caller if needed.
   */
  public function extractFormValues(ContentEntityInterface $entity, array &$form, FormStateInterface $form_state);

}
