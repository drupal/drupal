<?php

/**
 * @file
 * This file contains functions marked as deprecated.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Field;

/**
 * Returns a lightweight map of fields across bundles.
 *
 * The function only returns non-deleted fields.
 *
 * @return
 *   An array keyed by entity type. Each value is an array which keys are
 *   field names and value is an array with two entries:
 *   - type: The field type.
 *   - bundles: The bundles in which the field appears.
 * Example:
 * @code
 * array(
 *   'node' => array(
 *     'body' => array(
 *       'bundles' => array(
 *         'page', 'article'
 *       ),
 *       'type' => 'text_with_summary',
 *     ),
 *   ),
 * );
 * @endcode
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getFieldMap().
 */
function field_info_field_map() {
  return Field::fieldInfo()->getFieldMap();
}

/**
 * Returns data about an individual field.
 *
 * @param $entity_type
 *   The entity type.
 * @param $field_name
 *   The name of the field to retrieve. $field_name can only refer to a
 *   non-deleted field. For deleted fields, use field_info_field_by_id().
 *
 * @return
 *   The \Drupal\field\FieldConfigInterface field definition, as returned by
 *   entity_load_multiple_by_properties(), NULL if the field was not found.
 *
 * @see field_info_field_by_id()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getField($field_name).
 */
function field_info_field($entity_type, $field_name) {
  return Field::fieldInfo()->getField($entity_type, $field_name);
}

/**
 * Returns data about an individual field, given a field ID.
 *
 * @param $field_id
 *   The ID of the field to retrieve. $field_id can refer to a deleted field.
 *
 * @return
 *   The \Drupal\field\FieldConfigInterface field definition, as returned by
 *   entity_load_multiple_by_properties(), NULL if the field was not found.
 *
 * @see field_info_field()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getFieldById($field_id).
 */
function field_info_field_by_id($field_id) {
  return Field::fieldInfo()->getFieldById($field_id);
}

/**
 * Returns the same data as field_info_field_by_id() for every field.
 *
 * Use of this function should be avoided when possible, since it loads and
 * statically caches a potentially large array of information.
 *
 * When iterating over the fields present in a given bundle after a call to
 * field_info_instances($entity_type, $bundle), it is recommended to use the
 * getField() method on each $instance instead.
 *
 * @return \Drupal\field\FieldConfigInterface[]
 *   An array of FieldConfigInterface configuration entities, keyed by UUID.
 *
 * @see field_info_field()
 * @see field_info_field_by_id()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getFields().
 */
function field_info_field_by_ids() {
  return Field::fieldInfo()->getFields();
}

/**
 * Retrieves information about field instances.
 *
 * Use of this function to retrieve instances across separate bundles (i.e.
 * when the $bundle parameter is NULL) should be avoided when possible, since
 * it loads and statically caches a potentially large array of information.
 * Use field_info_field_map() instead.
 *
 * When retrieving the instances of a specific bundle (i.e. when both
 * $entity_type and $bundle_name are provided), the function also populates a
 * static cache with the corresponding field definitions, allowing fast
 * retrieval of field_info_field() later in the request.
 *
 * @param $entity_type
 *   (optional) The entity type for which to return instances.
 * @param $bundle_name
 *   (optional) The bundle name for which to return instances. If $entity_type
 *   is NULL, the $bundle_name parameter is ignored.
 *
 * @return
 *   If $entity_type is not set, return all instances keyed by entity type and
 *   bundle name. If $entity_type is set, return all instances for that entity
 *   type, keyed by bundle name. If $entity_type and $bundle_name are set,
 *   return all instances for that bundle.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal\field\Field::fieldInfo()->getInstances(),
 *   \Drupal\field\Field::fieldInfo()->getInstances($entity_type) or
 *   \Drupal\field\Field::fieldInfo()->getBundleInstances($entity_type, $bundle_name).
 */
function field_info_instances($entity_type = NULL, $bundle_name = NULL) {
  $cache = Field::fieldInfo();

  if (!isset($entity_type)) {
    return $cache->getInstances();
  }

  if (!isset($bundle_name)) {
    return $cache->getInstances($entity_type);
  }

  return $cache->getBundleInstances($entity_type, $bundle_name);
}

/**
 * Returns an array of instance data for a specific field and bundle.
 *
 * The function populates a static cache with all fields and instances used in
 * the bundle, allowing fast retrieval of field_info_field() or
 * field_info_instance() later in the request.
 *
 * @param $entity_type
 *   The entity type for the instance.
 * @param $field_name
 *   The field name for the instance.
 * @param $bundle_name
 *   The bundle name for the instance.
 *
 * @return
 *   An associative array of instance data for the specific field and bundle;
 *   NULL if the instance does not exist.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\field\Field::fieldInfo()->getInstance($entity_type, $bundle_name, $field_name).
 */
function field_info_instance($entity_type, $field_name, $bundle_name) {
  return Field::fieldInfo()->getInstance($entity_type, $bundle_name, $field_name);
}

/**
 * Adds form elements for all fields for an entity to a form structure.
 *
 * The form elements for the entity's fields are added by reference as direct
 * children in the $form parameter. This parameter can be a full form structure
 * (most common case for entity edit forms), or a sub-element of a larger form.
 *
 * By default, submitted field values appear at the top-level of
 * $form_state['values']. A different location within $form_state['values'] can
 * be specified by setting the '#parents' property on the incoming $form
 * parameter. Because of name clashes, two instances of the same field cannot
 * appear within the same $form element, or within the same '#parents' space.
 *
 * For each call to field_attach_form(), field values are processed by calling
 * field_attach_extract_form_values() on the same $form element.
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
 *       '#language' => $langcode,
 *       '#field_parents' => The 'parents' space for the field in the form,
 *          equal to the #parents property of the $form parameter received by
 *          field_attach_form(),
 *       '#required' => Whether or not the field is required,
 *       '#title' => The label of the field instance,
 *       '#description' => The description text for the field instance,
 *
 *       // Only for 'single' widgets:
 *       '#theme' => 'field_multiple_value_form',
 *       '#cardinality' => The field cardinality,
 *       '#cardinality_multiple => TRUE if the field can contain multiple items,
 *         FALSE otherwise.
 *       // One sub-array per copy of the widget, keyed by delta.
 *       0 => array(
 *         '#entity_type' => The name of the entity type,
 *         '#bundle' => The name of the bundle,
 *         '#field_name' => The name of the field,
 *         '#field_parents' => The 'parents' space for the field in the form,
 *            equal to the #parents property of the $form parameter received by
 *            field_attach_form(),
 *         '#title' => The title to be displayed by the widget,
 *         '#default_value' => The field value for delta 0,
 *         '#required' => Whether the widget should be marked required,
 *         '#delta' => 0,
 *         // The remaining elements in the sub-array depend on the widget.
 *         '#type' => The type of the widget,
 *         ...
 *       ),
 *       1 => array(
 *         ...
 *       ),
 *
 *       // Only for multiple widgets:
 *       '#entity_type' => The name of the entity type,
 *       '#bundle' => $instance['bundle'],
 *       // The remaining elements in the sub-array depend on the widget.
 *       '#type' => The type of the widget,
 *       ...
 *     ),
 *     ...
 *   ),
 * )
 * @endcode
 *
 * Additionally, some processing data is placed in $form_state, and can be
 * accessed by field_form_get_state() and field_form_set_state().
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which to load form elements, used to initialize
 *   default form values.
 * @param $form
 *   The form structure to fill in. This can be a full form structure, or a
 *   sub-element of a larger form. The #parents property can be set to control
 *   the location of submitted field values within $form_state['values']. If
 *   not specified, $form['#parents'] is set to an empty array, placing field
 *   values at the top-level of $form_state['values'].
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $langcode
 *   The language the field values are going to be entered, if no language
 *   is provided the default site language will be used.
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use the entity system instead, see https://drupal.org/developing/api/entity
 *
 * @see field_form_get_state()
 * @see field_form_set_state()
 */
function field_attach_form(EntityInterface $entity, &$form, &$form_state, $langcode = NULL, array $options = array()) {
  // Set #parents to 'top-level' by default.
  $form += array('#parents' => array());

  // Get the entity_form_display object for this form.
  $form_display = $form_state['form_display'];

  $form += (array) field_invoke_method('form', _field_invoke_widget_target($form_display), $entity, $form, $form_state, $options);

  $form['#entity_type'] = $entity->getEntityTypeId();
  $form['#bundle'] = $entity->bundle();

  // Let other modules make changes to the form.
  // Avoid \Drupal::moduleHandler()->invokeAll()
  // to let parameters be taken by reference.
  foreach (\Drupal::moduleHandler()->getImplementations('field_attach_form') as $module) {
    $function = $module . '_field_attach_form';
    $function($entity, $form, $form_state, $langcode);
  }
}

/**
 * Performs field validation against form-submitted field values.
 *
 * There are two levels of validation for fields in forms: widget validation and
 * and field validation.
 * - Widget validation steps are specific to a given widget's own form structure
 *   and UI metaphors. They are executed through FAPI's #element_validate
 *   property during normal form validation.
 * - Field validation steps are common to a given field type, independently of
 *   the specific widget being used in a given form. They are defined in the
 *   field type's implementation of hook_field_validate().
 *
 * This function performs field validation in the context of a form submission.
 * It converts field validation errors into form errors on the correct form
 * elements. Fieldable entity types should call this function during their own
 * form validation function.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity being submitted. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure where field elements are attached to. This might be a
 *   full form structure, or a sub-element of a larger form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use the entity system instead, see https://drupal.org/developing/api/entity
 */
function field_attach_form_validate(ContentEntityInterface $entity, $form, &$form_state, array $options = array()) {
  $has_violations = FALSE;
  foreach ($entity as $field_name => $field) {
    $definition = $field->getFieldDefinition();
    if ($definition->isConfigurable() && (empty($options['field_name']) || $options['field_name'] == $field_name)) {
      $field_violations = $field->validate();
      if (count($field_violations)) {
        $has_violations = TRUE;

        // Place violations in $form_state.
        $field_state = field_form_get_state($form['#parents'], $field_name, $form_state);
        $field_state['constraint_violations'] = $field_violations;
        field_form_set_state($form['#parents'], $field_name, $form_state, $field_state);
      }
    }
  }

  if ($has_violations) {
    // Map errors back to form elements.
    $form_display = $form_state['form_display'];
    field_invoke_method('flagErrors', _field_invoke_widget_target($form_display), $entity, $form, $form_state, $options);
  }
}

/**
 * Populates an entity object with values from a form submission.
 *
 * Currently, this accounts for drag-and-drop reordering of field values, and
 * filtering of empty values.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being submitted. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure where field elements are attached to. This might be a
 *   full form structure, or a sub-element of a larger form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use the entity system instead, see https://drupal.org/developing/api/entity
 */
function field_attach_extract_form_values(EntityInterface $entity, $form, &$form_state, array $options = array()) {
  // Extract field values from submitted values.
  $form_display = $form_state['form_display'];
  field_invoke_method('extractFormValues', _field_invoke_widget_target($form_display), $entity, $form, $form_state, $options);

  // Let other modules act on submitting the entity.
  // Avoid \Drupal::moduleHandler()->invokeAll()
  // to let $form_state be taken by reference.
  foreach (\Drupal::moduleHandler()->getImplementations('field_attach_extract_form_values') as $module) {
    $function = $module . 'field_attach_extract_form_values';
    $function($entity, $form, $form_state);
  }
}
