<?php

class FieldException extends Exception {}

/**
 * @defgroup field_structs Field API data structures
 * @{
 * Represent Field API fields and instances.
 *
 * The Field API defines two primary data structures, Field and
 * Instance, and the concept of a Bundle.  A Field defines a
 * particular type of data that can be attached to objects.  A Field
 * Instance is a Field attached to a single Bundle.  A Bundle is a set
 * of fields that are treated as a group by the Field Attach API.
 *
 * For example, suppose a site administrator wants Article nodes to
 * have a subtitle and photo.  Using the Field API or some UI module,
 * the administrator creates a field named 'subtitle' of type 'text'
 * and a field named 'photo' of type 'image'.  The administrator
 * (again, via a UI) creates two Field Instances, one attaching the
 * field 'subtitle' to the bundle 'article' and one attaching the field
 * 'photo' to the bundle 'article'.  When the node system uses the
 * Field Attach API to load all fields for an Article node, it passes
 * the node's content type (which is 'article') as the node's bundle.
 * field_attach_load() then loads the 'subtitle' and 'photo' fields
 * because they are both attached to the bundle 'article'.
 *
 * Field objects are (currently) represented as an array of key/value
 * pairs.  The object properties are:
 *
 * @param array $field:
 * - field_name (string)
 *     The name of the field.  Each field name is unique within Field API.
 *     When a field is attached to an object, the field's data is stored
 *     in $object->$field_name.
 * - type (string)
 *     The type of the field, such as 'text' or 'image'.  Field types
 *     are defined by modules that implement hook_field_into().
 * - cardinality (integer)
 *     The number of values the field can hold.  Legal values are any
 *     positive integer or FIELD_CARDINALITY_UNLIMITED.
 * - locked (integer)
 *     TODO: undefined.
 * - module (string, read-only)
 *     The name of the module that implements the field type.
 * - active (integer, read-only)
 *     TRUE if the module that implements the field type is currently
 *     enabled, FALSE otherwise.
 * - deleted (integer, read-only)
 *     TRUE if this field has been deleted, FALSE otherwise.  Deleted
 *     fields are ignored by the Field Attach API. This property exists
 *     because fields can be marked for deletion but only actually
 *     destroyed by a separate garbage-collection process.
 * - columns (array, read-only).
 *     An array of the Field API columns used to store each value of
 *     this field.  The column list may depend on field settings; it is
 *     not constant per field type.  Field API column specifications are
 *     exactly like Schema API column specifications but, depending on
 *     the field storage module in use, the name of the column may not
 *     represent an actual column in an SQL database.
 * - settings (array)
 *     A sub-array of key/value pairs of field-type-specific settings.  Each
 *     field type module defines and documents its own field settings.
 *
 * Field Instance objects are (currently) represented as an array of
 * key/value pairs.  The object properties are:
 *
 * @param array $instance:
 * - field_name (string)
 *     The name of field attached by this instance.
 * - bundle (string)
 *     The name of the bundle that the field is attached to.
 * - label (string)
 *     A human-readable label for the field when used with this
 *     bundle.  For example, the label will be the title of Form API
 *     elements for this instance.
 * - description (string)
 *     A human-readable description for the field when used with this
 *     bundle.  For example, the description will be the help text of
 *     Form API elements for this instance.
 * - weight (float)
 *     The order in which the field should be sorted relative
 *     to other fields when used with this bundle.  The weight affects
 *     ordering in both forms (see field_attach_form()) and rendered output
 *     (see field_attach_view()).
 *     TODO - this should probably become a context setting so that
 *     the weight can be different in the form and in various other
 *     contexts.
 * - required (integer)
 *     TRUE if a value for this field is required when used with this
 *     bundle, FALSE otherwise.  Currently, required-ness is only enforced
 *     during Form API operations, not by field_attach_load(),
 *     field_attach_insert(), or field_attach_update().
 * - default_value_function (string)
 *     The name of the function, if any, that will provide a default value.
 * - deleted (integer, read-only)
 *     TRUE if this instance has been deleted, FALSE otherwise.
 *     Deleted instances are ignored by the Field Attach API.
 *     This property exists because instances can be marked for deletion but
 *     only actually destroyed by a separate garbage-collection process.
 * - settings (array)
 *     A sub-array of key/value pairs of field-type-specific instance
 *     settings.  Each field type module defines and documents its own
 *     instance settings.
 * - widget (array)
 *     A sub-array of key/value pairs identifying the Form API input widget
 *     for the field when used by this bundle.
 *     - type (string)
 *         The type of the widget, such as text_textfield.  Widget types
 *         are defined by modules that implement hook_field_widget_info().
 *     - module (string, read-only)
 *         The name of the module that implements the widget type.
 *     - active (integer, read-only)
 *         TRUE if the module that implements the widget type is currently
 *         enabled, FALSE otherwise.
 *     - settings (array)
 *         A sub-array of key/value pairs of widget-type-specific settings.
 *         Each field widget type module defines and documents its own
 *         widget settings.
 * - display (array)
 *     A sub-array of key/value pairs identifying display contexts
 *     and the way the field should be displayed in that context.
 *     TODO more work to do here.
 *     - (context_1)
 *       - label
 *       - exclude
 *       - type
 *       - settings
 *         - ...
 *       - module (internal)
 *     - (context 2)
 *       - ...
 *
 * TODO D7 : document max length for field types, widget types,
 * formatter names...
 */
/**
 * @} End of "defgroup field_structs".
 */

/**
 * @defgroup field_crud Field CRUD API
 * @{
 * Create, update, and delete Field API fields, bundles, and instances.
 *
 * Modules use this API, often in hook_install(), to create custom
 * data structures.  UI modules will use it to create a user interface.
 *
 * The Field CRUD API uses
 * @link field_structs Field API data structures @endlink.
 */

/**
 * Create a field.  This function does not bind the field to any
 * bundle; use field_create_instance for that.
 *
 * @param $field
 *   A field structure.  The field_name and type properties are required.
 * @throw
 *   FieldException
 */
function field_create_field($field) {
  // Field name is required.
  if (empty($field['field_name'])) {
    throw new FieldException('Attempt to create an unnamed field.');
  }
  // Field name cannot contain invalid characters.
  if (preg_match('/[^a-z0-9_]/', $field['field_name'])) {
    throw new FieldException('Attempt to create a field with invalid characters. Only alphanumeric characters and underscores are allowed.');
  }

  // TODO: check that field_name < 32 chars.

  // Check that the field type is known.
  $field_type = field_info_field_types($field['type']);
  if (!$field_type) {
    throw new FieldException(t('Attempt to create a field of unknown type %type.', array('%type' => $field['type'])));
  }

  // Ensure the field name is unique. We also check disabled or deleted fields.
  // TODO : do we want specific messages when clashing with a disabled or inactive field ?
  $prior_field = field_read_field($field['field_name'], array('include_inactive' => TRUE, 'include_deleted' => TRUE));
  if (!empty($prior_field)) {
    throw new FieldException(t('Attempt to create field name %name which already exists.', array('%name' => $field['field_name'])));
  }

  $field += array(
    'cardinality' => 1,
    'locked' => FALSE,
    'settings' => array(),
  );
  $module = $field_type['module'];
  // Create all per-field-type properties (needed here as long as we have
  // settings that impact column definitions).
  $field['settings'] += field_info_field_settings($field['type']);
  $field['module'] = $module;
  $field['active'] = 1;
  $field['deleted'] = 0;
  // Create the data table. We need to populate the field columns, even though
  // we don't actually store them.
  $field['columns'] = (array) module_invoke($field['module'], 'field_columns', $field);
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_create_field', $field);

  drupal_write_record('field_config', $field);

  // Clear caches
  field_cache_clear(TRUE);
}

/**
 * Read a single field record directly from the database.  Generally,
 * you should use the field_info_field() instead.
 *
 * @param $field_name
 *   The field name to read.
 * @param array $include_additional
 *   The default behavior of this function is to not return a field that
 *   is inactive or has been deleted. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this
 *   behavior.
 * @return
 *   A field structure, or FALSE.
 */
function field_read_field($field_name, $include_additional = array()) {
  $fields = field_read_fields(array('field_name' => $field_name), $include_additional);
  return $fields ? current($fields) : FALSE;
}

/**
 * Read in fields that match an array of conditions.
 *
 * @param array $params
 *   An array of conditions to match against.
 * @param array $include_additional
 *   The default behavior of this function is to not return fields that
 *   are inactive or have been deleted. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this
 *   behavior.
 * @return
 *   An array of fields matching $params.
 */
function field_read_fields($params = array(), $include_additional = array()) {
  $query = db_select('field_config', 'fc', array('fetch' => PDO::FETCH_ASSOC));
  $query->fields('fc');

  // Turn the conditions into a query.
  foreach ($params as $key => $value) {
    $query->condition($key, $value);
  }
  if (!isset($include_additional['include_inactive']) || !$include_additional['include_inactive']) {
    $query->condition('fc.active', 1);
  }
  if (!isset($include_additional['include_deleted']) || !$include_additional['include_deleted']) {
    $query->condition('fc.deleted', 0);
  }

  $fields = array();
  $results = $query->execute();
  foreach ($results as $field) {
    // drupal_write_record() writes an empty string for empty arrays.
    $field['settings'] = $field['settings'] ? unserialize($field['settings']) : array();

    module_invoke_all('field_read_field', $field);

    // Populate storage columns.
    $field['columns'] = (array) module_invoke($field['module'], 'field_columns', $field);

    $fields[$field['field_name']] = $field;
  }
  return $fields;
}

/**
 * Mark a field for deletion, including all its instances and all data
 * associated with it.
 *
 * @param $field_name
 *   The field name to delete.
 */
function field_delete_field($field_name) {
  // Mark the field for deletion.
  db_update('field_config')
    ->fields(array('deleted' => 1))
    ->condition('field_name', $field_name)
    ->execute();

  // Mark any instances of the field for deletion.
  db_update('field_config_instance')
    ->fields(array('deleted' => 1))
    ->condition('field_name', $field_name)
    ->execute();
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_delete_field', $field_name);
  // Clear the cache.
  field_cache_clear(TRUE);
}

/**
 * Creates an instance of a field, binding it to a bundle.
 *
 * @param $instance
 *   A field instance structure.  The field_name and bundle properties
 *   are required.
 * @throw
 *   FieldException
 */
function field_create_instance($instance) {
  // Check that the specified field exists.
  $field = field_read_field($instance['field_name']);
  if (empty($field)) {
    throw new FieldException("Attempt to create an instance of a field that doesn't exist.");
  }

  // TODO: Check that the specifed bundle exists.

  // TODO: Check that the widget type is known and can handle the field type ?
  // TODO: Check that the formatters are known and can handle the field type ?
  // TODO: Check that the display build modes are known for the object type ?
  // Those checks should probably happen in _field_write_instance() ?
  // Problem : this would mean that a UI module cannot update an instance with a disabled formatter.

  // Ensure the field instance is unique.
  // TODO : do we want specific messages when clashing with a disabled or inactive instance ?
  $prior_instance = field_read_instance($instance['field_name'], $instance['bundle'], array('include_inactive' => TRUE, 'include_deleted' => TRUE));
  if (!empty($prior_instance)) {
    throw new FieldException('Attempt to create a field instance which already exists.');
  }

  _field_write_instance($instance);

  module_invoke_all('field_create_instance', $instance);

  // Clear caches
  field_cache_clear();
  return FALSE;
}

/*
 * Update an instance of a field.
 *
 * @param $instance
 *   An associative array represeting an instance structure. The required
 *   keys and values are:
 *     field_name:  The name of an existing field.
 *     bundle:  The bundle this field belongs to.
 *   Any other properties specified in $instance overwrite the
 *   existing values for the instance.
 * @throw
 *   FieldException
 * @see field_create_instance()
 */
function field_update_instance($instance) {
  // Check that the specified field exists.
  $field = field_read_field($instance['field_name']);
  if (empty($field)) {
    throw new FieldException("Attempt to update an instance of a nonexistent field.");
  }

  // Check that the field instance exists (even if it is inactive, since we
  // want to be able to replace inactive widgets with new ones).
  $prior_instance = field_read_instance($instance['field_name'], $instance['bundle'], array('include_inactive' => TRUE));
  if (empty($prior_instance)) {
    throw new FieldException("Attempt to update a field instance that doesn't exist.");
  }

  _field_write_instance($instance, TRUE);

  // Clear caches.
  field_cache_clear();
}

/**
 * Store an instance record in the field configuration database.
 *
 * @param $instance
 *   An instance structure.
 * @param $update
 *   Whether this is a new or existing instance.
 */
function _field_write_instance($instance, $update = FALSE) {
  $field = field_read_field($instance['field_name']);
  $field_type = field_info_field_types($field['type']);

  // Set defaults.
  $instance += array(
    'settings' => array(),
    'display' => array(),
    'widget' => array(),
    'required' => FALSE,
    'label' => $instance['field_name'],
    'description' => '',
    'weight' => 0,
    'deleted' => 0,
  );

  // Set default instance settings.
  $instance['settings'] += field_info_instance_settings($field['type']);

  // Set default widget and settings.
  $instance['widget'] += array(
    // TODO: what if no 'default_widget' specified ?
    'type' => $field_type['default_widget'],
    'settings' => array(),
  );
  // Check widget module.
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $widget_module = $widget_type['module'];
  $widget_active = module_exists($widget_module);
  $instance['widget']['settings'] += field_info_widget_settings($instance['widget']['type']);
  $instance['widget']['module'] = $widget_module;
  $instance['widget']['active'] = $widget_active;

  // Make sure there is at least display info for the 'full' context.
  $instance['display'] += array(
    'full' => array(),
  );
  // Set default display settings for each context.
  foreach ($instance['display'] as $context => $display) {
    $instance['display'][$context] += array(
      'label' => 'above',
      'exclude' => 0,
      // TODO: what if no 'default_formatter' specified ?
      'type' => $field_type['default_formatter'],
      'settings' => array(),
    );
    $formatter_type = field_info_formatter_types($instance['display'][$context]['type']);
    // TODO : 'hidden' will raise PHP warnings.
    $instance['display'][$context]['module'] = $formatter_type['module'];
    $instance['display'][$context]['settings'] += field_info_formatter_settings($instance['display'][$context]['type']);
  }

  // Create $data to contain everything from $instance that does not
  // have its own column, and thus will be stored serialized.
  $data = $instance;
  unset($data['field_name'], $data['bundle'], $data['widget']['type'], $data['weight'], $data['deleted']);

  $record = array(
    'field_name' => $instance['field_name'],
    'bundle' => $instance['bundle'],
    'widget_type' => $instance['widget']['type'],
    'widget_module' => $widget_module,
    'widget_active' => $widget_active,
    'weight' => $instance['weight'],
    'data' => $data,
    'deleted' => $instance['deleted'],
  );
  // We need to tell drupal_update_record() the primary keys to trigger an
  // update.
  $primary_keys = $update ? array('field_name', 'bundle') : array();
  drupal_write_record('field_config_instance', $record, $primary_keys);
}

/**
 * Read a single instance record directly from the database.  Generally,
 * you should use the field_info_instance() instead.
 *
 * @param $field_name
 *   The field name to read.
 * @param $bundle
 *   The bundle to which the field is bound.
 * @param array $include_additional
 *   The default behavior of this function is to not return an instance that
 *   is inactive or has been deleted. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this
 *   behavior.
 * @return
 *   An instance structure, or FALSE.
 */
function field_read_instance($field_name, $bundle, $include_additional = array()) {
  $instances = field_read_instances(array('field_name' => $field_name, 'bundle' => $bundle), $include_additional);
  return $instances ? current($instances) : FALSE;
}

/**
 * Read in field instances that match an array of conditions.
 *
 * @param $param
 *   An array of properties to use in selecting a field
 *   instance. Valid keys include any column of the
 *   field_config_instance table.  If NULL, all instances will be returned.
 * @param $include_additional
 *   The default behavior of this function is to not return field
 *   instances that are inactive or have been marked deleted. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this
 *   behavior.
 * @return
 *   An array of instances matching the arguments.
 */
function field_read_instances($params = array(), $include_additional = array()) {
  $query = db_select('field_config_instance', 'fci', array('fetch' => PDO::FETCH_ASSOC));
  $query->join('field_config', 'fc', 'fc.field_name = fci.field_name');
  $query->fields('fci');
  #$query->fields('fc', array('type'));

  // Turn the conditions into a query.
  foreach ($params as $key => $value) {
    $query->condition('fci.' . $key, $value);
  }
  $query->condition('fc.active', 1);
  if (!isset($include_additional['include_inactive']) || !$include_additional['include_inactive']) {
    $query->condition('fci.widget_active', 1);
  }
  if (!isset($include_additional['include_deleted']) || !$include_additional['include_deleted']) {
    $query->condition('fc.deleted', 0);
    $query->condition('fci.deleted', 0);
  }

  $instances = array();
  $results = $query->execute();

  foreach ($results as $record) {
    $instance = unserialize($record['data']);
    $instance['field_name'] = $record['field_name'];
    $instance['bundle'] = $record['bundle'];
    $instance['weight'] = $record['weight'];
    $instance['deleted'] = $record['deleted'];
    $instance['widget']['type'] = $record['widget_type'];
    $instance['widget']['module'] = $record['widget_module'];
    $instance['widget']['active'] = $record['widget_active'];

    // TODO D7 : Set default widget settings, default instance settings, default display settings.
    // (the modules that defined them might have changed since the instance was last saved).

    module_invoke_all('field_read_instance', $instance);
    $instances[] = $instance;
  }
  return $instances;
}

/**
 * Mark a field instance for deletion, including all data associated with
 * it.
 *
 * @param $field_name
 *   The name of the field whose instance will be deleted.
 * @param $bundle
 *   The bundle for the instance which will be deleted.
 */
function field_delete_instance($field_name, $bundle) {
  // Mark the field instance for deletion.
  db_update('field_config_instance')
    ->fields(array('deleted' => 1))
    ->condition('field_name', $field_name)
    ->condition('bundle', $bundle)
    ->execute();

  // Mark all data associated with the field for deletion.
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_delete_instance', $field_name, $bundle);
  // Clear the cache.
  field_cache_clear();
}

/**
 * @} End of "defgroup field_crud".
 */