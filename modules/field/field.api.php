<?php
// $Id: field.api.php,v 1.81 2010/05/18 18:30:49 dries Exp $

/**
 * @ingroup field_fieldable_type
 * @{
 */

/**
 * Expose "pseudo-field" components on fieldable entities.
 *
 * Field UI's 'Manage fields' page lets users re-order fields, but also
 * non-field components. For nodes, these include the title, menu settings, and
 * other elements exposed by contributed modules through hook_form() and
 * hook_form_alter().
 *
 * Fieldable entities or contributed modules that want to have their components
 * supported should expose them using this hook, and use
 * field_attach_extra_weight() to retrieve the user-defined weight when
 * inserting the component.
 *
 * @return
 *   A nested array of 'pseudo-field' components. Each list is nested within the
 *   field bundle to which those components apply. The keys are the name of the
 *   element as it appears in the form structure. The values are arrays with the
 *   following key/value pairs:
 *   - label: The human readable name of the component.
 *   - description: A short description of the component contents.
 *   - weight: The default weight of the element.
 *   - view: (optional) The name of the element as it appears in the rendered
 *     structure, if different from the name in the form.
 *
 * @see hook_field_extra_fields_alter()
 */
function hook_field_extra_fields() {
  $extra = array();

  foreach (node_type_get_types() as $bundle) {
    if ($type->has_title) {
      $extra['node'][$bundle]['title'] = array(
        'label' => $type->title_label,
        'description' => t('Node module element.'),
        'weight' => -5,
      );
    }
  }
  if (module_exists('poll')) {
    $extra['node']['poll']['choice_wrapper'] = array(
      'label' => t('Poll choices'),
      'description' => t('Poll module choices.'),
      'weight' => -4,
    );
    $extra['node']['poll']['settings'] = array(
      'label' => t('Poll settings'),
      'description' => t('Poll module settings.'),
      'weight' => -3,
    );
  }

  return $extra;
}

/**
 * Alter "pseudo-field" components on fieldable entities.
 *
 * @param $info
 *   The associative array of 'pseudo-field' components.
 *
 * @see hook_field_extra_fields()
 */
function hook_field_extra_fields_alter(&$info) {
  // Force node title to always be at the top of the list
  // by default.
  foreach (node_type_get_types() as $bundle) {
    if (isset($info['node'][$bundle]['title'])) {
      $info['node'][$bundle]['title']['weight'] = -20;
    }
  }

}

/**
 * @} End of "ingroup field_fieldable_type"
 */

/**
 * @defgroup field_types Field Types API
 * @{
 * Define field types, widget types, display formatter types, storage types.
 *
 * The bulk of the Field Types API are related to field types. A field type
 * represents a particular type of data (integer, string, date, etc.) that
 * can be attached to a fieldable entity. hook_field_info() defines the basic
 * properties of a field type, and a variety of other field hooks are called by
 * the Field Attach API to perform field-type-specific actions.
 *
 * @see hook_field_info()
 * @see hook_field_info_alter()
 * @see hook_field_schema()
 * @see hook_field_load()
 * @see hook_field_validate()
 * @see hook_field_presave()
 * @see hook_field_insert()
 * @see hook_field_update()
 * @see hook_field_delete()
 * @see hook_field_delete_revision()
 * @see hook_field_prepare_view()
 * @see hook_field_is_empty()
 *
 * The Field Types API also defines two kinds of pluggable handlers: widgets
 * and formatters, which specify how the field appears in edit forms and in
 * displayed entities. Widgets and formatters can be implemented by a field-type
 * module for its own field types, or by a third-party module to extend the
 * behavior of existing field types.
 *
 * @see hook_field_widget_info()
 * @see hook_field_formatter_info()
 *
 * A third kind of pluggable handlers, storage backends, is defined by the
 * @link field_storage Field Storage API @endlink.
 */

/**
 * Define Field API field types.
 *
 * @return
 *   An array whose keys are field type names and whose values are arrays
 *   describing the field type, with the following key/value pairs:
 *   - label: The human-readable name of the field type.
 *   - description: A short description for the field type.
 *   - settings: An array whose keys are the names of the settings available
 *     for the field type, and whose values are the default values for those
 *     settings.
 *   - instance_settings: An array whose keys are the names of the settings
 *     available for instances of the field type, and whose values are the
 *     default values for those settings. Instance-level settings can have
 *     different values on each field instance, and thus allow greater
 *     flexibility than field-level settings. It is recommended to put settings
 *     at the instance level whenever possible. Notable exceptions: settings
 *     acting on the schema definition, or settings that Views needs to use
 *     across field instances (for example, the list of allowed values).
 *   - default_widget: The machine name of the default widget to be used by
 *     instances of this field type, when no widget is specified in the
 *     instance definition. This widget must be available whenever the field
 *     type is available (i.e. provided by the field type module, or by a module
 *     the field type module depends on).
 *   - default_formatter: The machine name of the default formatter to be used
 *     by instances of this field type, when no formatter is specified in the
 *     instance definition. This formatter must be available whenever the field
 *     type is available (i.e. provided by the field type module, or by a module
 *     the field type module depends on).
 *   - no_ui: (optional) A boolean specifying that users should not be allowed
 *     to create fields and instances of this field type through the UI. Such
 *     fields can only be created programmatically with field_create_field()
 *     and field_create_instance(). Defaults to FALSE.
 *
 * @see hook_field_info_alter()
 */
function hook_field_info() {
  return array(
    'text' => array(
      'label' => t('Text'),
      'description' => t('This field stores varchar text in the database.'),
      'settings' => array('max_length' => 255),
      'instance_settings' => array('text_processing' => 0),
      'default_widget' => 'text_textfield',
      'default_formatter' => 'text_default',
    ),
    'text_long' => array(
      'label' => t('Long text'),
      'description' => t('This field stores long text in the database.'),
      'settings' => array('max_length' => ''),
      'instance_settings' => array('text_processing' => 0),
      'default_widget' => 'text_textarea',
      'default_formatter' => 'text_default',
    ),
    'text_with_summary' => array(
      'label' => t('Long text and summary'),
      'description' => t('This field stores long text in the database along with optional summary text.'),
      'settings' => array('max_length' => ''),
      'instance_settings' => array('text_processing' => 1, 'display_summary' => 0),
      'default_widget' => 'text_textarea_with_summary',
      'default_formatter' => 'text_summary_or_trimmed',
    ),
  );
}

/**
 * Perform alterations on Field API field types.
 *
 * @param $info
 *   Array of information on field types exposed by hook_field_info()
 *   implementations.
 */
function hook_field_info_alter(&$info) {
  // Add a setting to all field types.
  foreach ($info as $field_type => $field_type_info) {
    $info[$field_type]['settings'] += array(
      'mymodule_additional_setting' => 'default value',
    );
  }

  // Change the default widget for fields of type 'foo'.
  if (isset($info['foo'])) {
    $info['foo']['default widget'] = 'mymodule_widget';
  }
}

/**
 * Define the Field API schema for a field structure.
 *
 * @param $field
 *   A field structure.
 *
 * @return
 *   An associative array with the following keys:
 *   - columns: An array of Schema API column specifications, keyed by column
 *     name. This specifies what comprises a value for a given field. For
 *     example, a value for a number field is simply 'value', while a value for
 *     a formatted text field is the combination of 'value' and 'format'. It is
 *     recommended to avoid having the column definitions depend on field
 *     settings when possible. No assumptions should be made on how storage
 *     engines internally use the original column name to structure their
 *     storage.
 *   - indexes: An array of Schema API indexes definitions. Only columns that
 *     appear in the 'columns' array are allowed. Those indexes will be used as
 *     default indexes. Callers of field_create_field() can specify additional
 *     indexes, or, at their own risk, modify the default indexes specified by
 *     the field-type module. Some storage engines might not support indexes.
 */
function hook_field_schema($field) {
  if ($field['type'] == 'text_long') {
    $columns = array(
      'value' => array(
        'type' => 'text',
        'size' => 'big',
        'not null' => FALSE,
      ),
    );
  }
  else {
    $columns = array(
      'value' => array(
        'type' => 'varchar',
        'length' => $field['settings']['max_length'],
        'not null' => FALSE,
      ),
    );
  }
  $columns += array(
    'format' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ),
  );
  return array(
    'columns' => $columns,
    'indexes' => array(
      'format' => array('format'),
    ),
  );
}

/**
 * Define custom load behavior for this module's field types.
 *
 * Unlike most other field hooks, this hook operates on multiple entities. The
 * $entities, $instances and $items parameters are arrays keyed by entity ID.
 * For performance reasons, information for all available entity should be
 * loaded in a single query where possible.
 *
 * Note that the changes made to the field values get cached by the field cache
 * for subsequent loads. You should never use this hook to load fieldable
 * entities, since this is likely to cause infinite recursions when
 * hook_field_load() is run on those as well. Use
 * hook_field_formatter_prepare_view() instead.
 *
 * Make changes or additions to field values by altering the $items parameter by
 * reference. There is no return value.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entities
 *   Array of entities being loaded, keyed by entity ID.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each entity, keyed by entity
 *   ID.
 * @param $langcode
 *   The language code associated with $items.
 * @param $items
 *   Array of field values already loaded for the entities, keyed by entity ID.
 *   Store your changes in this parameter (passed by reference).
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each entity.
 */
function hook_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  // Sample code from text.module: precompute sanitized strings so they are
  // stored in the field cache.
  foreach ($entities as $id => $entity) {
    foreach ($items[$id] as $delta => $item) {
      // Only process items with a cacheable format, the rest will be handled
      // by formatters if needed.
      if (empty($instances[$id]['settings']['text_processing']) || filter_format_allowcache($item['format'])) {
        $items[$id][$delta]['safe_value'] = isset($item['value']) ? _text_sanitize($instances[$id], $langcode, $item, 'value') : '';
        if ($field['type'] == 'text_with_summary') {
          $items[$id][$delta]['safe_summary'] = isset($item['summary']) ? _text_sanitize($instances[$id], $langcode, $item, 'summary') : '';
        }
      }
    }
  }
}

/**
 * Prepare field values prior to display.
 *
 * This hook is invoked before the field values are handed to formatters
 * for display, and runs before the formatters' own
 * hook_field_formatter_prepare_view().
 *
 * Unlike most other field hooks, this hook operates on multiple entities. The
 * $entities, $instances and $items parameters are arrays keyed by entity ID.
 * For performance reasons, information for all available entities should be
 * loaded in a single query where possible.
 *
 * Make changes or additions to field values by altering the $items parameter by
 * reference. There is no return value.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entities
 *   Array of entities being displayed, keyed by entity ID.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each entity, keyed by entity
 *   ID.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   $entity->{$field['field_name']}, or an empty array if unset.
 */
function hook_field_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items) {
  // Sample code from image.module: if there are no images specified at all,
  // use the default image.
  foreach ($entities as $id => $entity) {
    if (empty($items[$id]) && $field['settings']['default_image']) {
      if ($file = file_load($field['settings']['default_image'])) {
        $items[$id][0] = (array) $file + array(
          'is_default' => TRUE,
          'alt' => '',
          'title' => '',
        );
      }
    }
  }
}

/**
 * Validate this module's field data.
 *
 * If there are validation problems, add to the $errors array (passed by
 * reference). There is no return value.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 * @param $errors
 *   The array of errors, keyed by field name and by value delta, that have
 *   already been reported for the entity. The function should add its errors
 *   to this array. Each error is an associative array, with the following
 *   keys and values:
 *   - error: An error code (should be a string, prefixed with the module
 *     name).
 *   - message: The human readable message to be displayed.
 */
function hook_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
  foreach ($items as $delta => $item) {
    if (!empty($item['value'])) {
      if (!empty($field['settings']['max_length']) && drupal_strlen($item['value']) > $field['settings']['max_length']) {
        $errors[$field['field_name']][$delta][] = array(
          'error' => 'text_max_length',
          'message' => t('%name: the value may not be longer than %max characters.', array('%name' => $instance['label'], '%max' => $field['settings']['max_length'])),
        );
      }
    }
  }
}

/**
 * Define custom presave behavior for this module's field types.
 *
 * Make changes or additions to field values by altering the $items parameter by
 * reference. There is no return value.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 */
function hook_field_presave($entity_type, $entity, $field, $instance, $langcode, &$items) {
  if ($field['type'] == 'number_decimal') {
    // Let PHP round the value to ensure consistent behavior across storage
    // backends.
    foreach ($items as $delta => $item) {
      if (isset($item['value'])) {
        $items[$delta]['value'] = round($item['value'], $field['settings']['scale']);
      }
    }
  }
}

/**
 * Define custom insert behavior for this module's field types.
 *
 * Invoked from field_attach_insert().
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 */
function hook_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // @todo Needs function body.
}

/**
 * Define custom update behavior for this module's field types.
 *
 * Invoked from field_attach_update().
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 */
function hook_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // @todo Needs function body.
}

/**
 * Update the storage information for a field.
 *
 * This is invoked on the field's storage module from field_update_field(),
 * before the new field information is saved to the database. The field storage
 * module should update its storage tables to agree with the new field
 * information. If there is a problem, the field storage module should throw an
 * exception.
 *
 * @param $field
 *   The updated field structure to be saved.
 * @param $prior_field
 *   The previously-saved field structure.
 * @param $has_data
 *   TRUE if the field has data in storage currently.
 */
function hook_field_storage_update_field($field, $prior_field, $has_data) {
  if (!$has_data) {
    // There is no data. Re-create the tables completely.
    $prior_schema = _field_sql_storage_schema($prior_field);
    foreach ($prior_schema as $name => $table) {
      db_drop_table($name, $table);
    }
    $schema = _field_sql_storage_schema($field);
    foreach ($schema as $name => $table) {
      db_create_table($name, $table);
    }
  }
  else {
    // There is data. See field_sql_storage_field_storage_update_field() for
    // an example of what to do to modify the schema in place, preserving the
    // old data as much as possible.
  }
  drupal_get_schema(NULL, TRUE);
}

/**
 * Define custom delete behavior for this module's field types.
 *
 * This hook is invoked just before the data is deleted from field storage
 * in field_attach_delete().
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 */
function hook_field_delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // @todo Needs function body.
}

/**
 * Define custom revision delete behavior for this module's field types.
 *
 * This hook is invoked just before the data is deleted from field storage
 * in field_attach_delete_revision(), and will only be called for fieldable
 * types that are versioned.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 */
function hook_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // @todo Needs function body.
}

/**
 * Define custom prepare_translation behavior for this module's field types.
 *
 * TODO: This hook may or may not survive in Field API.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity for the operation.
 * @param $field
 *   The field structure for the operation.
 * @param $instance
 *   The instance structure for $field on $entity's bundle.
 * @param $langcode
 *   The language associated to $items.
 * @param $items
 *   $entity->{$field['field_name']}[$langcode], or an empty array if unset.
 */
function hook_field_prepare_translation($entity_type, $entity, $field, $instance, $langcode, &$items) {
}

/**
 * Define what constitutes an empty item for a field type.
 *
 * @param $item
 *   An item that may or may not be empty.
 * @param $field
 *   The field to which $item belongs.
 *
 * @return
 *   TRUE if $field's type considers $item not to contain any data;
 *   FALSE otherwise.
 */
function hook_field_is_empty($item, $field) {
  if (empty($item['value']) && (string) $item['value'] !== '0') {
    return TRUE;
  }
  return FALSE;
}

/**
 * Expose Field API widget types.
 *
 * Widgets are Form API elements with additional processing capabilities.
 * Widget hooks are typically called by the Field Attach API during the
 * creation of the field form structure with field_attach_form().
 *
 * @see hook_field_widget_info_alter()
 * @see hook_field_widget_form()
 * @see hook_field_widget_error()
 *
 * @return
 *   An array describing the widget types implemented by the module.
 *   The keys are widget type names. To avoid name clashes, widget type
 *   names should be prefixed with the name of the module that exposes them.
 *   The values are arrays describing the widget type, with the following
 *   key/value pairs:
 *   - label: The human-readable name of the widget type.
 *   - description: A short description for the widget type.
 *   - field types: An array of field types the widget supports.
 *   - settings: An array whose keys are the names of the settings available
 *     for the widget type, and whose values are the default values for those
 *     settings.
 *   - behaviors: (optional) An array describing behaviors of the widget, with
 *     the following elements:
 *     - multiple values: One of the following constants:
 *       - FIELD_BEHAVIOR_DEFAULT: (default) If the widget allows the input of
 *         one single field value (most common case). The widget will be
 *         repeated for each value input.
 *       - FIELD_BEHAVIOR_CUSTOM: If one single copy of the widget can receive
 *         several field values. Examples: checkboxes, multiple select,
 *         comma-separated textfield.
 *     - default value: One of the following constants:
 *       - FIELD_BEHAVIOR_DEFAULT: (default) If the widget accepts default
 *         values.
 *       - FIELD_BEHAVIOR_NONE: if the widget does not support default values.
 */
function hook_field_widget_info() {
    return array(
    'text_textfield' => array(
      'label' => t('Text field'),
      'field types' => array('text'),
      'settings' => array('size' => 60),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_DEFAULT,
        'default value' => FIELD_BEHAVIOR_DEFAULT,
      ),
    ),
    'text_textarea' => array(
      'label' => t('Text area (multiple rows)'),
      'field types' => array('text_long'),
      'settings' => array('rows' => 5),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_DEFAULT,
        'default value' => FIELD_BEHAVIOR_DEFAULT,
      ),
    ),
    'text_textarea_with_summary' => array(
      'label' => t('Text area with a summary'),
      'field types' => array('text_with_summary'),
      'settings' => array('rows' => 20, 'summary_rows' => 5),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_DEFAULT,
        'default value' => FIELD_BEHAVIOR_DEFAULT,
      ),
    ),
  );
}

/**
 * Perform alterations on Field API widget types.
 *
 * @param $info
 *   Array of informations on widget types exposed by hook_field_widget_info()
 *   implementations.
 */
function hook_field_widget_info_alter(&$info) {
  // Add a setting to a widget type.
  $info['text_textfield']['settings'] += array(
    'mymodule_additional_setting' => 'default value',
  );

  // Let a new field type re-use an existing widget.
  $info['options_select']['field types'][] = 'my_field_type';
}

/**
 * Return the form for a single field widget.
 *
 * Field widget form elements should be based on the passed in $element, which
 * contains the base form element properties derived from the field
 * configuration.
 *
 * Field API will set the weight, field name and delta values for each form
 * element. If there are multiple values for this field, the Field API will
 * invoke this hook as many times as needed.
 *
 * Note that, depending on the context in which the widget is being included
 * (regular entity edit form, 'default value' input in the field settings form,
 * etc.), the passed in values for $field and $instance might be different
 * from the official definitions returned by field_info_field() and
 * field_info_instance(). If the widget uses Form API callbacks (like
 * #element_validate, #value_callback...) that need to access the $field or
 * $instance definitions, they should not use the field_info_*() functions, but
 * fetch the information present in $form_state['field']:
 * - $form_state['field'][$field_name][$langcode]['field']
 * - $form_state['field'][$field_name][$langcode]['instance']
 *
 * @param $form
 *   The entire form array.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $field
 *   The field structure.
 * @param $instance
 *   The field instance.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   Array of default values for this field.
 * @param $delta
 *   The order of this item in the array of subelements (0, 1, 2, etc).
 * @param $element
 *   A form element array containing basic properties for the widget:
 *   - #entity_type: The name of the entity the field is attached to.
 *   - #bundle: The name of the field bundle the field is contained in.
 *   - #field_name: The name of the field.
 *   - #language: The language the field is being edited in.
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
 *
 * @return
 *   The form elements for a single widget for this field.
 */
function hook_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $element += array(
    '#type' => $instance['widget']['type'],
    '#default_value' => isset($items[$delta]) ? $items[$delta] : '',
  );
  return $element;
}

/**
 * Flag a field-level validation error.
 *
 * @param $element
 *   An array containing the form element for the widget. The error needs to be
 *   flagged on the right sub-element, according to the widget's internal
 *   structure.
 * @param $error
 *   An associative array with the following key-value pairs, as returned by
 *   hook_field_validate():
 *   - error: the error code. Complex widgets might need to report different
 *     errors to different form elements inside the widget.
 *   - message: the human readable message to be displayed.
 * @param $form
 *   The form array.
 * @param $form_state
 *   An associative array containing the current state of the form.
 */
function hook_field_widget_error($element, $error, $form, &$form_state) {
  form_error($element['value'], $error['message']);
}

/**
 * Expose Field API formatter types.
 *
 * Formatters handle the display of field values. Formatter hooks are typically
 * called by the Field Attach API field_attach_prepare_view() and
 * field_attach_view() functions.
 *
 * @return
 *   An array describing the formatter types implemented by the module.
 *   The keys are formatter type names. To avoid name clashes, formatter type
 *   names should be prefixed with the name of the module that exposes them.
 *   The values are arrays describing the formatter type, with the following
 *   key/value pairs:
 *   - label: The human-readable name of the formatter type.
 *   - description: A short description for the formatter type.
 *   - field types: An array of field types the formatter supports.
 *   - settings: An array whose keys are the names of the settings available
 *     for the formatter type, and whose values are the default values for
 *     those settings.
 *
 * @see hook_field_formatter_info_alter()
 * @see hook_field_formatter_view()
 * @see hook_field_formatter_prepare_view()
 */
function hook_field_formatter_info() {
  return array(
    'text_default' => array(
      'label' => t('Default'),
      'field types' => array('text', 'text_long', 'text_with_summary'),
    ),
    'text_plain' => array(
      'label' => t('Plain text'),
      'field types' => array('text', 'text_long', 'text_with_summary'),
    ),

    // The text_trimmed formatter displays the trimmed version of the
    // full element of the field. It is intended to be used with text
    // and text_long fields. It also works with text_with_summary
    // fields though the text_summary_or_trimmed formatter makes more
    // sense for that field type.
    'text_trimmed' => array(
      'label' => t('Trimmed'),
      'field types' => array('text', 'text_long', 'text_with_summary'),
    ),

    // The 'summary or trimmed' field formatter for text_with_summary
    // fields displays returns the summary element of the field or, if
    // the summary is empty, the trimmed version of the full element
    // of the field.
    'text_summary_or_trimmed' => array(
      'label' => t('Summary or trimmed'),
      'field types' => array('text_with_summary'),
    ),
  );
}

/**
 * Perform alterations on Field API formatter types.
 *
 * @param $info
 *   Array of informations on formatter types exposed by
 *   hook_field_field_formatter_info() implementations.
 */
function hook_field_formatter_info_alter(&$info) {
  // Add a setting to a formatter type.
  $info['text_default']['settings'] += array(
    'mymodule_additional_setting' => 'default value',
  );

  // Let a new field type re-use an existing formatter.
  $info['text_default']['field types'][] = 'my_field_type';
}

/**
 * Allow formatters to load information for field values being displayed.
 *
 * This should be used when a formatter needs to load additional information
 * from the database in order to render a field, for example a reference field
 * which displays properties of the referenced entities such as name or type.
 *
 * This hook is called after the field type's own hook_field_prepare_view().
 *
 * Unlike most other field hooks, this hook operates on multiple entities. The
 * $entities, $instances and $items parameters are arrays keyed by entity ID.
 * For performance reasons, information for all available entities should be
 * loaded in a single query where possible.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entities
 *   Array of entities being displayed, keyed by entity ID.
 * @param $field
 *   The field structure for the operation.
 * @param $instances
 *   Array of instance structures for $field for each entity, keyed by entity
 *   ID.
 * @param $langcode
 *   The language the field values are to be shown in. If no language is
 *   provided the current language is used.
 * @param $items
 *   Array of field values for the entities, keyed by entity ID.
 * @param $displays
 *   Array of display settings to use for each entity, keyed by entity ID.
 *
 * @return
 *   Changes or additions to field values are done by altering the $items
 *   parameter by reference.
 */
function hook_field_formatter_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items, $displays) {
  // @todo Needs function body.
}

/**
 * Build a renderable array for a field value.
 *
 * @param $entity_type
 *   The type of $entity.
 * @param $entity
 *   The entity being displayed.
 * @param $field
 *   The field structure.
 * @param $instance
 *   The field instance.
 * @param $langcode
 *   The language associated with $items.
 * @param $items
 *   Array of values for this field.
 * @param $display
 *   The display settings to use, as found in the 'display' entry of instance
 *   definitions. The array notably contains the following keys and values;
 *   - type: The name of the formatter to use.
 *   - settings: The array of formatter settings.
 *
 * @return
 *   A renderable array for the $items, as an array of child elements keyed
 *   by numeric indexes starting from 0.
 */
function hook_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();
  $settings = $display['settings'];

  switch ($display['type']) {
    case 'sample_field_formatter_simple':
      // Common case: each value is displayed individually in a sub-element
      // keyed by delta. The field.tpl.php template specifies the markup
      // wrapping each value.
      foreach ($items as $delta => $item) {
        $element[$delta] = array('#markup' => $settings['some_setting'] . $item['value']);
      }
      break;

    case 'sample_field_formatter_themeable':
      // More elaborate formatters can defer to a theme function for easier
      // customization.
      foreach ($items as $delta => $item) {
        $element[$delta] = array(
          '#theme' => 'mymodule_theme_sample_field_formatter_themeable',
          '#data' => $item['value'],
          '#some_setting' => $settings['some_setting'],
        );
      }
      break;

    case 'sample_field_formatter_combined':
      // Some formatters might need to display all values within a single piece
      // of markup.
      $rows = array();
      foreach ($items as $delta => $item) {
        $rows[] = array($delta, $item['value']);
      }
      $element[0] = array(
        '#theme' => 'table',
        '#header' => array(t('Delta'), t('Value')),
        '#rows' => $rows,
      );
      break;
  }

  return $element;
}

/**
 * @} End of "ingroup field_type"
 */

/**
 * @ingroup field_attach
 * @{
 */

/**
 * Act on field_attach_form.
 *
 * This hook is invoked after the field module has performed the operation.
 * Implementing modules should alter the $form or $form_state parameters.
 *
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The entity for which to load form elements, used to initialize
 *   default form values.
 * @param $form
 *   The form structure to fill in.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $langcode
 *   The language the field values are going to be entered in. If no language
 *   is provided the default site language will be used.
 */
function hook_field_attach_form($entity_type, $entity, &$form, &$form_state, $langcode) {
  $tids = array();

  // Collect every possible term attached to any of the fieldable entities.
  foreach ($entities as $id => $entity) {
    foreach ($items[$id] as $delta => $item) {
      // Force the array key to prevent duplicates.
      $tids[$item['value']] = $item['value'];
    }
  }
  if ($tids) {
    $terms = array();

    // Avoid calling taxonomy_term_load_multiple because it could lead to
    // circular references.
    $query = db_select('taxonomy_term_data', 't');
    $query->fields('t');
    $query->condition('t.tid', $tids, 'IN');
    $query->addTag('term_access');
    $terms = $query->execute()->fetchAllAssoc('tid');

    // Iterate through the fieldable entities again to attach the loaded term data.
    foreach ($entities as $id => $entity) {
      foreach ($items[$id] as $delta => $item) {
        // Check whether the taxonomy term field instance value could be loaded.
        if (isset($terms[$item['value']])) {
          // Replace the instance value with the term data.
          $items[$id][$delta]['taxonomy_term'] = $terms[$item['value']];
        }
        // Otherwise, unset the instance value, since the term does not exist.
        else {
          unset($items[$id][$delta]);
        }
      }
    }
  }
}

/**
 * Act on field_attach_load().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * Unlike other field_attach hooks, this hook accounts for 'multiple loads'.
 * Instead of the usual $entity parameter, it accepts an array of entities,
 * indexed by entity ID. For performance reasons, information for all available
 * entities should be loaded in a single query where possible.
 *
 * The changes made to the entities' field values get cached by the field cache
 * for subsequent loads.
 *
 * See field_attach_load() for details and arguments.
 */
function hook_field_attach_load($entity_type, &$entities, $age, $options) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_validate().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_validate() for details and arguments.
 */
function hook_field_attach_validate($entity_type, $entity, &$errors) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_submit().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_submit() for details and arguments.
 */
function hook_field_attach_submit($entity_type, $entity, $form, &$form_state) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_presave().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_presave() for details and arguments.
 */
function hook_field_attach_presave($entity_type, $entity) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_insert().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_insert() for details and arguments.
 */
function hook_field_attach_insert($entity_type, $entity) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_update().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_update() for details and arguments.
 */
function hook_field_attach_update($entity_type, $entity) {
  // @todo Needs function body.
}

/**
 * Alter field_attach_preprocess() variables.
 *
 * This hook is invoked while preprocessing the field.tpl.php template file
 * in field_attach_preprocess().
 *
 * @param $variables
 *   The variables array is passed by reference and will be populated with field
 *   values.
 * @param $context
 *   An associative array containing:
 *   - entity_type: The type of $entity; for example, 'node' or 'user'.
 *   - entity: The entity with fields to render.
 *   - element: The structured array containing the values ready for rendering.
 */
function hook_field_attach_preprocess_alter(&$variables, $context) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_delete().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_delete() for details and arguments.
 */
function hook_field_attach_delete($entity_type, $entity) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_delete_revision().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_delete_revision() for details and arguments.
 */
function hook_field_attach_delete_revision($entity_type, $entity) {
  // @todo Needs function body.
}

/**
 * Act on field_purge_data().
 *
 * This hook is invoked in field_purge_data() and allows modules to act on
 * purging data from a single field pseudo-entity. For example, if a module
 * relates data in the field with its own data, it may purge its own data
 * during this process as well.
 *
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The pseudo-entity whose field data is being purged.
 * @param $field
 *   The (possibly deleted) field whose data is being purged.
 * @param $instance
 *   The deleted field instance whose data is being purged.
 *
 * @see @link field_purge Field API bulk data deletion @endlink
 * @see field_purge_data()
 */
function hook_field_attach_purge($entity_type, $entity, $field, $instance) {
  // find the corresponding data in mymodule and purge it
  if($entity_type == 'node' && $field->field_name == 'my_field_name') {
    mymodule_remove_mydata($entity->nid);
  }
}

/**
 * Perform alterations on field_attach_view().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * @param $output
 *   The structured content array tree for all of the entity's fields.
 * @param $context
 *   An associative array containing:
 *   - entity_type: The type of $entity; for example, 'node' or 'user'.
 *   - entity: The entity with fields to render.
 *   - view_mode: View mode, for example, 'full' or 'teaser'.
 */
function hook_field_attach_view_alter(&$output, $context) {
  // @todo Needs function body.
}

/**
 * Perform alterations on field_language() values.
 *
 * This hook is invoked to alter the array of display languages for the given
 * entity.
 *
 * @param $display_language
 *   A reference to an array of language codes keyed by field name.
 * @param $context
 *   An associative array containing:
 *   - entity_type: The type of the entity to be displayed.
 *   - entity: The entity with fields to render.
 *   - langcode: The language code $entity has to be displayed in.
 */
function hook_field_language_alter(&$display_language, $context) {
  // @todo Needs function body.
}

/**
 * Alter field_available_languages() values.
 *
 * This hook is invoked from field_available_languages() to allow modules to
 * alter the array of available languages for the given field.
 *
 * @param &$languages
 *   A reference to an array of language codes to be made available.
 * @param $context
 *   An associative array containing:
 *   - entity_type: The type of the entity the field is attached to.
 *   - field: A field data structure.
 */
function hook_field_available_languages_alter(&$languages, $context) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_create_bundle().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_create_bundle() for details and arguments.
 */
function hook_field_attach_create_bundle($entity_type, $bundle) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_rename_bundle().
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * See field_attach_rename_bundle() for details and arguments.
 */
function hook_field_attach_rename_bundle($entity_type, $bundle_old, $bundle_new) {
  // @todo Needs function body.
}

/**
 * Act on field_attach_delete_bundle.
 *
 * This hook is invoked after the field module has performed the operation.
 *
 * @param $entity_type
 *   The type of entity; for example, 'node' or 'user'.
 * @param $bundle
 *   The bundle that was just deleted.
 * @param $instances
 *   An array of all instances that existed for the bundle before it was
 *   deleted.
 */
function hook_field_attach_delete_bundle($entity_type, $bundle, $instances) {
  // @todo Needs function body.
}

/**
 * @} End of "ingroup field_attach"
 */

/**********************************************************************
 * Field Storage API
 **********************************************************************/

/**
 * @ingroup field_storage
 * @{
 */

/**
 * Expose Field API storage backends.
 *
 * @return
 *   An array describing the storage backends implemented by the module.
 *   The keys are storage backend names. To avoid name clashes, storage backend
 *   names should be prefixed with the name of the module that exposes them.
 *   The values are arrays describing the storage backend, with the following
 *   key/value pairs:
 *   - label: The human-readable name of the storage backend.
 *   - description: A short description for the storage backend.
 *   - settings: An array whose keys are the names of the settings available
 *     for the storage backend, and whose values are the default values for
 *     those settings.
 */
function hook_field_storage_info() {
  return array(
    'field_sql_storage' => array(
      'label' => t('Default SQL storage'),
      'description' => t('Stores fields in the local SQL database, using per-field tables.'),
      'settings' => array(),
    ),
  );
}

/**
 * Perform alterations on Field API storage types.
 *
 * @param $info
 *   Array of informations on storage types exposed by
 *   hook_field_field_storage_info() implementations.
 */
function hook_field_storage_info_alter(&$info) {
  // Add a setting to a storage type.
  $info['field_sql_storage']['settings'] += array(
    'mymodule_additional_setting' => 'default value',
  );
}

/**
 * Reveal the internal details about the storage for a field.
 *
 * For example, an SQL storage module might return the Schema API structure for
 * the table. A key/value storage module might return the server name,
 * authentication credentials, and bin name.
 *
 * Field storage modules are not obligated to implement this hook. Modules
 * that rely on these details must only use them for read operations.
 *
 * @param $field
 *   A field structure.
 *
 * @return
 *   An array of details.
 *    - The first dimension is a store type (sql, solr, etc).
 *    - The second dimension indicates the age of the values in the store
 *      FIELD_LOAD_CURRENT or FIELD_LOAD_REVISION.
 *    - Other dimensions are specific to the field storage module.
 *
 * @see hook_field_storage_details_alter()
 */
function hook_field_storage_details($field) {
  // @todo Needs function body.
}

/**
 * Perform alterations on Field API storage details.
 *
 * @param $details
 *   An array of storage details for fields as exposed by
 *   hook_field_storage_details() implementations.
 * @param $field
 *   A field structure.
 *
 * @see hook_field_storage_details()
 */
function hook_field_storage_details_alter(&$details, $field) {
  // @todo Needs function body.
}

/**
 * Load field data for a set of entities.
 *
 * This hook is invoked from field_attach_load() to ask the field storage
 * module to load field data.
 *
 * Modules implementing this hook should load field values and add them to
 * objects in $entities. Fields with no values should be added as empty
 * arrays.
 *
 * @param $entity_type
 *   The type of entity, such as 'node' or 'user'.
 * @param $entities
 *   The array of entity objects to add fields to, keyed by entity ID.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each entity.
 * @param $fields
 *   An array listing the fields to be loaded. The keys of the array are field
 *   IDs, and the values of the array are the entity IDs (or revision IDs,
 *   depending on the $age parameter) to add each field to.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - deleted: If TRUE, deleted fields should be loaded as well as
 *     non-deleted fields. If unset or FALSE, only non-deleted fields should be
 *     loaded.
 */
function hook_field_storage_load($entity_type, &$entities, $age, $fields, $options) {
  // @todo Needs function body.
}

/**
 * Write field data for an entity.
 *
 * This hook is invoked from field_attach_insert() and field_attach_update(),
 * to ask the field storage module to save field data.
 *
 * @param $entity_type
 *   The entity type of entity, such as 'node' or 'user'.
 * @param $entity
 *   The entity on which to operate.
 * @param $op
 *   FIELD_STORAGE_UPDATE when updating an existing entity,
 *   FIELD_STORAGE_INSERT when inserting a new entity.
 * @param $fields
 *   An array listing the fields to be written. The keys and values of the
 *   array are field IDs.
 */
function hook_field_storage_write($entity_type, $entity, $op, $fields) {
  // @todo Needs function body.
}

/**
 * Delete all field data for an entity.
 *
 * This hook is invoked from field_attach_delete() to ask the field storage
 * module to delete field data.
 *
 * @param $entity_type
 *   The entity type of entity, such as 'node' or 'user'.
 * @param $entity
 *   The entity on which to operate.
 * @param $fields
 *   An array listing the fields to delete. The keys and values of the
 *   array are field IDs.
 */
function hook_field_storage_delete($entity_type, $entity, $fields) {
  // @todo Needs function body.
}

/**
 * Delete a single revision of field data for an entity.
 *
 * This hook is invoked from field_attach_delete_revision() to ask the field
 * storage module to delete field revision data.
 *
 * Deleting the current (most recently written) revision is not
 * allowed as has undefined results.
 *
 * @param $entity_type
 *   The entity type of entity, such as 'node' or 'user'.
 * @param $entity
 *   The entity on which to operate. The revision to delete is
 *   indicated by the entity's revision ID property, as identified by
 *   hook_fieldable_info() for $entity_type.
 * @param $fields
 *   An array listing the fields to delete. The keys and values of the
 *   array are field IDs.
 */
function hook_field_storage_delete_revision($entity_type, $entity, $fields) {
  // @todo Needs function body.
}

/**
 * Handle a field query.
 *
 * This hook is invoked from field_attach_query() to ask the field storage
 * module to handle a field query.
 *
 * @param $field_name
 *   The name of the field to query.
 * @param $conditions
 *   See field_attach_query(). A storage module that doesn't support querying a
 *   given column should raise a FieldQueryException. Incompatibilities should
 *   be mentioned on the module project page.
 * @param $options
 *   See field_attach_query(). All option keys are guaranteed to be specified.
 *
 * @return
 *   See field_attach_query().
 */
function hook_field_storage_query($field_name, $conditions, $options) {
  // @todo Needs function body
}

/**
 * Act on creation of a new field.
 *
 * This hook is invoked from field_create_field() to ask the field storage
 * module to save field information and prepare for storing field instances.
 * If there is a problem, the field storage module should throw an exception.
 *
 * @param $field
 *   The field structure being created.
 */
function hook_field_storage_create_field($field) {
  // @todo Needs function body.
}

/**
 * Act on deletion of a field.
 *
 * This hook is invoked from field_delete_field() to ask the field storage
 * module to mark all information stored in the field for deletion.
 *
 * @param $field
 *   The field being deleted.
 */
function hook_field_storage_delete_field($field) {
  // @todo Needs function body.
}

/**
 * Act on deletion of a field instance.
 *
 * This hook is invoked from field_delete_instance() to ask the field storage
 * module to mark all information stored for the field instance for deletion.
 *
 * @param $instance
 *   The instance being deleted.
 */
function hook_field_storage_delete_instance($instance) {
  // @todo Needs function body.
}

/**
 * Act before the storage backends load field data.
 *
 * This hook allows modules to load data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * This lets 3rd party modules override, mirror, shard, or otherwise store a
 * subset of fields in a different way than the current storage engine.
 * Possible use cases include per-bundle storage, per-combo-field storage, etc.
 *
 * Modules implementing this hook should load field values and add them to
 * objects in $entities. Fields with no values should be added as empty
 * arrays. In addition, fields loaded should be added as keys to $skip_fields.
 *
 * @param $entity_type
 *   The type of entity, such as 'node' or 'user'.
 * @param $entities
 *   The array of entity objects to add fields to, keyed by entity ID.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each entity.
 * @param $skip_fields
 *   An array keyed by field IDs whose data has already been loaded and
 *   therefore should not be loaded again. Add a key to this array to indicate
 *   that your module has already loaded a field.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - field_id: The field ID that should be loaded. If unset, all fields
 *     should be loaded.
 *   - deleted: If TRUE, deleted fields should be loaded as well as
 *     non-deleted fields. If unset or FALSE, only non-deleted fields should be
 *     loaded.
 */
function hook_field_storage_pre_load($entity_type, $entities, $age, &$skip_fields, $options) {
  // @todo Needs function body.
}

/**
 * Act before the storage backends insert field data.
 *
 * This hook allows modules to store data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The entity with fields to save.
 * @param $skip_fields
 *   An array keyed by field IDs whose data has already been written and
 *   therefore should not be written again. The values associated with these
 *   keys are not specified.
 * @return
 *   Saved field IDs are set set as keys in $skip_fields.
 */
function hook_field_storage_pre_insert($entity_type, $entity, &$skip_fields) {
  if ($entity_type == 'node' && $entity->status && _forum_node_check_node_type($entity)) {
    $query = db_insert('forum_index')->fields(array('nid', 'title', 'tid', 'sticky', 'created', 'comment_count', 'last_comment_timestamp'));
    foreach ($entity->taxonomy_forums as $language) {
      foreach ($language as $delta) {
        $query->values(array(
          'nid' => $entity->nid,
          'title' => $entity->title,
          'tid' => $delta['value'],
          'sticky' => $entity->sticky,
          'created' => $entity->created,
          'comment_count' => 0,
          'last_comment_timestamp' => $entity->created,
        ));
      }
    }
    $query->execute();
  }
}

/**
 * Act before the storage backends update field data.
 *
 * This hook allows modules to store data before the Field Storage API,
 * optionally preventing the field storage module from doing so.
 *
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The entity with fields to save.
 * @param $skip_fields
 *   An array keyed by field IDs whose data has already been written and
 *   therefore should not be written again. The values associated with these
 *   keys are not specified.
 * @return
 *   Saved field IDs are set set as keys in $skip_fields.
 */
function hook_field_storage_pre_update($entity_type, $entity, &$skip_fields) {
  $first_call = &drupal_static(__FUNCTION__, array());

  if ($entity_type == 'node' && $entity->status && _forum_node_check_node_type($entity)) {
    // We don't maintain data for old revisions, so clear all previous values
    // from the table. Since this hook runs once per field, per entity, make
    // sure we only wipe values once.
    if (!isset($first_call[$entity->nid])) {
      $first_call[$entity->nid] = FALSE;
      db_delete('forum_index')->condition('nid', $entity->nid)->execute();
    }
    // Only save data to the table if the node is published.
    if ($entity->status) {
      $query = db_insert('forum_index')->fields(array('nid', 'title', 'tid', 'sticky', 'created', 'comment_count', 'last_comment_timestamp'));
      foreach ($entity->taxonomy_forums as $language) {
        foreach ($language as $delta) {
          $query->values(array(
            'nid' => $entity->nid,
            'title' => $entity->title,
            'tid' => $delta['value'],
            'sticky' => $entity->sticky,
            'created' => $entity->created,
            'comment_count' => 0,
            'last_comment_timestamp' => $entity->created,
          ));
        }
      }
      $query->execute();
      // The logic for determining last_comment_count is fairly complex, so
      // call _forum_update_forum_index() too.
      _forum_update_forum_index($entity->nid);
    }
  }
}

/**
 * Act before the storage backend runs the query.
 *
 * This hook should be implemented by modules that use
 * hook_field_storage_pre_load(), hook_field_storage_pre_insert() and
 * hook_field_storage_pre_update() to bypass the regular storage engine, to
 * handle field queries.
 *
 * @param $field_name
 *   The name of the field to query.
 * @param $conditions
 *   See field_attach_query().
 *   A storage module that doesn't support querying a given column should raise
 *   a FieldQueryException. Incompatibilities should be mentioned on the module
 *   project page.
 * @param $options
 *   See field_attach_query(). All option keys are guaranteed to be specified.
 * @param $skip_field
 *   Boolean, always coming as FALSE.
 * @return
 *   See field_attach_query().
 *   The $skip_field parameter should be set to TRUE if the query has been
 *   handled.
 */
function hook_field_storage_pre_query($field_name, $conditions, $options, &$skip_field) {
  // @todo Needs function body.
}

/**
 * @} End of "ingroup field_storage"
 */

/**********************************************************************
 * Field CRUD API
 **********************************************************************/

/**
 * @ingroup field_crud
 * @{
 */

/**
 * Act on a field being created.
 *
 * This hook is invoked from field_create_field() after the field is created, to
 * allow modules to act on field creation.
 *
 * @param $field
 *   The field just created.
 */
function hook_field_create_field($field) {
  // @todo Needs function body.
}

/**
 * Act on a field instance being created.
 *
 * This hook is invoked from field_create_instance() after the instance record
 * is saved, so it cannot be used to modify the instance itself.
 *
 * @param $instance
 *   The instance just created.
 */
function hook_field_create_instance($instance) {
  // @todo Needs function body.
}

/**
 * Forbid a field update from occurring.
 *
 * Any module may forbid any update for any reason. For example, the
 * field's storage module might forbid an update if it would change
 * the storage schema while data for the field exists. A field type
 * module might forbid an update if it would change existing data's
 * semantics, or if there are external dependencies on field settings
 * that cannot be updated.
 *
 * To forbid the update from occurring, throw a FieldUpdateForbiddenException.
 *
 * @param $field
 *   The field as it will be post-update.
 * @param $prior_field
 *   The field as it is pre-update.
 * @param $has_data
 *   Whether any data already exists for this field.
 */
function hook_field_update_forbid($field, $prior_field, $has_data) {
  // A 'list' field stores integer keys mapped to display values. If
  // the new field will have fewer values, and any data exists for the
  // abandoned keys, the field will have no way to display them. So,
  // forbid such an update.
  if ($has_data && count($field['settings']['allowed_values']) < count($prior_field['settings']['allowed_values'])) {
    // Identify the keys that will be lost.
    $lost_keys = array_diff(array_keys($field['settings']['allowed_values']), array_keys($prior_field['settings']['allowed_values']));
    // If any data exist for those keys, forbid the update.
    $count = field_attach_query($prior_field['id'], array('value', $lost_keys, 'IN'), 1);
    if ($count > 0) {
      throw new FieldUpdateForbiddenException("Cannot update a list field not to include keys with existing data");
    }
  }
}

/**
 * Act on a field being updated.
 *
 * This hook is invoked just after field is updated in field_update_field().
 *
 * @param $field
 *   The field as it is post-update.
 * @param $prior_field
 *   The field as it was pre-update.
 * @param $has_data
 *   Whether any data already exists for this field.
 */
function hook_field_update_field($field, $prior_field, $has_data) {
  // @todo Needs function body.
}

/**
 * Act on a field being deleted.
 *
 * This hook is invoked just after a field is deleted by field_delete_field().
 *
 * @param $field
 *   The field just deleted.
 */
function hook_field_delete_field($field) {
  // @todo Needs function body.
}

/**
 * Act on a field instance being updated.
 *
 * This hook is invoked from field_update_instance() after the instance record
 * is saved, so it cannot be used by a module to modify the instance itself.
 *
 * @param $instance
 *   The instance as it is post-update.
 * @param $prior_$instance
 *   The instance as it was pre-update.
 */
function hook_field_update_instance($instance, $prior_instance) {
  // @todo Needs function body.
}

/**
 * Act on a field instance being deleted.
 *
 * This hook is invoked from field_delete_instance() after the instance is
 * deleted.
 *
 * @param $instance
 *   The instance just deleted.
 */
function hook_field_delete_instance($instance) {
  // @todo Needs function body.
}

/**
 * Act on field records being read from the database.
 *
 * This hook is invoked from field_read_fields() on each field being read.
 *
 * @param $field
 *   The field record just read from the database.
 */
function hook_field_read_field(&$field) {
  // @todo Needs function body.
}

/**
 * Act on a field record being read from the database.
 *
 * This hook is invoked from field_read_instances() on each instance being read.
 *
 * @param $instance
 *   The instance record just read from the database.
 */
function hook_field_read_instance($instance) {
  // @todo Needs function body.
}

/**
 * Acts when a field record is being purged.
 *
 * In field_purge_field(), after the field configuration has been
 * removed from the database, the field storage module has had a chance to
 * run its hook_field_storage_purge_field(), and the field info cache
 * has been cleared, this hook is invoked on all modules to allow them to
 * respond to the field being purged.
 *
 * @param $field
 *   The field being purged.
 */
function hook_field_purge_field($field) {
  db_delete('my_module_field_info')
    ->condition('id', $field['id'])
    ->execute();
}

/**
 * Acts when a field instance is being purged.
 *
 * In field_purge_instance(), after the field instance has been
 * removed from the database, the field storage module has had a chance to
 * run its hook_field_storage_purge_instance(), and the field info cache
 * has been cleared, this hook is invoked on all modules to allow them to
 * respond to the field instance being purged.
 *
 * @param $instance
 *   The instance being purged.
 */
function hook_field_purge_field_instance($instance) {
  db_delete('my_module_field_instance_info')
    ->condition('id', $instance['id'])
    ->execute();
}

/**
 * Remove field storage information when a field record is purged.
 *
 * Called from field_purge_field() to allow the field storage module
 * to remove field information when a field is being purged.
 *
 * @param $field
 *   The field being purged.
 */
function hook_field_storage_purge_field($field) {
  $table_name = _field_sql_storage_tablename($field);
  $revision_name = _field_sql_storage_revision_tablename($field);
  db_drop_table($table_name);
  db_drop_table($revision_name);
}

/**
 * Remove field storage information when a field instance is purged.
 *
 * Called from field_purge_instance() to allow the field storage module
 * to remove field instance information when a field instance is being
 * purged.
 *
 * @param $instance
 *   The instance being purged.
 */
function hook_field_storage_purge_field_instance($instance) {
  db_delete('my_module_field_instance_info')
    ->condition('id', $instance['id'])
    ->execute();
}

/**
 * Remove field storage information when field data is purged.
 *
 * Called from field_purge_data() to allow the field storage
 * module to delete field data information.
 *
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   The pseudo-entity whose field data to delete.
 * @param $field
 *   The (possibly deleted) field whose data is being purged.
 * @param $instance
 *   The deleted field instance whose data is being purged.
 */
function hook_field_storage_purge($entity_type, $entity, $field, $instance) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  $etid = _field_sql_storage_etid($entity_type);

  $table_name = _field_sql_storage_tablename($field);
  $revision_name = _field_sql_storage_revision_tablename($field);
  db_delete($table_name)
    ->condition('etid', $etid)
    ->condition('entity_id', $id)
    ->execute();
  db_delete($revision_name)
    ->condition('etid', $etid)
    ->condition('entity_id', $id)
    ->execute();
}

/**
 * @} End of "ingroup field_crud"
 */

/**********************************************************************
 * TODO: I'm not sure where these belong yet.
 **********************************************************************/

/**
 * Determine whether the user has access to a given field.
 *
 * This hook is invoked from field_access() to let modules block access to
 * operations on fields. If no module returns FALSE, the operation is allowed.
 *
 * @param $op
 *   The operation to be performed. Possible values: 'edit', 'view'.
 * @param $field
 *   The field on which the operation is to be performed.
 * @param $entity_type
 *   The type of $entity; for example, 'node' or 'user'.
 * @param $entity
 *   (optional) The entity for the operation.
 * @param $account
 *   (optional) The account to check; if not given use currently logged in user.
 *
 * @return
 *   TRUE if the operation is allowed, and FALSE if the operation is denied.
 */
function hook_field_access($op, $field, $entity_type, $entity, $account) {
  // @todo Needs function body.
}
