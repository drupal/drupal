<?php

/**
 * @file
 * Field CRUD API, handling field and field instance creation and deletion.
 */

/**
 * @defgroup field_crud Field CRUD API
 * @{
 * Create, update, and delete Field API fields, bundles, and instances.
 *
 * Modules use this API, often in hook_install(), to create custom
 * data structures. UI modules will use it to create a user interface.
 *
 * The Field CRUD API uses
 * @link field Field API data structures @endlink.
 */

/**
 * Creates a field.
 *
 * This function does not bind the field to any bundle; use
 * field_create_instance() for that.
 *
 * @param $field
 *   A field definition array. The field_name and type properties are required.
 *   Other properties, if omitted, will be given the following default values:
 *   - cardinality: 1
 *   - locked: FALSE
 *   - indexes: the field-type indexes, specified by the field type's
 *     hook_field_schema(). The indexes specified in $field are added
 *     to those default indexes. It is possible to override the
 *     definition of a field-type index by providing an index with the
 *     same name, or to remove it by redefining it as an empty array
 *     of columns. Overriding field-type indexes should be done
 *     carefully, for it might seriously affect the site's performance.
 *   - settings: each omitted setting is given the default value defined in
 *     hook_field_info().
 *   - storage:
 *     - type: the storage backend specified in the 'field_storage_default'
 *       system variable.
 *     - settings: each omitted setting is given the default value specified in
 *       hook_field_storage_info().
 * @return
 *   The $field array with the id property filled in.
 * @throw
 *   FieldException
 *
 * See: @link field Field API data structures @endlink.
 */
function field_create_field($field) {
  // Field name is required.
  if (empty($field['field_name'])) {
    throw new FieldException('Attempt to create an unnamed field.');
  }
  // Field type is required.
  if (empty($field['type'])) {
    throw new FieldException('Attempt to create a field with no type.');
  }
  // Field name cannot contain invalid characters.
  if (!preg_match('/^[_a-z]+[_a-z0-9]*$/', $field['field_name'])) {
    throw new FieldException('Attempt to create a field with invalid characters. Only lowercase alphanumeric characters and underscores are allowed, and only lowercase letters and underscore are allowed as the first character');
  }

  // Field name cannot be longer than 32 characters. We use drupal_strlen()
  // because the DB layer assumes that column widths are given in characters,
  // not bytes.
  if (drupal_strlen($field['field_name']) > 32) {
    throw new FieldException(t('Attempt to create a field with a name longer than 32 characters: %name',
      array('%name' => $field['field_name'])));
  }

  // Ensure the field name is unique over active and disabled fields.
  // We do not care about deleted fields.
  $prior_field = field_read_field($field['field_name'], array('include_inactive' => TRUE));
  if (!empty($prior_field)) {
    $message = $prior_field['active']?
      t('Attempt to create field name %name which already exists and is active.', array('%name' => $field['field_name'])):
      t('Attempt to create field name %name which already exists, although it is inactive.', array('%name' => $field['field_name']));
    throw new FieldException($message);
  }

  // Disallow reserved field names. This can't prevent all field name
  // collisions with existing entity properties, but some is better
  // than none.
  foreach (entity_get_info() as $type => $info) {
    if (in_array($field['field_name'], $info['entity keys'])) {
      throw new FieldException(t('Attempt to create field name %name which is reserved by entity type %type.', array('%name' => $field['field_name'], '%type' => $type)));
    }
  }

  $field += array(
    'entity_types' => array(),
    'cardinality' => 1,
    'translatable' => FALSE,
    'locked' => FALSE,
    'settings' => array(),
    'storage' => array(),
    'deleted' => 0,
  );

  // Check that the field type is known.
  $field_type = field_info_field_types($field['type']);
  if (!$field_type) {
    throw new FieldException(t('Attempt to create a field of unknown type %type.', array('%type' => $field['type'])));
  }
  // Create all per-field-type properties (needed here as long as we have
  // settings that impact column definitions).
  $field['settings'] += field_info_field_settings($field['type']);
  $field['module'] = $field_type['module'];
  $field['active'] = 1;

  // Provide default storage.
  $field['storage'] += array(
    'type' => variable_get('field_storage_default', 'field_sql_storage'),
    'settings' => array(),
  );
  // Check that the storage type is known.
  $storage_type = field_info_storage_types($field['storage']['type']);
  if (!$storage_type) {
    throw new FieldException(t('Attempt to create a field with unknown storage type %type.', array('%type' => $field['storage']['type'])));
  }
  // Provide default storage settings.
  $field['storage']['settings'] += field_info_storage_settings($field['storage']['type']);
  $field['storage']['module'] = $storage_type['module'];
  $field['storage']['active'] = 1;
  // Collect storage information.
  module_load_install($field['module']);
  $schema = (array) module_invoke($field['module'], 'field_schema', $field);
  $schema += array('columns' => array(), 'indexes' => array(), 'foreign keys' => array());
  // 'columns' are hardcoded in the field type.
  $field['columns'] = $schema['columns'];
  // 'foreign keys' are hardcoded in the field type.
  $field['foreign keys'] = $schema['foreign keys'];
  // 'indexes' can be both hardcoded in the field type, and specified in the
  // incoming $field definition.
  $field += array(
    'indexes' => array(),
  );
  $field['indexes'] += $schema['indexes'];

  // The serialized 'data' column contains everything from $field that does not
  // have its own column and is not automatically populated when the field is
  // read.
  $data = $field;
  unset($data['columns'], $data['field_name'], $data['type'], $data['active'], $data['module'], $data['storage_type'], $data['storage_active'], $data['storage_module'], $data['locked'], $data['cardinality'], $data['deleted']);
  // Additionally, do not save the 'bundles' property populated by
  // field_info_field().
  unset($data['bundles']);

  $record = array(
    'field_name' => $field['field_name'],
    'type' => $field['type'],
    'module' => $field['module'],
    'active' => $field['active'],
    'storage_type' => $field['storage']['type'],
    'storage_module' => $field['storage']['module'],
    'storage_active' => $field['storage']['active'],
    'locked' => $field['locked'],
    'data' => $data,
    'cardinality' => $field['cardinality'],
    'translatable' => $field['translatable'],
    'deleted' => $field['deleted'],
  );

  // Store the field and get the id back.
  drupal_write_record('field_config', $record);
  $field['id'] = $record['id'];

  // Invoke hook_field_storage_create_field after the field is
  // complete (e.g. it has its id).
  try {
    // Invoke hook_field_storage_create_field after
    // drupal_write_record() sets the field id.
    module_invoke($storage_type['module'], 'field_storage_create_field', $field);
  }
  catch (Exception $e) {
    // If storage creation failed, remove the field_config record before
    // rethrowing the exception.
    db_delete('field_config')
      ->condition('id', $field['id'])
      ->execute();
    throw $e;
  }

  // Clear caches
  field_cache_clear(TRUE);

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_create_field', $field);

  return $field;
}

/**
 * Updates a field.
 *
 * Any module may forbid any update for any reason. For example, the
 * field's storage module might forbid an update if it would change
 * the storage schema while data for the field exists. A field type
 * module might forbid an update if it would change existing data's
 * semantics, or if there are external dependencies on field settings
 * that cannot be updated.
 *
 * @param $field
 *   A field structure. $field['field_name'] must provided; it
 *   identifies the field that will be updated to match this
 *   structure. Any other properties of the field that are not
 *   specified in $field will be left unchanged, so it is not
 *   necessary to pass in a fully populated $field structure.
 * @return
 *   Throws a FieldException if the update cannot be performed.
 * @see field_create_field()
 */
function field_update_field($field) {
  // Check that the specified field exists.
  $prior_field = field_read_field($field['field_name']);
  if (empty($prior_field)) {
    throw new FieldException('Attempt to update a non-existent field.');
  }

  // Use the prior field values for anything not specifically set by the new
  // field to be sure that all values are set.
  $field += $prior_field;
  $field['settings'] += $prior_field['settings'];

  // Some updates are always disallowed.
  if ($field['type'] != $prior_field['type']) {
    throw new FieldException("Cannot change an existing field's type.");
  }
  if ($field['entity_types'] != $prior_field['entity_types']) {
    throw new FieldException("Cannot change an existing field's entity_types property.");
  }
  if ($field['storage']['type'] != $prior_field['storage']['type']) {
    throw new FieldException("Cannot change an existing field's storage type.");
  }

  // Collect the new storage information, since what is in
  // $prior_field may no longer be right.
  module_load_install($field['module']);
  $schema = (array) module_invoke($field['module'], 'field_schema', $field);
  $schema += array('columns' => array(), 'indexes' => array());
  // 'columns' are hardcoded in the field type.
  $field['columns'] = $schema['columns'];
  // 'indexes' can be both hardcoded in the field type, and specified in the
  // incoming $field definition.
  $field += array(
    'indexes' => array(),
  );
  $field['indexes'] += $schema['indexes'];

  $has_data = field_has_data($field);

  // See if any module forbids the update by throwing an exception.
  foreach (module_implements('field_update_forbid') as $module) {
    $function = $module . '_field_update_forbid';
    $function($field, $prior_field, $has_data);
  }

  // Tell the storage engine to update the field. Do this before
  // saving the new definition since it still might fail.
  $storage_type = field_info_storage_types($field['storage']['type']);
  module_invoke($storage_type['module'], 'field_storage_update_field', $field, $prior_field, $has_data);

  // Save the new field definition. @todo: refactor with
  // field_create_field.

  // The serialized 'data' column contains everything from $field that does not
  // have its own column and is not automatically populated when the field is
  // read.
  $data = $field;
  unset($data['columns'], $data['field_name'], $data['type'], $data['locked'], $data['module'], $data['cardinality'], $data['active'], $data['deleted']);
  // Additionally, do not save the 'bundles' property populated by
  // field_info_field().
  unset($data['bundles']);

  $field['data'] = $data;

  // Store the field and create the id.
  $primary_key = array('id');
  drupal_write_record('field_config', $field, $primary_key);

  // Clear caches
  field_cache_clear(TRUE);

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_update_field', $field, $prior_field, $has_data);
}

/**
 * Reads a single field record directly from the database.
 *
 * Generally, you should use the field_info_field() instead.
 *
 * This function will not return deleted fields. Use
 * field_read_fields() instead for this purpose.
 *
 * @param $field_name
 *   The field name to read.
 * @param array $include_additional
 *   The default behavior of this function is to not return a field that
 *   is inactive. Setting
 *   $include_additional['include_inactive'] to TRUE will override this
 *   behavior.
 * @return
 *   A field definition array, or FALSE.
 */
function field_read_field($field_name, $include_additional = array()) {
  $fields = field_read_fields(array('field_name' => $field_name), $include_additional);
  return $fields ? current($fields) : FALSE;
}

/**
 * Reads in fields that match an array of conditions.
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
 *   An array of fields matching $params. If
 *   $include_additional['include_deleted'] is TRUE, the array is keyed
 *   by field id, otherwise it is keyed by field name.
 */
function field_read_fields($params = array(), $include_additional = array()) {
  $query = db_select('field_config', 'fc', array('fetch' => PDO::FETCH_ASSOC));
  $query->fields('fc');

  // Turn the conditions into a query.
  foreach ($params as $key => $value) {
    $query->condition($key, $value);
  }
  if (!isset($include_additional['include_inactive']) || !$include_additional['include_inactive']) {
    $query
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1);
  }
  $include_deleted = (isset($include_additional['include_deleted']) && $include_additional['include_deleted']);
  if (!$include_deleted) {
    $query->condition('fc.deleted', 0);
  }

  $fields = array();
  $results = $query->execute();
  foreach ($results as $record) {
    $field = unserialize($record['data']);
    $field['id'] = $record['id'];
    $field['field_name'] = $record['field_name'];
    $field['type'] = $record['type'];
    $field['module'] = $record['module'];
    $field['active'] = $record['active'];
    $field['storage']['type'] = $record['storage_type'];
    $field['storage']['module'] = $record['storage_module'];
    $field['storage']['active'] = $record['storage_active'];
    $field['locked'] = $record['locked'];
    $field['cardinality'] = $record['cardinality'];
    $field['translatable'] = $record['translatable'];
    $field['deleted'] = $record['deleted'];

    module_invoke_all('field_read_field', $field);

    // Populate storage information.
    module_load_install($field['module']);
    $schema = (array) module_invoke($field['module'], 'field_schema', $field);
    $schema += array('columns' => array(), 'indexes' => array());
    $field['columns'] = $schema['columns'];

    $field_name = $field['field_name'];
    if ($include_deleted) {
      $field_name = $field['id'];
    }
    $fields[$field_name] = $field;
  }
  return $fields;
}

/**
 * Marks a field and its instances and data for deletion.
 *
 * @param $field_name
 *   The field name to delete.
 */
function field_delete_field($field_name) {
  // Delete all non-deleted instances.
  $field = field_info_field($field_name);
  if (isset($field['bundles'])) {
    foreach ($field['bundles'] as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $instance = field_info_instance($entity_type, $field_name, $bundle);
        field_delete_instance($instance, FALSE);
      }
    }
  }

  // Mark field data for deletion.
  module_invoke($field['storage']['module'], 'field_storage_delete_field', $field);

  // Mark the field for deletion.
  db_update('field_config')
    ->fields(array('deleted' => 1))
    ->condition('field_name', $field_name)
    ->execute();

  // Clear the cache.
  field_cache_clear(TRUE);

  module_invoke_all('field_delete_field', $field);
}

/**
 * Creates an instance of a field, binding it to a bundle.
 *
 * @param $instance
 *   A field instance definition array. The field_name, entity_type and
 *   bundle properties are required. Other properties, if omitted,
 *   will be given the following default values:
 *   - label: the field name
 *   - description: empty string
 *   - required: FALSE
 *   - default_value_function: empty string
 *   - settings: each omitted setting is given the default value specified in
 *     hook_field_info().
 *   - widget:
 *     - type: the default widget specified in hook_field_info().
 *     - settings: each omitted setting is given the default value specified in
 *       hook_field_widget_info().
 *   - display:
 *     Settings for the 'default' view mode will be added if not present, and
 *     each view mode in the definition will be completed with the following
 *     default values:
 *     - label: 'above'
 *     - type: the default formatter specified in hook_field_info().
 *     - settings: each omitted setting is given the default value specified in
 *       hook_field_formatter_info().
 *     View modes not present in the definition are left empty, and the field
 *     will not be displayed in this mode.
 *
 * @return
 *   The $instance array with the id property filled in.
 * @throw
 *   FieldException
 *
 * See: @link field Field API data structures @endlink.
 */
function field_create_instance($instance) {
  $field = field_read_field($instance['field_name']);
  if (empty($field)) {
    throw new FieldException(t("Attempt to create an instance of a field @field_name that doesn't exist or is currently inactive.", array('@field_name' => $instance['field_name'])));
  }
  // Check that the required properties exists.
  if (empty($instance['entity_type'])) {
    throw new FieldException(t('Attempt to create an instance of field @field_name without an entity type.', array('@field_name' => $instance['field_name'])));
  }
  if (empty($instance['bundle'])) {
    throw new FieldException(t('Attempt to create an instance of field @field_name without a bundle.', array('@field_name' => $instance['field_name'])));
  }
  // Check that the field can be attached to this entity type.
  if (!empty($field['entity_types']) && !in_array($instance['entity_type'], $field['entity_types'])) {
    throw new FieldException(t('Attempt to create an instance of field @field_name on forbidden entity type @entity_type.', array('@field_name' => $instance['field_name'], '@entity_type' => $instance['entity_type'])));
  }

  // Set the field id.
  $instance['field_id'] = $field['id'];

  // Note that we do *not* prevent creating a field on non-existing bundles,
  // because that would break the 'Body as field' upgrade for contrib
  // node types.

  // TODO: Check that the widget type is known and can handle the field type ?
  // TODO: Check that the formatters are known and can handle the field type ?
  // TODO: Check that the display view modes are known for the entity type ?
  // Those checks should probably happen in _field_write_instance() ?
  // Problem : this would mean that a UI module cannot update an instance with a disabled formatter.

  // Ensure the field instance is unique within the bundle.
  // We only check for instances of active fields, since adding an instance of
  // a disabled field is not supported.
  $prior_instance = field_read_instance($instance['entity_type'], $instance['field_name'], $instance['bundle']);
  if (!empty($prior_instance)) {
    $message = t('Attempt to create an instance of field @field_name on bundle @bundle that already has an instance of that field.', array('@field_name' => $instance['field_name'], '@bundle' => $instance['bundle']));
    throw new FieldException($message);
  }

  _field_write_instance($instance);

  // Clear caches
  field_cache_clear();

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_create_instance', $instance);

  return $instance;
}

/**
 * Updates an instance of a field.
 *
 * @param $instance
 *   An associative array representing an instance structure. The required
 *   keys and values are:
 *   - entity_type: The type of the entity the field is attached to.
 *   - bundle: The bundle this field belongs to.
 *   - field_name: The name of an existing field.
 *   Read-only_id properties are assigned automatically. Any other
 *   properties specified in $instance overwrite the existing values for
 *   the instance.
 *
 * @throw
 *   FieldException
 *
 * @see field_create_instance()
 */
function field_update_instance($instance) {
  // Check that the specified field exists.
  $field = field_read_field($instance['field_name']);
  if (empty($field)) {
    throw new FieldException(t('Attempt to update an instance of a nonexistent field @field.', array('@field' => $instance['field_name'])));
  }

  // Check that the field instance exists (even if it is inactive, since we
  // want to be able to replace inactive widgets with new ones).
  $prior_instance = field_read_instance($instance['entity_type'], $instance['field_name'], $instance['bundle'], array('include_inactive' => TRUE));
  if (empty($prior_instance)) {
    throw new FieldException(t("Attempt to update an instance of field @field on bundle @bundle that doesn't exist.", array('@field' => $instance['field_name'], '@bundle' => $instance['bundle'])));
  }

  $instance['id'] = $prior_instance['id'];
  $instance['field_id'] = $prior_instance['field_id'];

  _field_write_instance($instance, TRUE);

  // Clear caches.
  field_cache_clear();

  module_invoke_all('field_update_instance', $instance, $prior_instance);
}

/**
 * Stores an instance record in the field configuration database.
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
  // If no weight specified, make sure the field sinks at the bottom.
  if (!isset($instance['widget']['weight'])) {
    $max_weight = field_info_max_weight($instance['entity_type'], $instance['bundle'], 'form');
    $instance['widget']['weight'] = isset($max_weight) ? $max_weight + 1 : 0;
  }
  // Check widget module.
  $widget_type = field_info_widget_types($instance['widget']['type']);
  $instance['widget']['module'] = $widget_type['module'];
  $instance['widget']['settings'] += field_info_widget_settings($instance['widget']['type']);

  // Make sure there are at least display settings for the 'default' view mode,
  // and fill in defaults for each view mode specified in the definition.
  $instance['display'] += array(
    'default' => array(),
  );
  foreach ($instance['display'] as $view_mode => $display) {
    $display += array(
      'label' => 'above',
      'type' => isset($field_type['default_formatter']) ? $field_type['default_formatter'] : 'hidden',
      'settings' => array(),
    );
    if ($display['type'] != 'hidden') {
      $formatter_type = field_info_formatter_types($display['type']);
      $display['module'] = $formatter_type['module'];
      $display['settings'] += field_info_formatter_settings($display['type']);
    }
    // If no weight specified, make sure the field sinks at the bottom.
    if (!isset($display['weight'])) {
      $max_weight = field_info_max_weight($instance['entity_type'], $instance['bundle'], $view_mode);
      $display['weight'] = isset($max_weight) ? $max_weight + 1 : 0;
    }
    $instance['display'][$view_mode] = $display;
  }

  // The serialized 'data' column contains everything from $instance that does
  // not have its own column and is not automatically populated when the
  // instance is read.
  $data = $instance;
  unset($data['id'], $data['field_id'], $data['field_name'], $data['entity_type'], $data['bundle'], $data['deleted']);

  $record = array(
    'field_id' => $instance['field_id'],
    'field_name' => $instance['field_name'],
    'entity_type' => $instance['entity_type'],
    'bundle' => $instance['bundle'],
    'data' => $data,
    'deleted' => $instance['deleted'],
  );
  // We need to tell drupal_update_record() the primary keys to trigger an
  // update.
  if ($update) {
    $record['id'] = $instance['id'];
    $primary_key = array('id');
  }
  else {
    $primary_key = array();
  }
  drupal_write_record('field_config_instance', $record, $primary_key);
}

/**
 * Reads a single instance record from the database.
 *
 * Generally, you should use field_info_instance() instead, as it
 * provides caching and allows other modules the opportunity to
 * append additional formatters, widgets, and other information.
 *
 * @param $entity_type
 *   The type of entity to which the field is bound.
 * @param $field_name
 *   The field name to read.
 * @param $bundle
 *   The bundle to which the field is bound.
 * @param array $include_additional
 *   The default behavior of this function is to not return an instance that
 *   has been deleted, or whose field is inactive. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this
 *   behavior.
 * @return
 *   An instance structure, or FALSE.
 */
function field_read_instance($entity_type, $field_name, $bundle, $include_additional = array()) {
  $instances = field_read_instances(array('entity_type' => $entity_type, 'field_name' => $field_name, 'bundle' => $bundle), $include_additional);
  return $instances ? current($instances) : FALSE;
}

/**
 * Reads in field instances that match an array of conditions.
 *
 * @param $param
 *   An array of properties to use in selecting a field
 *   instance. Valid keys include any column of the
 *   field_config_instance table. If NULL, all instances will be returned.
 * @param $include_additional
 *   The default behavior of this function is to not return field
 *   instances that have been marked deleted, or whose field is inactive.
 *   Setting $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this
 *   behavior.
 * @return
 *   An array of instances matching the arguments.
 */
function field_read_instances($params = array(), $include_additional = array()) {
  $include_inactive = isset($include_additional['include_inactive']) && $include_additional['include_inactive'];
  $include_deleted = isset($include_additional['include_deleted']) && $include_additional['include_deleted'];

  $query = db_select('field_config_instance', 'fci', array('fetch' => PDO::FETCH_ASSOC));
  $query->join('field_config', 'fc', 'fc.id = fci.field_id');
  $query->fields('fci');

  // Turn the conditions into a query.
  foreach ($params as $key => $value) {
    $query->condition('fci.' . $key, $value);
  }
  if (!$include_inactive) {
    $query
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1);
  }
  if (!$include_deleted) {
    $query->condition('fc.deleted', 0);
    $query->condition('fci.deleted', 0);
  }

  $instances = array();
  $results = $query->execute();

  foreach ($results as $record) {
    // Filter out instances on unknown entity types (for instance because the
    // module exposing them was disabled).
    $entity_info = entity_get_info($record['entity_type']);
    if ($include_inactive || $entity_info) {
      $instance = unserialize($record['data']);
      $instance['id'] = $record['id'];
      $instance['field_id'] = $record['field_id'];
      $instance['field_name'] = $record['field_name'];
      $instance['entity_type'] = $record['entity_type'];
      $instance['bundle'] = $record['bundle'];
      $instance['deleted'] = $record['deleted'];

      module_invoke_all('field_read_instance', $instance);
      $instances[] = $instance;
    }
  }
  return $instances;
}

/**
 * Marks a field instance and its data for deletion.
 *
 * @param $instance
 *   An instance structure.
 * @param $field_cleanup
 *   If TRUE, the field will be deleted as well if its last instance is being
 *   deleted. If FALSE, it is the caller's responsability to handle the case of
 *   fields left without instances. Defaults to TRUE.
 */
function field_delete_instance($instance, $field_cleanup = TRUE) {
  // Mark the field instance for deletion.
  db_update('field_config_instance')
    ->fields(array('deleted' => 1))
    ->condition('field_name', $instance['field_name'])
    ->condition('entity_type', $instance['entity_type'])
    ->condition('bundle', $instance['bundle'])
    ->execute();

  // Clear the cache.
  field_cache_clear();

  // Mark instance data for deletion.
  $field = field_info_field($instance['field_name']);
  module_invoke($field['storage']['module'], 'field_storage_delete_instance', $instance);

  // Let modules react to the deletion of the instance.
  module_invoke_all('field_delete_instance', $instance);

  // Delete the field itself if we just deleted its last instance.
  if ($field_cleanup && count($field['bundles']) == 0) {
    field_delete_field($field['field_name']);
  }
}

/**
 * @} End of "defgroup field_crud".
 */

/**
 * @defgroup field_purge Field API bulk data deletion
 * @{
 * Clean up after Field API bulk deletion operations.
 *
 * Field API provides functions for deleting data attached to individual
 * entities as well as deleting entire fields or field instances in a single
 * operation.
 *
 * Deleting field data items for an entity with field_attach_delete() involves
 * three separate operations:
 * - Invoking the Field Type API hook_field_delete() for each field on the
 * entity. The hook for each field type receives the entity and the specific
 * field being deleted. A file field module might use this hook to delete
 * uploaded files from the filesystem.
 * - Invoking the Field Storage API hook_field_storage_delete() to remove
 * data from the primary field storage. The hook implementation receives the
 * entity being deleted and deletes data for all of the entity's bundle's
 * fields.
 * - Invoking the global Field Attach API hook_field_attach_delete() for all
 * modules that implement it. Each hook implementation receives the entity
 * being deleted and can operate on whichever subset of the entity's bundle's
 * fields it chooses to.
 *
 * These hooks are invoked immediately when field_attach_delete() is
 * called. Similar operations are performed for field_attach_delete_revision().
 *
 * When a field, bundle, or field instance is deleted, it is not practical to
 * invoke these hooks immediately on every affected entity in a single page
 * request; there could be thousands or millions of them. Instead, the
 * appropriate field data items, instances, and/or fields are marked as deleted
 * so that subsequent load or query operations will not return them. Later, a
 * separate process cleans up, or "purges", the marked-as-deleted data by going
 * through the three-step process described above and, finally, removing
 * deleted field and instance records.
 *
 * Purging field data is made somewhat tricky by the fact that, while
 * field_attach_delete() has a complete entity to pass to the various deletion
 * hooks, the Field API purge process only has the field data it has previously
 * stored. It cannot reconstruct complete original entities to pass to the
 * deletion hooks. It is even possible that the original entity to which some
 * Field API data was attached has been itself deleted before the field purge
 * operation takes place.
 *
 * Field API resolves this problem by using "pseudo-entities" during purge
 * operations. A pseudo-entity contains only the information from the original
 * entity that Field API knows about: entity type, id, revision id, and
 * bundle. It also contains the field data for whichever field instance is
 * currently being purged. For example, suppose that the node type 'story' used
 * to contain a field called 'subtitle' but the field was deleted. If node 37
 * was a story with a subtitle, the pseudo-entity passed to the purge hooks
 * would look something like this:
 *
 * @code
 *   $entity = stdClass Object(
 *     [nid] => 37,
 *     [vid] => 37,
 *     [type] => 'story',
 *     [subtitle] => array(
 *       [0] => array(
 *         'value' => 'subtitle text',
 *       ),
 *     ),
 *   );
 * @endcode
 */

/**
 * Purges a batch of deleted Field API data, instances, or fields.
 *
 * This function will purge deleted field data on up to a specified maximum
 * number of entities and then return. If a deleted field instance with no
 * remaining data records is found, the instance itself will be purged.
 * If a deleted field with no remaining field instances is found, the field
 * itself will be purged.
 *
 * @param $batch_size
 *   The maximum number of field data records to purge before returning.
 */
function field_purge_batch($batch_size) {
  // Retrieve all deleted field instances. We cannot use field_info_instances()
  // because that function does not return deleted instances.
  $instances = field_read_instances(array('deleted' => 1), array('include_deleted' => 1));

  foreach ($instances as $instance) {
    // field_purge_data() will need the field array.
    $field = field_info_field_by_id($instance['field_id']);
    // Retrieve some entities.
    $query = new EntityFieldQuery();
    $results = $query
      ->fieldCondition($field)
      ->entityCondition('bundle', $instance['bundle'])
      ->deleted(TRUE)
      ->range(0, $batch_size)
      ->execute();

    if ($results) {
      foreach ($results as $entity_type => $stub_entities) {
        field_attach_load($entity_type, $stub_entities, FIELD_LOAD_CURRENT, array('field_id' => $field['id'], 'deleted' => 1));
        foreach ($stub_entities as $stub_entity) {
          // Purge the data for the entity.
          field_purge_data($entity_type, $stub_entity, $field, $instance);
        }
      }
    }
    else {
      // No field data remains for the instance, so we can remove it.
      field_purge_instance($instance);
    }
  }

  // Retrieve all deleted fields. Any that have no instances can be purged.
  $fields = field_read_fields(array('deleted' => 1), array('include_deleted' => 1));
  foreach ($fields as $field) {
    $instances = field_read_instances(array('field_id' => $field['id']), array('include_deleted' => 1));
    if (empty($instances)) {
      field_purge_field($field);
    }
  }
}

/**
 * Purges the field data for a single field on a single pseudo-entity.
 *
 * This is basically the same as field_attach_delete() except it only applies
 * to a single field. The entity itself is not being deleted, and it is quite
 * possible that other field data will remain attached to it.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The pseudo-entity whose field data is being purged.
 * @param $field
 *   The (possibly deleted) field whose data is being purged.
 * @param $instance
 *   The deleted field instance whose data is being purged.
 */
function field_purge_data($entity_type, $entity, $field, $instance) {
  // Each field type's hook_field_delete() only expects to operate on a single
  // field at a time, so we can use it as-is for purging.
  $options = array('field_id' => $instance['field_id'], 'deleted' => TRUE);
  _field_invoke('delete', $entity_type, $entity, $dummy, $dummy, $options);

  // Tell the field storage system to purge the data.
  module_invoke($field['storage']['module'], 'field_storage_purge', $entity_type, $entity, $field, $instance);

  // Let other modules act on purging the data.
  foreach (module_implements('field_attach_purge') as $module) {
    $function = $module . '_field_attach_purge';
    $function($entity_type, $entity, $field, $instance);
  }
}

/**
 * Purges a field instance record from the database.
 *
 * This function assumes all data for the instance has already been purged, and
 * should only be called by field_purge_batch().
 *
 * @param $instance
 *   The instance record to purge.
 */
function field_purge_instance($instance) {
  db_delete('field_config_instance')
    ->condition('id', $instance['id'])
    ->execute();

  // Notify the storage engine.
  $field = field_info_field_by_id($instance['field_id']);
  module_invoke($field['storage']['module'], 'field_storage_purge_instance', $instance);

  // Clear the cache.
  field_info_cache_clear();

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_purge_instance', $instance);
}

/**
 * Purges a field record from the database.
 *
 * This function assumes all instances for the field has already been purged,
 * and should only be called by field_purge_batch().
 *
 * @param $field
 *   The field record to purge.
 */
function field_purge_field($field) {
  $instances = field_read_instances(array('field_id' => $field['id']), array('include_deleted' => 1));
  if (count($instances) > 0) {
    throw new FieldException(t('Attempt to purge a field @field_name that still has instances.', array('@field_name' => $field['field_name'])));
  }

  db_delete('field_config')
    ->condition('id', $field['id'])
    ->execute();

  // Notify the storage engine.
  module_invoke($field['storage']['module'], 'field_storage_purge_field', $field);

  // Clear the cache.
  field_info_cache_clear();

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_purge_field', $field);
}

/**
 * @} End of "defgroup field_purge".
 */
