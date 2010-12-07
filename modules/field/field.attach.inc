<?php
// $Id: field.attach.inc,v 1.98 2010/12/07 05:09:58 webchick Exp $

/**
 * @file
 * Field attach API, allowing entities (nodes, users, ...) to be 'fieldable'.
 */

/**
 * Exception thrown by field_attach_validate() on field validation errors.
 */
class FieldValidationException extends FieldException {
  var $errors;

 /**
  * Constructor for FieldValidationException.
  *
  * @param $errors
  *   An array of field validation errors, keyed by field name and
  *   delta that contains two keys:
  *   - 'error': A machine-readable error code string, prefixed by
  *     the field module name. A field widget may use this code to decide
  *     how to report the error.
  *   - 'message': A human-readable error message such as to be
  *     passed to form_error() for the appropriate form element.
  */
  function __construct($errors) {
    $this->errors = $errors;
    parent::__construct(t('Field validation errors'));
  }
}

/**
 * @defgroup field_storage Field Storage API
 * @{
 * Implement a storage engine for Field API data.
 *
 * The Field Attach API uses the Field Storage API to perform all "database
 * access". Each Field Storage API hook function defines a primitive database
 * operation such as read, write, or delete. The default field storage module,
 * field_sql_storage.module, uses the local SQL database to implement these
 * operations, but alternative field storage backends can choose to represent
 * the data in SQL differently or use a completely different storage mechanism
 * such as a cloud-based database.
 *
 * Each field defines which storage backend it uses. The Drupal system variable
 * 'field_default_storage' identifies the storage backend used by default.
 */

/**
 * Argument for an update operation.
 *
 * This is used in hook_field_storage_write when updating an
 * existing entity.
 */
define('FIELD_STORAGE_UPDATE', 'update');

/**
 * Argument for an insert operation.
 *
 * This is used in hook_field_storage_write when inserting a new entity.
 */
define('FIELD_STORAGE_INSERT', 'insert');

/**
 * @} End of "defgroup field_storage"
 */

/**
 * @defgroup field_attach Field Attach API
 * @{
 * Operate on Field API data attached to Drupal entities.
 *
 * Field Attach API functions load, store, display, generate Form API
 * structures, and perform a variety of other functions for field data attached
 * to individual entities.
 *
 * Field Attach API functions generally take $entity_type and $entity arguments
 * along with additional function-specific arguments. $entity_type is the type
 * of the fieldable entity, such as 'node' or 'user', and $entity is the entity
 * itself.
 *
 * hook_entity_info() is the central place for entity types to define if and
 * how Field API should operate on their entity objects. Notably, the
 * 'fieldable' property needs to be set to TRUE.
 *
 * The Field Attach API uses the concept of bundles: the set of fields for a
 * given entity is defined on a per-bundle basis. The collection of bundles for
 * an entity type is defined its hook_entity_info() implementation. For
 * instance, node_entity_info() exposes each node type as its own bundle. This
 * means that the set of fields of a node is determined by the node type. The
 * Field API reads the bundle name for a given entity from a particular
 * property of the entity object, and hook_entity_info() defines which property
 * to use. For instance, node_entity_info() specifies:
 * @code $info['entity keys']['bundle'] = 'type'@endcode
 * This indicates that for a particular node object, the bundle name can be
 * found in $node->type. This property can be omitted if the entity type only
 * exposes a single bundle (all entities of this type have the same collection
 * of fields). This is the case for the 'user' entity type.
 *
 * Most Field Attach API functions define a corresponding hook function that
 * allows any module to act on Field Attach operations for any entity after the
 * operation is complete, and access or modify all the field, form, or display
 * data for that entity and operation. For example, field_attach_view() invokes
 * hook_field_attach_view_alter(). These all-module hooks are distinct from
 * those of the Field Types API, such as hook_field_load(), that are only
 * invoked for the module that defines a specific field type.
 *
 * field_attach_load(), field_attach_insert(), and field_attach_update() also
 * define pre-operation hooks, e.g. hook_field_attach_pre_load(). These hooks
 * run before the corresponding Field Storage API and Field Type API
 * operations. They allow modules to define additional storage locations (e.g.
 * denormalizing, mirroring) for field data on a per-field basis. They also
 * allow modules to take over field storage completely by instructing other
 * implementations of the same hook and the Field Storage API itself not to
 * operate on specified fields.
 *
 * The pre-operation hooks do not make the Field Storage API irrelevant. The
 * Field Storage API is essentially the "fallback mechanism" for any fields
 * that aren't being intercepted explicitly by pre-operation hooks.
 */

/**
 * Invoke a field hook.
 *
 * @param $op
 *   Possible operations include:
 *   - form
 *   - validate
 *   - presave
 *   - insert
 *   - update
 *   - delete
 *   - delete revision
 *   - view
 *   - prepare translation
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The fully formed $entity_type entity.
 * @param $a
 *   - The $form in the 'form' operation.
 *   - The value of $view_mode in the 'view' operation.
 *   - Otherwise NULL.
 * @param $b
 *   - The $form_state in the 'submit' operation.
 *   - Otherwise NULL.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *  - 'field_name': The name of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the entity's bundle. NOTE: This option is not compatible with
 *    the 'deleted' option; the 'field_id' option should be used
 *    instead.
 *  - 'field_id': The id of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the entity's' bundles.
 *  - 'default': A boolean value, specifying which implementation of
 *    the operation should be invoked.
 *    - if FALSE (default), the field types implementation of the operation
 *      will be invoked (hook_field_[op])
 *    - If TRUE, the default field implementation of the field operation
 *      will be invoked (field_default_[op])
 *    Internal use only. Do not explicitely set to TRUE, but use
 *    _field_invoke_default() instead.
 *  - 'deleted': If TRUE, the function will operate on deleted fields
 *    as well as non-deleted fields. If unset or FALSE, only
 *    non-deleted fields are operated on.
 *  - 'language': A language code or an array of language codes keyed by field
 *    name. It will be used to narrow down to a single value the available
 *    languages to act on.
 */
function _field_invoke($op, $entity_type, $entity, &$a = NULL, &$b = NULL, $options = array()) {
  // Merge default options.
  $default_options = array(
    'default' => FALSE,
    'deleted' => FALSE,
    'language' => NULL,
  );
  $options += $default_options;

  // Determine the list of instances to iterate on.
  list(, , $bundle) = entity_extract_ids($entity_type, $entity);
  $instances = _field_invoke_get_instances($entity_type, $bundle, $options);

  // Iterate through the instances and collect results.
  $return = array();
  foreach ($instances as $instance) {
    $field_name = $instance['field_name'];
    $field = field_info_field($field_name);
    $function = $options['default'] ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
    if (function_exists($function)) {
      // Determine the list of languages to iterate on.
      $available_languages = field_available_languages($entity_type, $field);
      $languages = _field_language_suggestion($available_languages, $options['language'], $field_name);

      foreach ($languages as $langcode) {
        $items = isset($entity->{$field_name}[$langcode]) ? $entity->{$field_name}[$langcode] : array();
        $result = $function($entity_type, $entity, $field, $instance, $langcode, $items, $a, $b);
        if (isset($result)) {
          // For hooks with array results, we merge results together.
          // For hooks with scalar results, we collect results in an array.
          if (is_array($result)) {
            $return = array_merge($return, $result);
          }
          else {
            $return[] = $result;
          }
        }

        // Populate $items back in the field values, but avoid replacing missing
        // fields with an empty array (those are not equivalent on update).
        if ($items !== array() || isset($entity->{$field_name}[$langcode])) {
          $entity->{$field_name}[$langcode] = $items;
        }
      }
    }
  }

  return $return;
}

/**
 * Invoke a field hook across fields on multiple entities.
 *
 * @param $op
 *   Possible operations include:
 *   - load
 *   - prepare_view
 *   For all other operations, use _field_invoke() / field_invoke_default()
 *   instead.
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entities
 *   An array of entities, keyed by entity id.
 * @param $a
 *   - The $age parameter in the 'load' operation.
 *   - Otherwise NULL.
 * @param $b
 *   Currently always NULL.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *  - 'field_name': The name of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the entity's bundle. NOTE: This option is not compatible with
 *    the 'deleted' option; the 'field_id' option should be used instead.
 *  - 'field_id': The id of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the entity's' bundles.
 *  - 'default': A boolean value, specifying which implementation of
 *    the operation should be invoked.
 *    - if FALSE (default), the field types implementation of the operation
 *      will be invoked (hook_field_[op])
 *    - If TRUE, the default field implementation of the field operation
 *      will be invoked (field_default_[op])
 *    Internal use only. Do not explicitely set to TRUE, but use
 *    _field_invoke_multiple_default() instead.
 *  - 'deleted': If TRUE, the function will operate on deleted fields
 *    as well as non-deleted fields. If unset or FALSE, only
 *    non-deleted fields are operated on.
 *  - 'language': A language code or an array of language codes keyed by field
 *    name. It will be used to narrow down to a single value the available
 *    languages to act on.
 *
 * @return
 *   An array of returned values keyed by entity id.
 */
function _field_invoke_multiple($op, $entity_type, $entities, &$a = NULL, &$b = NULL, $options = array()) {
  // Merge default options.
  $default_options = array(
    'default' => FALSE,
    'deleted' => FALSE,
    'language' => NULL,
  );
  $options += $default_options;
  $field_info = field_info_field_by_ids();

  $fields = array();
  $grouped_instances = array();
  $grouped_entities = array();
  $grouped_items = array();
  $return = array();

  // Go through the entities and collect the fields on which the hook should be
  // invoked.
  //
  // We group fields by id, not by name, because this function can operate on
  // deleted fields which may have non-unique names. However, entities can only
  // contain data for a single field for each name, even if that field
  // is deleted, so we reference field data via the
  // $entity->$field_name property.
  foreach ($entities as $entity) {
    // Determine the list of instances to iterate on.
    list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
    $instances = _field_invoke_get_instances($entity_type, $bundle, $options);

    foreach ($instances as $instance) {
      $field_id = $instance['field_id'];
      $field_name = $instance['field_name'];
      $field = $field_info[$field_id];
      $function = $options['default'] ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
      if (function_exists($function)) {
        // Add the field to the list of fields to invoke the hook on.
        if (!isset($fields[$field_id])) {
          $fields[$field_id] = $field;
        }
        // Group the corresponding instances and entities.
        $grouped_instances[$field_id][$id] = $instance;
        $grouped_entities[$field_id][$id] = $entities[$id];
        // Extract the field values into a separate variable, easily accessed
        // by hook implementations.
        // Unless a language suggestion is provided we iterate on all the
        // available languages.
        $available_languages = field_available_languages($entity_type, $field);
        $languages = _field_language_suggestion($available_languages, $options['language'], $field_name);
        foreach ($languages as $langcode) {
          $grouped_items[$field_id][$langcode][$id] = isset($entity->{$field_name}[$langcode]) ? $entity->{$field_name}[$langcode] : array();
        }
      }
    }
    // Initialize the return value for each entity.
    $return[$id] = array();
  }

  // For each field, invoke the field hook and collect results.
  foreach ($fields as $field_id => $field) {
    $field_name = $field['field_name'];
    $function = $options['default'] ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
    // Iterate over all the field translations.
    foreach ($grouped_items[$field_id] as $langcode => $items) {
      $results = $function($entity_type, $grouped_entities[$field_id], $field, $grouped_instances[$field_id], $langcode, $grouped_items[$field_id][$langcode], $a, $b);
      if (isset($results)) {
        // Collect results by entity.
        // For hooks with array results, we merge results together.
        // For hooks with scalar results, we collect results in an array.
        foreach ($results as $id => $result) {
          if (is_array($result)) {
            $return[$id] = array_merge($return[$id], $result);
          }
          else {
            $return[$id][] = $result;
          }
        }
      }
    }

    // Populate field values back in the entities, but avoid replacing missing
    // fields with an empty array (those are not equivalent on update).
    foreach ($grouped_entities[$field_id] as $id => $entity) {
      foreach ($grouped_items[$field_id] as $langcode => $items) {
        if ($grouped_items[$field_id][$langcode][$id] !== array() || isset($entity->{$field_name}[$langcode])) {
          $entity->{$field_name}[$langcode] = $grouped_items[$field_id][$langcode][$id];
        }
      }
    }
  }

  return $return;
}

/**
 * Invoke field.module's version of a field hook.
 *
 * This function invokes the field_default_[op]() function.
 * Use _field_invoke() to invoke the field type implementation,
 * hook_field_[op]().
 *
 * @see _field_invoke()
 */
function _field_invoke_default($op, $entity_type, $entity, &$a = NULL, &$b = NULL, $options = array()) {
  $options['default'] = TRUE;
  return _field_invoke($op, $entity_type, $entity, $a, $b, $options);
}

/**
 * Invoke field.module's version of a field hook on multiple entities.
 *
 * This function invokes the field_default_[op]() function.
 * Use _field_invoke_multiple() to invoke the field type implementation,
 * hook_field_[op]().
 *
 * @see _field_invoke_multiple()
 */
function _field_invoke_multiple_default($op, $entity_type, $entities, &$a = NULL, &$b = NULL, $options = array()) {
  $options['default'] = TRUE;
  return _field_invoke_multiple($op, $entity_type, $entities, $a, $b, $options);
}

/**
 * Helper for _field_invoke(): retrieves a list of instances to operate on.
 *
 * @param $entity_type
 *   The entity type.
 * @param $bundle
 *   The bundle name.
 * @param $options
 *   An associative array of options, as provided to _field_invoke(). Only the
 *   following keys are considered :
 *   - deleted
 *   - field_name
 *   - field_id
 *   See _field_invoke() for details.
 *
 * @return
 *   The array of selected instance definitions.
 */
function _field_invoke_get_instances($entity_type, $bundle, $options) {
  if ($options['deleted']) {
    // Deleted fields are not included in field_info_instances(), and need to
    // be fetched from the database with field_read_instances().
    $params = array('entity_type' => $entity_type, 'bundle' => $bundle);
    if (isset($options['field_id'])) {
      // Single-field mode by field id: field_read_instances() does the filtering.
      // Single-field mode by field name is not compatible with the 'deleted'
      // option.
      $params['field_id'] = $options['field_id'];
    }
    $instances = field_read_instances($params, array('include_deleted' => TRUE));
  }
  elseif (isset($options['field_name'])) {
    // Single-field mode by field name: field_info_instance() does the
    // filtering.
    $instances = array(field_info_instance($entity_type, $options['field_name'], $bundle));
  }
  else {
    $instances = field_info_instances($entity_type, $bundle);
    if (isset($options['field_id'])) {
      // Single-field mode by field id: we need to loop on each instance to
      // find the right one.
      foreach ($instances as $instance) {
        if ($instance['field_id'] == $options['field_id']) {
          $instances = array($instance);
          break;
        }
      }
    }
  }

  return $instances;
}

/**
 * Add form elements for all fields for an entity to a form structure.
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
 * field_attach_form_validate() and field_attach_submit() on the same $form
 * element.
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
 *   // The sub-array is nested into a $langcode key where $langcode has the
 *   // same value of the $langcode parameter above.
 *   // The '#language' key holds the same value of $langcode and it is used
 *   // to access the field sub-array when $langcode is unknown.
 *   'field_foo' => array(
 *     '#tree' => TRUE,
 *     '#field_name' => The name of the field,
 *     '#language' => $langcode,
 *     $langcode => array(
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
 *         '#columns' => The array of field columns,
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
 *       '#columns'  => array_keys($field['columns']),
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
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
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
 *
 * @see field_form_get_state()
 * @see field_form_set_state()
 */
function field_attach_form($entity_type, $entity, &$form, &$form_state, $langcode = NULL) {
  // Set #parents to 'top-level' by default.
  $form += array('#parents' => array());

  // If no language is provided use the default site language.
  $options = array('language' => field_valid_language($langcode));
  $form += (array) _field_invoke_default('form', $entity_type, $entity, $form, $form_state, $options);

  // Add custom weight handling.
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  $form['#pre_render'][] = '_field_extra_fields_pre_render';
  $form['#entity_type'] = $entity_type;
  $form['#bundle'] = $bundle;

  // Let other modules make changes to the form.
  // Avoid module_invoke_all() to let parameters be taken by reference.
  foreach (module_implements('field_attach_form') as $module) {
    $function = $module . '_field_attach_form';
    $function($entity_type, $entity, $form, $form_state, $langcode);
  }
}

/**
 * Loads fields for the current revisions of a group of entities.
 *
 * Loads all fields for each entity object in a group of a single entity type.
 * The loaded field values are added directly to the entity objects.
 *
 * field_attach_load() is automatically called by the default entity controller
 * class, and thus, in most cases, doesn't need to be explicitly called by the
 * entity type module.
 *
 * @param $entity_type
 *   The type of $entity; e.g., 'node' or 'user'.
 * @param $entities
 *   An array of entities for which to load fields, keyed by entity ID.
 *   Each entity needs to have its 'bundle', 'id' and (if applicable)
 *   'revision' keys filled in. The function adds the loaded field data
 *   directly in the entity objects of the $entities array.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all
 *   fields, or FIELD_LOAD_REVISION to load the version indicated by
 *   each entity. Defaults to FIELD_LOAD_CURRENT; use
 *   field_attach_load_revision() instead of passing FIELD_LOAD_REVISION.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - 'field_id': The field ID that should be loaded, instead of
 *     loading all fields, for each entity. Note that returned entities
 *     may contain data for other fields, for example if they are read
 *     from a cache.
 *   - 'deleted': If TRUE, the function will operate on deleted fields
 *     as well as non-deleted fields. If unset or FALSE, only
 *     non-deleted fields are operated on.
 */
function field_attach_load($entity_type, $entities, $age = FIELD_LOAD_CURRENT, $options = array()) {
  $field_info = field_info_field_by_ids();
  $load_current = $age == FIELD_LOAD_CURRENT;

  // Merge default options.
  $default_options = array(
    'deleted' => FALSE,
  );
  $options += $default_options;

  $info = entity_get_info($entity_type);
  // Only the most current revision of non-deleted fields for cacheable entity
  // types can be cached.
  $cache_read = $load_current && $info['field cache'] && empty($options['deleted']);
  // In addition, do not write to the cache when loading a single field.
  $cache_write = $cache_read && !isset($options['field_id']);

  if (empty($entities)) {
    return;
  }

  // Assume all entities will need to be queried. Entities found in the cache
  // will be removed from the list.
  $queried_entities = $entities;

  // Fetch available entities from cache, if applicable.
  if ($cache_read) {
    // Build the list of cache entries to retrieve.
    $cids = array();
    foreach ($entities as $id => $entity) {
      $cids[] = "field:$entity_type:$id";
    }
    $cache = cache_get_multiple($cids, 'cache_field');
    // Put the cached field values back into the entities and remove them from
    // the list of entities to query.
    foreach ($entities as $id => $entity) {
      $cid = "field:$entity_type:$id";
      if (isset($cache[$cid])) {
        unset($queried_entities[$id]);
        foreach ($cache[$cid]->data as $field_name => $values) {
          $entity->$field_name = $values;
        }
      }
    }
  }

  // Fetch other entities from their storage location.
  if ($queried_entities) {
    // The invoke order is:
    // - hook_field_storage_pre_load()
    // - storage backend's hook_field_storage_load()
    // - field-type module's hook_field_load()
    // - hook_field_attach_load()

    // Invoke hook_field_storage_pre_load(): let any module load field
    // data before the storage engine, accumulating along the way.
    $skip_fields = array();
    foreach (module_implements('field_storage_pre_load') as $module) {
      $function = $module . '_field_storage_pre_load';
      $function($entity_type, $queried_entities, $age, $skip_fields, $options);
    }

    $instances = array();

    // Collect the storage backends used by the remaining fields in the entities.
    $storages = array();
    foreach ($queried_entities as $entity) {
      list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
      $instances = _field_invoke_get_instances($entity_type, $bundle, $options);

      foreach ($instances as $instance) {
        $field_name = $instance['field_name'];
        $field_id = $instance['field_id'];
        // Make sure all fields are present at least as empty arrays.
        if (!isset($queried_entities[$id]->{$field_name})) {
          $queried_entities[$id]->{$field_name} = array();
        }
        // Collect the storage backend if the field has not been loaded yet.
        if (!isset($skip_fields[$field_id])) {
          $field = $field_info[$field_id];
          $storages[$field['storage']['type']][$field_id][] = $load_current ? $id : $vid;
        }
      }
    }

    // Invoke hook_field_storage_load() on the relevant storage backends.
    foreach ($storages as $storage => $fields) {
      $storage_info = field_info_storage_types($storage);
      module_invoke($storage_info['module'], 'field_storage_load', $entity_type, $queried_entities, $age, $fields, $options);
    }

    // Invoke field-type module's hook_field_load().
    _field_invoke_multiple('load', $entity_type, $queried_entities, $age, $options);

    // Invoke hook_field_attach_load(): let other modules act on loading the
    // entitiy.
    module_invoke_all('field_attach_load', $entity_type, $queried_entities, $age, $options);

    // Build cache data.
    if ($cache_write) {
      foreach ($queried_entities as $id => $entity) {
        $data = array();
        list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
        $instances = field_info_instances($entity_type, $bundle);
        foreach ($instances as $instance) {
          $data[$instance['field_name']] = $queried_entities[$id]->{$instance['field_name']};
        }
        $cid = "field:$entity_type:$id";
        cache_set($cid, $data, 'cache_field');
      }
    }
  }
}

/**
 * Load all fields for previous versions of a group of entities.
 *
 * Loading different versions of the same entities is not supported, and should
 * be done by separate calls to the function.
 *
 * field_attach_load_revision() is automatically called by the default entity
 * controller class, and thus, in most cases, doesn't need to be explicitly
 * called by the entity type module.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entities
 *   An array of entities for which to load fields, keyed by entity ID. Each
 *   entity needs to have its 'bundle', 'id' and (if applicable) 'revision'
 *   keys filled. The function adds the loaded field data directly in the
 *   entity objects of the $entities array.
 * @param $options
 *   An associative array of additional options. See field_attach_load() for
 *   details.
 */
function field_attach_load_revision($entity_type, $entities, $options = array()) {
  return field_attach_load($entity_type, $entities, FIELD_LOAD_REVISION, $options);
}

/**
 * Perform field validation against the field data in an entity.
 *
 * This function does not perform field widget validation on form
 * submissions. It is intended to be called during API save
 * operations. Use field_attach_form_validate() to validate form
 * submissions.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to validate.
 * @throws FieldValidationException
 *   If validation errors are found, a FieldValidationException is thrown. The
 *   'errors' property contains the array of errors, keyed by field name,
 *   language and delta.
 */
function field_attach_validate($entity_type, $entity) {
  $errors = array();
  // Check generic, field-type-agnostic errors first.
  _field_invoke_default('validate', $entity_type, $entity, $errors);
  // Check field-type specific errors.
  _field_invoke('validate', $entity_type, $entity, $errors);

  // Let other modules validate the entity.
  // Avoid module_invoke_all() to let $errors be taken by reference.
  foreach (module_implements('field_attach_validate') as $module) {
    $function = $module . '_field_attach_validate';
    $function($entity_type, $entity, $errors);
  }

  if ($errors) {
    throw new FieldValidationException($errors);
  }
}

/**
 * Perform field validation against form-submitted field values.
 *
 * There are two levels of validation for fields in forms: widget
 * validation, and field validation.
 * - Widget validation steps are specific to a given widget's own form
 * structure and UI metaphors. They are executed through FAPI's
 * #element_validate property during normal form validation.
 * - Field validation steps are common to a given field type, independently of
 * the specific widget being used in a given form. They are defined in the
 * field type's implementation of hook_field_validate().
 *
 * This function performs field validation in the context of a form
 * submission. It converts field validation errors into form errors
 * on the correct form elements. Fieldable entity types should call
 * this function during their own form validation function.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity being submitted. The 'bundle', 'id' and (if applicable)
 *   'revision' keys should be present. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure where field elements are attached to. This might be a
 *   full form structure, or a sub-element of a larger form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 */
function field_attach_form_validate($entity_type, $entity, $form, &$form_state) {
  // Extract field values from submitted values.
  _field_invoke_default('extract_form_values', $entity_type, $entity, $form, $form_state);

  // Perform field_level validation.
  try {
    field_attach_validate($entity_type, $entity);
  }
  catch (FieldValidationException $e) {
    // Pass field-level validation errors back to widgets for accurate error
    // flagging.
    foreach ($e->errors as $field_name => $field_errors) {
      foreach ($field_errors as $langcode => $errors) {
        $field_state = field_form_get_state($form['#parents'], $field_name, $langcode, $form_state);
        $field_state['errors'] = $errors;
        field_form_set_state($form['#parents'], $field_name, $langcode, $form_state, $field_state);
      }
    }
    _field_invoke_default('form_errors', $entity_type, $entity, $form, $form_state);
  }
}

/**
 * Perform necessary operations on field data submitted by a form.
 *
 * Currently, this accounts for drag-and-drop reordering of
 * field values, and filtering of empty values.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity being submitted. The 'bundle', 'id' and (if applicable)
 *   'revision' keys should be present. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure where field elements are attached to. This might be a
 *   full form structure, or a sub-element of a larger form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 */
function field_attach_submit($entity_type, $entity, $form, &$form_state) {
  // Extract field values from submitted values.
  _field_invoke_default('extract_form_values', $entity_type, $entity, $form, $form_state);

  _field_invoke_default('submit', $entity_type, $entity, $form, $form_state);

  // Let other modules act on submitting the entity.
  // Avoid module_invoke_all() to let $form_state be taken by reference.
  foreach (module_implements('field_attach_submit') as $module) {
    $function = $module . '_field_attach_submit';
    $function($entity_type, $entity, $form, $form_state);
  }
}

/**
 * Perform necessary operations just before fields data get saved.
 *
 * We take no specific action here, we just give other
 * modules the opportunity to act.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to process.
 */
function field_attach_presave($entity_type, $entity) {
  _field_invoke('presave', $entity_type, $entity);

  // Let other modules act on presaving the entity.
  module_invoke_all('field_attach_presave', $entity_type, $entity);
}

/**
 * Save field data for a new entity.
 *
 * The passed in entity must already contain its id and (if applicable)
 * revision id attributes.
 * Default values (if any) will be saved for fields not present in the
 * $entity.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to save.
 * @return
 *   Default values (if any) will be added to the $entity parameter for fields
 *   it leaves unspecified.
 */
function field_attach_insert($entity_type, $entity) {
  _field_invoke_default('insert', $entity_type, $entity);
  _field_invoke('insert', $entity_type, $entity);

  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Let any module insert field data before the storage engine, accumulating
  // saved fields along the way.
  $skip_fields = array();
  foreach (module_implements('field_storage_pre_insert') as $module) {
    $function = $module . '_field_storage_pre_insert';
    $function($entity_type, $entity, $skip_fields);
  }

  // Collect the storage backends used by the remaining fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $field_name = $field['field_name'];
    if (!empty($entity->$field_name)) {
      // Collect the storage backend if the field has not been written yet.
      if (!isset($skip_fields[$field_id])) {
        $storages[$field['storage']['type']][$field_id] = $field_id;
      }
    }
  }

  // Field storage backends save any remaining unsaved fields.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_write', $entity_type, $entity, FIELD_STORAGE_INSERT, $fields);
  }

  // Let other modules act on inserting the entity.
  module_invoke_all('field_attach_insert', $entity_type, $entity);

  $entity_info = entity_get_info($entity_type);
}

/**
 * Save field data for an existing entity.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to save.
 */
function field_attach_update($entity_type, $entity) {
  _field_invoke('update', $entity_type, $entity);

  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Let any module update field data before the storage engine, accumulating
  // saved fields along the way.
  $skip_fields = array();
  foreach (module_implements('field_storage_pre_update') as $module) {
    $function = $module . '_field_storage_pre_update';
    $function($entity_type, $entity, $skip_fields);
  }

  // Collect the storage backends used by the remaining fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $field_name = $field['field_name'];
    // Leave the field untouched if $entity comes with no $field_name property,
    // but empty the field if it comes as a NULL value or an empty array.
    // Function property_exists() is slower, so we catch the more frequent
    // cases where it's an empty array with the faster isset().
    if (isset($entity->$field_name) || property_exists($entity, $field_name)) {
      // Collect the storage backend if the field has not been written yet.
      if (!isset($skip_fields[$field_id])) {
        $storages[$field['storage']['type']][$field_id] = $field_id;
      }
    }
  }

  // Field storage backends save any remaining unsaved fields.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_write', $entity_type, $entity, FIELD_STORAGE_UPDATE, $fields);
  }

  // Let other modules act on updating the entity.
  module_invoke_all('field_attach_update', $entity_type, $entity);

  $entity_info = entity_get_info($entity_type);
  if ($entity_info['field cache']) {
    cache_clear_all("field:$entity_type:$id", 'cache_field');
  }
}

/**
 * Delete field data for an existing entity. This deletes all
 * revisions of field data for the entity.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity whose field data to delete.
 */
function field_attach_delete($entity_type, $entity) {
  _field_invoke('delete', $entity_type, $entity);

  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Collect the storage backends used by the fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $storages[$field['storage']['type']][$field_id] = $field_id;
  }

  // Field storage backends delete their data.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_delete', $entity_type, $entity, $fields);
  }

  // Let other modules act on deleting the entity.
  module_invoke_all('field_attach_delete', $entity_type, $entity);

  $entity_info = entity_get_info($entity_type);
  if ($entity_info['field cache']) {
    cache_clear_all("field:$entity_type:$id", 'cache_field');
  }
}

/**
 * Delete field data for a single revision of an existing entity. The
 * passed entity must have a revision id attribute.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to save.
 */
function field_attach_delete_revision($entity_type, $entity) {
  _field_invoke('delete_revision', $entity_type, $entity);

  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Collect the storage backends used by the fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $storages[$field['storage']['type']][$field_id] = $field_id;
  }

  // Field storage backends delete their data.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_delete_revision', $entity_type, $entity, $fields);
  }

  // Let other modules act on deleting the revision.
  module_invoke_all('field_attach_delete_revision', $entity_type, $entity);
}

/**
 * Prepare field data prior to display.
 *
 * This function lets field types and formatters load additional data
 * needed for display that is not automatically loaded during
 * field_attach_load(). It accepts an array of entities to allow query
 * optimisation when displaying lists of entities.
 *
 * field_attach_prepare_view() and field_attach_view() are two halves
 * of the same operation. It is safe to call
 * field_attach_prepare_view() multiple times on the same entity
 * before calling field_attach_view() on it, but calling any Field
 * API operation on an entity between passing that entity to these two
 * functions may yield incorrect results.
 *
 * @param $entity_type
 *   The type of $entities; e.g. 'node' or 'user'.
 * @param $entities
 *   An array of entities, keyed by entity id.
 * @param $view_mode
 *   View mode, e.g. 'full', 'teaser'...
 */
function field_attach_prepare_view($entity_type, $entities, $view_mode) {
  // To ensure hooks are only run once per entity, only process items without
  // the _field_view_prepared flag.
  // @todo: resolve this more generally for both entity and field level hooks.
  $prepare = array();
  foreach ($entities as $id => $entity) {
    if (empty($entity->_field_view_prepared)) {
      // Add this entity to the items to be prepared.
      $prepare[$id] = $entity;

      // Mark this item as prepared.
      $entity->_field_view_prepared = TRUE;
    }
  }

  // First let the field types do their preparation.
  _field_invoke_multiple('prepare_view', $entity_type, $prepare);
  // Then let the formatters do their own specific massaging.
  // field_default_prepare_view() takes care of dispatching to the correct
  // formatters according to the display settings for the view mode.
  _field_invoke_multiple_default('prepare_view', $entity_type, $prepare, $view_mode);
}

/**
 * Returns a renderable array for the fields on an entity.
 *
 * Each field is displayed according to the display options specified in the
 * $instance definition for the given $view_mode.
 *
 * field_attach_prepare_view() and field_attach_view() are two halves
 * of the same operation. It is safe to call
 * field_attach_prepare_view() multiple times on the same entity
 * before calling field_attach_view() on it, but calling any Field
 * API operation on an entity between passing that entity to these two
 * functions may yield incorrect results.
 *
 * Sample structure:
 * @code
 * array(
 *   'field_foo' => array(
 *     '#theme' => 'field',
 *     '#title' => the label of the field instance,
 *     '#label_display' => the label display mode,
 *     '#object' => the fieldable entity being displayed,
 *     '#entity_type' => the type of the entity being displayed,
 *     '#language' => the language of the field values being displayed,
 *     '#view_mode' => the view mode,
 *     '#field_name' => the name of the field,
 *     '#field_type' => the type of the field,
 *     '#formatter' => the name of the formatter,
 *     '#items' => the field values being displayed,
 *     // The element's children are the formatted values returned by
 *     // hook_field_formatter_view().
 *   ),
 * );
 * @endcode
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to render.
 * @param $view_mode
 *   View mode, e.g. 'full', 'teaser'...
 * @param $langcode
 *   The language the field values are to be shown in. If no language is
 *   provided the current language is used.
 * @return
 *   A renderable array for the field values.
 */
function field_attach_view($entity_type, $entity, $view_mode, $langcode = NULL) {
  // Determine the actual language to display for each field, given the
  // languages available in the field data.
  $display_language = field_language($entity_type, $entity, NULL, $langcode);
  $options = array('language' => $display_language);

  // Invoke field_default_view().
  $null = NULL;
  $output = _field_invoke_default('view', $entity_type, $entity, $view_mode, $null, $options);

  // Add custom weight handling.
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  $output['#pre_render'][] = '_field_extra_fields_pre_render';
  $output['#entity_type'] = $entity_type;
  $output['#bundle'] = $bundle;

  // Let other modules alter the renderable array.
  $context = array(
    'entity_type' => $entity_type,
    'entity' => $entity,
    'view_mode' => $view_mode,
    'display' => $view_mode,
    'language' => $langcode,
  );
  drupal_alter('field_attach_view', $output, $context);

  // Reset the _field_view_prepared flag set in field_attach_prepare_view(),
  // in case the same entity is displayed with different settings later in
  // the request.
  unset($entity->_field_view_prepared);

  return $output;
}

/**
 * Populate the template variables with the field values available for rendering.
 *
 * The $variables array will be populated with all the field instance values
 * associated with the given entity type, keyed by field name; in case of
 * translatable fields the language currently chosen for display will be
 * selected.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity with fields to render.
 * @param $element
 *   The structured array containing the values ready for rendering.
 * @param $variables
 *   The variables array is passed by reference and will be populated with field
 *   values.
 */
function field_attach_preprocess($entity_type, $entity, $element, &$variables) {
  list(, , $bundle) = entity_extract_ids($entity_type, $entity);

  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field_name = $instance['field_name'];
    if (isset($element[$field_name]['#language'])) {
      $langcode = $element[$field_name]['#language'];
      $variables[$field_name] = isset($entity->{$field_name}[$langcode]) ? $entity->{$field_name}[$langcode] : NULL;
    }
  }

  // Let other modules make changes to the $variables array.
  $context = array(
    'entity_type' => $entity_type,
    'entity' => $entity,
    'element' => $element,
  );
  drupal_alter('field_attach_preprocess', $variables, $context);
}

/**
 * Prepares an entity for translation.
 *
 * This function is used to fill-in the form default values for Field API fields
 * while performing entity translation. By default it copies all the source
 * values in the given source language to the new entity and assigns them the
 * target language.
 *
 * This is used as part of the 'per entity' translation pattern, which is
 * implemented only for nodes by translation.module. Other entity types may be
 * supported through contributed modules.
 *
 * @param $entity_type
 *   The type of $entity; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity to be prepared for translation.
 * @param $langcode
 *   The language the entity has to be translated in.
 * @param $source_entity
 *   The source entity holding the field values to be translated.
 * @param $source_langcode
 *   The source language from which translate.
 */
function field_attach_prepare_translation($entity_type, $entity, $langcode, $source_entity, $source_langcode) {
  $options = array('language' => $langcode);
  // Copy source field values into the entity to be prepared.
  _field_invoke_default('prepare_translation', $entity_type, $entity, $source_entity, $source_langcode, $options);
  // Let field types handle their own advanced translation pattern if needed.
  _field_invoke('prepare_translation', $entity_type, $entity, $source_entity, $source_langcode, $options);
  // Let other modules alter the entity translation.
  $context = array(
    'entity_type' => $entity_type,
    'langcode' => $langcode,
    'source_entity' => $source_entity,
    'source_langcode' => $source_langcode,
  );
  drupal_alter('field_attach_prepare_translation', $entity, $context);
}

/**
 * Notify field.module that a new bundle was created.
 *
 * The default SQL-based storage doesn't need to do anything about it, but
 * others might.
 *
 * @param $entity_type
 *   The entity type to which the bundle is bound.
 * @param $bundle
 *   The name of the newly created bundle.
 */
function field_attach_create_bundle($entity_type, $bundle) {
  // Clear the cache.
  field_cache_clear();

  // Let other modules act on creating the bundle.
  module_invoke_all('field_attach_create_bundle', $entity_type, $bundle);
}

/**
 * Notify field.module that a bundle was renamed.
 *
 * @param $entity_type
 *   The entity type to which the bundle is bound.
 * @param $bundle_old
 *   The previous name of the bundle.
 * @param $bundle_new
 *   The new name of the bundle.
 */
function field_attach_rename_bundle($entity_type, $bundle_old, $bundle_new) {
  db_update('field_config_instance')
    ->fields(array('bundle' => $bundle_new))
    ->condition('entity_type', $entity_type)
    ->condition('bundle', $bundle_old)
    ->execute();

  // Clear the cache.
  field_cache_clear();

  // Update bundle settings.
  $settings = variable_get('field_bundle_settings', array());
  if (isset($settings[$entity_type][$bundle_old])) {
    $settings[$entity_type][$bundle_new] = $settings[$entity_type][$bundle_old];
    unset($settings[$entity_type][$bundle_old]);
    variable_set('field_bundle_settings', $settings);
  }

  // Let other modules act on renaming the bundle.
  module_invoke_all('field_attach_rename_bundle', $entity_type, $bundle_old, $bundle_new);
}

/**
 * Notify field.module the a bundle was deleted.
 *
 * This deletes the data for the field instances as well as the field instances
 * themselves. This function actually just marks the data and field instances
 * and deleted, leaving the garbage collection for a separate process, because
 * it is not always possible to delete this much data in a single page request
 * (particularly since for some field types, the deletion is more than just a
 * simple DELETE query).
 *
 * @param $entity_type
 *   The entity type to which the bundle is bound.
 * @param $bundle
 *   The bundle to delete.
 */
function field_attach_delete_bundle($entity_type, $bundle) {
  // First, delete the instances themseves.
  $instances = field_info_instances($entity_type, $bundle);
  foreach ($instances as $instance) {
    field_delete_instance($instance);
  }

  // Clear the cache.
  field_cache_clear();

  // Clear bundle display settings.
  $settings = variable_get('field_bundle_settings', array());
  if (isset($settings[$entity_type][$bundle])) {
    unset($settings[$entity_type][$bundle]);
    variable_set('field_bundle_settings', $settings);
  }

  // Let other modules act on deleting the bundle.
  module_invoke_all('field_attach_delete_bundle', $entity_type, $bundle, $instances);
}


/**
 * @} End of "defgroup field_attach"
 */
