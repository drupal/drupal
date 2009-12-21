<?php
// $Id$

/**
 * @file
 * Field attach API, allowing objects (nodes, users, ...) to be 'fieldable'.
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
 * Exception thrown by field_attach_query() on unsupported query syntax.
 *
 * Some storage modules might not support the full range of the syntax for
 * conditions, and will raise a FieldQueryException when an usupported
 * condition was specified.
 */
class FieldQueryException extends FieldException {}

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
 * existing object.
 */
define('FIELD_STORAGE_UPDATE', 'update');

/**
 * Argument for an insert operation.
 *
 * This is used in hook_field_storage_write when inserting a new object.
 */
define('FIELD_STORAGE_INSERT', 'insert');

/**
 * @} End of "defgroup field_storage"
 */

/**
 * @defgroup field_attach Field Attach API
 * @{
 * Operate on Field API data attached to Drupal objects.
 *
 * Field Attach API functions load, store, generate Form API
 * structures, display, and perform a vareity of other functions for
 * field data connected to individual objects.
 *
 * Field Attach API functions generally take $obj_type and $object
 * arguments along with additional function-specific arguments.
 * $obj_type is the type of the fieldable entity, such as 'node' or
 * 'user', and $object is the object itself. An individual object's
 * bundle, if any, is read from the object's bundle key property
 * identified by hook_fieldable_info() for $obj_type.
 *
 * Fieldable types call Field Attach API functions during their own
 * API calls; for example, node_load() calls field_attach_load(). A
 * fieldable type is not required to use all of the Field Attach
 * API functions.
 *
 * Most Field Attach API functions define a corresponding hook
 * function that allows any module to act on Field Attach operations
 * for any object after the operation is complete, and access or
 * modify all the field, form, or display data for that object and
 * operation. For example, field_attach_view() invokes
 * hook_field_attach_view_alter(). These all-module hooks are distinct from
 * those of the Field Types API, such as hook_field_load(), that are
 * only invoked for the module that defines a specific field type.
 *
 * field_attach_load(), field_attach_insert(), and
 * field_attach_update() also define pre-operation hooks,
 * e.g. hook_field_attach_pre_load(). These hooks run before the
 * corresponding Field Storage API and Field Type API operations.
 * They allow modules to define additional storage locations
 * (e.g. denormalizing, mirroring) for field data on a per-field
 * basis. They also allow modules to take over field storage
 * completely by instructing other implementations of the same hook
 * and the Field Storage API itself not to operate on specified
 * fields.
 *
 * The pre-operation hooks do not make the Field Storage API
 * irrelevant. The Field Storage API is essentially the "fallback
 * mechanism" for any fields that aren't being intercepted explicitly
 * by pre-operation hooks.
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
 *   - sanitize
 *   - view
 *   - prepare translation
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The fully formed $obj_type object.
 * @param $a
 *   - The $form in the 'form' operation.
 *   - The value of $build_mode in the 'view' operation.
 *   - Otherwise NULL.
 * @param $b
 *   - The $form_state in the 'submit' operation.
 *   - Otherwise NULL.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *  - 'field_name': The name of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the object's bundle. NOTE: This option is not compatible with
 *    the 'deleted' option; the 'field_id' option should be used
 *    instead.
 *  - 'field_id': The id of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the objects' bundles.
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
 */
function _field_invoke($op, $obj_type, $object, &$a = NULL, &$b = NULL, $options = array()) {
  // Merge default options.
  $default_options = array(
    'default' => FALSE,
    'deleted' => FALSE,
    'language' => NULL,
  );
  $options += $default_options;

  // Iterate through the object's field instances.
  $return = array();
  list(, , $bundle) = entity_extract_ids($obj_type, $object);

  if ($options['deleted']) {
    $instances = field_read_instances(array('object_type' => $obj_type, 'bundle' => $bundle), array('include_deleted' => $options['deleted']));
  }
  else {
    $instances = field_info_instances($obj_type, $bundle);
  }

  foreach ($instances as $instance) {
    $field_name = $instance['field_name'];

    // When in 'single field' mode, only act on the specified field.
    if ((!isset($options['field_id']) || $options['field_id'] == $instance['field_id']) && (!isset($options['field_name']) || $options['field_name'] == $field_name)) {
      $field = field_info_field($field_name);
      $field_translations = array();
      $suggested_languages = empty($options['language']) ? NULL : array($options['language']);

      // Initialize field translations according to the available languages.
      foreach (field_multilingual_available_languages($obj_type, $field, $suggested_languages) as $langcode) {
        $field_translations[$langcode] = isset($object->{$field_name}[$langcode]) ? $object->{$field_name}[$langcode] : array();
      }

      // Invoke the field hook and collect results.
      $function = $options['default'] ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
      if (function_exists($function)) {
        // Iterate over all the field translations.
        foreach ($field_translations as $langcode => $items) {
          $result = $function($obj_type, $object, $field, $instance, $langcode, $items, $a, $b);
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
          if ($items !== array() || isset($object->{$field_name}[$langcode])) {
            $object->{$field_name}[$langcode] = $items;
          }
        }
      }
    }
  }

  return $return;
}

/**
 * Invoke a field hook across fields on multiple objects.
 *
 * @param $op
 *   Possible operations include:
 *   - load
 *   For all other operations, use _field_invoke() / field_invoke_default()
 *   instead.
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $objects
 *   An array of objects, keyed by object id.
 * @param $a
 *   - The $age parameter in the 'load' operation.
 *   - Otherwise NULL.
 * @param $b
 *   Currently always NULL.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *  - 'field_name': The name of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the object's bundle. NOTE: This option is not compatible with
 *    the 'deleted' option; the 'field_id' option should be used instead.
 *  - 'field_id': The id of the field whose operation should be
 *    invoked. By default, the operation is invoked on all the fields
 *    in the objects' bundles.
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
 * @return
 *   An array of returned values keyed by object id.
 */
function _field_invoke_multiple($op, $obj_type, $objects, &$a = NULL, &$b = NULL, $options = array()) {
  // Merge default options.
  $default_options = array(
    'default' => FALSE,
    'deleted' => FALSE,
    'language' => NULL,
  );
  $options += $default_options;

  $fields = array();
  $grouped_instances = array();
  $grouped_objects = array();
  $grouped_items = array();
  $return = array();

  // Go through the objects and collect the fields on which the hook should be
  // invoked.
  //
  // We group fields by id, not by name, because this function can operate on
  // deleted fields which may have non-unique names. However, objects can only
  // contain data for a single field for each name, even if that field
  // is deleted, so we reference field data via the
  // $object->$field_name property.
  foreach ($objects as $object) {
    list($id, $vid, $bundle) = entity_extract_ids($obj_type, $object);

    if ($options['deleted']) {
      $instances = field_read_field(array('bundle' => $bundle, array('include_deleted' => $options['deleted'])));
    }
    else {
      $instances = field_info_instances($obj_type, $bundle);
    }

    foreach ($instances as $instance) {
      $field_id = $instance['field_id'];
      $field_name = $instance['field_name'];
      // When in 'single field' mode, only act on the specified field.
      if ((empty($options['field_id']) || $options['field_id'] == $field_id) && (empty($options['field_name']) || $options['field_name'] == $field_name)) {
        // Add the field to the list of fields to invoke the hook on.
        if (!isset($fields[$field_id])) {
          $fields[$field_id] = field_info_field_by_id($field_id);
        }
        // Group the corresponding instances and objects.
        $grouped_instances[$field_id][$id] = $instance;
        $grouped_objects[$field_id][$id] = $objects[$id];
        // Extract the field values into a separate variable, easily accessed
        // by hook implementations.
        $suggested_languages = empty($options['language']) ? NULL : array($options['language']);
        foreach (field_multilingual_available_languages($obj_type, $fields[$field_id], $suggested_languages) as $langcode) {
          $grouped_items[$field_id][$langcode][$id] = isset($object->{$field_name}[$langcode]) ? $object->{$field_name}[$langcode] : array();
        }
      }
    }
    // Initialize the return value for each object.
    $return[$id] = array();
  }

  // For each field, invoke the field hook and collect results.
  foreach ($fields as $field_id => $field) {
    $field_name = $field['field_name'];
    $function = $options['default'] ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
    if (function_exists($function)) {
      // Iterate over all the field translations.
      foreach ($grouped_items[$field_id] as $langcode => $items) {
        $results = $function($obj_type, $grouped_objects[$field_id], $field, $grouped_instances[$field_id], $langcode, $grouped_items[$field_id][$langcode], $a, $b);
        if (isset($results)) {
          // Collect results by object.
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
    }

    // Populate field values back in the objects, but avoid replacing missing
    // fields with an empty array (those are not equivalent on update).
    foreach ($grouped_objects[$field_id] as $id => $object) {
      foreach ($grouped_items[$field_id] as $langcode => $items) {
        if ($grouped_items[$field_id][$langcode][$id] !== array() || isset($object->{$field_name}[$langcode])) {
          $object->{$field_name}[$langcode] = $grouped_items[$field_id][$langcode][$id];
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
 * @see _field_invoke().
 */
function _field_invoke_default($op, $obj_type, $object, &$a = NULL, &$b = NULL, $options = array()) {
  $options['default'] = TRUE;
  return _field_invoke($op, $obj_type, $object, $a, $b, $options);
}

/**
 * Invoke field.module's version of a field hook on multiple objects.
 *
 * This function invokes the field_default_[op]() function.
 * Use _field_invoke_multiple() to invoke the field type implementation,
 * hook_field_[op]().
 *
 * @see _field_invoke_multiple().
 */
function _field_invoke_multiple_default($op, $obj_type, $objects, &$a = NULL, &$b = NULL, $options = array()) {
  $options['default'] = TRUE;
  return _field_invoke_multiple($op, $obj_type, $objects, $a, $b, $options);
}

/**
 * Add form elements for all fields for an object to a form structure.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object for which to load form elements, used to initialize
 *   default form values.
 * @param $form
 *   The form structure to fill in.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $langcode
 *   The language the field values are going to be entered, if no language
 *   is provided the default site language will be used.
 * @return
 *   The form elements are added by reference at the top level of the $form
 *   parameter. Sample structure:
 *   @code
 *   array(
 *     '#fields' => array(
 *       // One sub-array per field appearing in the form, keyed by field name.
 *       'field_foo' => array (
 *         'field' => the field definition structure,
 *         'instance' => the field instance definition structure,
 *         'form_path' => an array of keys indicating the path to the field
 *           element within the full $form structure, used by the 'add more
 *           values' AHAH button. Any 3rd party module using form_alter() to
 *           modify the structure of the form should update this entry as well.
 *       ),
 *     ),
 *
 *     // One sub-array per field appearing in the form, keyed by field name.
 *     // The structure of the array differs slightly depending on whether the
 *     // widget is 'single-value' (provides the input for one field value,
 *     // most common case), and will therefore be repeated as many times as
 *     // needed, or 'multiple-values' (one single widget allows the input of
 *     // several values, e.g checkboxes, select box...).
 *     // The sub-array is nested into a $langcode key where $langcode has the
 *     // same value of the $langcode parameter above. This allow us to match
 *     // the field data structure ($field_name[$langcode][$delta][$column]).
 *     // The '#language' key holds the same value of $langcode and it is used
 *     // to access the field sub-array when $langcode is unknown.
 *     'field_foo' => array(
 *       '#tree' => TRUE,
 *       '#language' => $langcode,
 *       $langcode => array(
 *         '#field_name' => the name of the field,
 *         '#tree' => TRUE,
 *         '#required' => whether or not the field is required,
 *         '#title' => the label of the field instance,
 *         '#description' => the description text for the field instance,
 *
 *         // Only for 'single' widgets:
 *         '#theme' => 'field_multiple_value_form',
 *         '#cardinality' => the field cardinality,
 *         // One sub-array per copy of the widget, keyed by delta.
 *         0 => array(
 *           '#title' => the title to be displayed by the widget,
 *           '#default_value' => the field value for delta 0,
 *           '#required' => whether the widget should be marked required,
 *           '#delta' => 0,
 *           '#field_name' => the name of the field,
 *           '#bundle' => the name of the bundle,
 *           '#columns' => the array of field columns,
 *           // The remaining elements in the sub-array depend on the widget.
 *           '#type' => the type of the widget,
 *           ...
 *         ),
 *         1 => array(
 *           ...
 *         ),
 *
 *         // Only for multiple widgets:
 *         '#bundle' => $instance['bundle'],
 *         '#columns'  => array_keys($field['columns']),
 *         // The remaining elements in the sub-array depend on the widget.
 *         '#type' => the type of the widget,
 *         ...
 *       ),
 *       ...
 *     ),
 *   )
 *   @endcode
 */
function field_attach_form($obj_type, $object, &$form, &$form_state, $langcode = NULL) {
  // If no language is provided use the default site language.
  $options = array('language' => field_multilingual_valid_language($langcode));
  $form += (array) _field_invoke_default('form', $obj_type, $object, $form, $form_state, $options);

  // Add custom weight handling.
  list($id, $vid, $bundle) = entity_extract_ids($obj_type, $object);
  $form['#attached']['css'][] = drupal_get_path('module', 'field') . '/theme/field.css';
  $form['#pre_render'][] = '_field_extra_weights_pre_render';
  $form['#extra_fields'] = field_extra_fields($bundle);

  // Let other modules make changes to the form.
  // Avoid module_invoke_all() to let parameters be taken by reference.
  foreach (module_implements('field_attach_form') as $module) {
    $function = $module . '_field_attach_form';
    $function($obj_type, $object, $form, $form_state, $langcode);
  }
}

/**
 * Load all fields for the most current version of each of a set of
 * objects of a single object type.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $objects
 *   An array of objects for which to load fields, keyed by object id.
 *   Each object needs to have its 'bundle', 'id' and (if applicable)
 *   'revision' keys filled.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all
 *   fields, or FIELD_LOAD_REVISION to load the version indicated by
 *   each object. Defaults to FIELD_LOAD_CURRENT; use
 *   field_attach_load_revision() instead of passing FIELD_LOAD_REVISION.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *  - 'field_id': The field id that should be loaded, instead of
 *    loading all fields, for each object. Note that returned objects
 *    may contain data for other fields, for example if they are read
 *    from a cache.
 *  - 'deleted': If TRUE, the function will operate on deleted fields
 *    as well as non-deleted fields. If unset or FALSE, only
 *    non-deleted fields are operated on.
 * @return
 *   Loaded field values are added to $objects.
 */
function field_attach_load($obj_type, $objects, $age = FIELD_LOAD_CURRENT, $options = array()) {
  $load_current = $age == FIELD_LOAD_CURRENT;

  // Merge default options.
  $default_options = array(
    'deleted' => FALSE,
  );
  $options += $default_options;

  $info = entity_get_info($obj_type);
  // Only the most current revision of non-deleted fields for
  // cacheable fieldable types can be cached.
  $cache_read = $load_current && $info['cacheable'] && empty($options['deleted']);
  // In addition, do not write to the cache when loading a single field.
  $cache_write = $cache_read && !isset($options['field_id']);

  if (empty($objects)) {
    return;
  }

  // Assume all objects will need to be queried. Objects found in the cache
  // will be removed from the list.
  $queried_objects = $objects;

  // Fetch available objects from cache, if applicable.
  if ($cache_read) {
    // Build the list of cache entries to retrieve.
    $cids = array();
    foreach ($objects as $id => $object) {
      $cids[] = "field:$obj_type:$id";
    }
    $cache = cache_get_multiple($cids, 'cache_field');
    // Put the cached field values back into the objects and remove them from
    // the list of objects to query.
    foreach ($objects as $id => $object) {
      $cid = "field:$obj_type:$id";
      if (isset($cache[$cid])) {
        unset($queried_objects[$id]);
        foreach ($cache[$cid]->data as $field_name => $values) {
          $object->$field_name = $values;
        }
      }
    }
  }

  // Fetch other objects from their storage location.
  if ($queried_objects) {
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
      $function($obj_type, $queried_objects, $age, $skip_fields, $options);
    }

    // Collect the storage backends used by the remaining fields in the objects.
    $storages = array();
    foreach ($queried_objects as $obj) {
      list($id, $vid, $bundle) = entity_extract_ids($obj_type, $obj);
      if ($options['deleted']) {
        $instances = field_read_instances(array('object_type' => $obj_type, 'bundle' => $bundle), array('include_deleted' => $options['deleted']));
      }
      else {
        $instances = field_info_instances($obj_type, $bundle);
      }

      foreach ($instances as $instance) {
        if (!isset($options['field_id']) || $options['field_id'] == $instance['field_id']) {
          $field_name = $instance['field_name'];
          $field_id = $instance['field_id'];
          // Make sure all fields are present at least as empty arrays.
          if (!isset($queried_objects[$id]->{$field_name})) {
            $queried_objects[$id]->{$field_name} = array();
          }
          // Collect the storage backend if the field has not been loaded yet.
          if (!isset($skip_fields[$field_id])) {
            $field = field_info_field_by_id($field_id);
            $storages[$field['storage']['type']][$field_id][] = $load_current ? $id : $vid;
          }
        }
      }
    }

    // Invoke hook_field_storage_load() on the relevant storage backends.
    foreach ($storages as $storage => $fields) {
      $storage_info = field_info_storage_types($storage);
      module_invoke($storage_info['module'], 'field_storage_load', $obj_type, $queried_objects, $age, $fields, $options);
    }

    // Invoke field-type module's hook_field_load().
    _field_invoke_multiple('load', $obj_type, $queried_objects, $age, $options);

    // Invoke hook_field_attach_load(): let other modules act on loading the
    // object.
    module_invoke_all('field_attach_load', $obj_type, $queried_objects, $age, $options);

    // Build cache data.
    if ($cache_write) {
      foreach ($queried_objects as $id => $object) {
        $data = array();
        list($id, $vid, $bundle) = entity_extract_ids($obj_type, $object);
        $instances = field_info_instances($obj_type, $bundle);
        foreach ($instances as $instance) {
          $data[$instance['field_name']] = $queried_objects[$id]->{$instance['field_name']};
        }
        $cid = "field:$obj_type:$id";
        cache_set($cid, $data, 'cache_field');
      }
    }
  }
}

/**
 * Load all fields for a previous version of each of a set of
 * objects of a single object type.
 *
 * Loading different versions of the same objects is not supported,
 * and should be done by separate calls to the function.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $objects
 *   An array of objects for which to load fields, keyed by object id.
 *   Each object needs to have its 'bundle', 'id' and (if applicable)
 *   'revision' keys filled.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - 'field_name':  The field name that should be loaded, instead of
 *     loading all fields, for each object. Note that returned objects
 *     may contain data for other fields, for example if they are read
 *     from a cache.
 * @returns
 *   On return, the objects in $objects are modified by having the
 *   appropriate set of fields added.
 */
function field_attach_load_revision($obj_type, $objects, $options = array()) {
  return field_attach_load($obj_type, $objects, FIELD_LOAD_REVISION, $options);
}

/**
 * Perform field validation against the field data in an object.
 *
 * This function does not perform field widget validation on form
 * submissions. It is intended to be called during API save
 * operations. Use field_attach_form_validate() to validate form
 * submissions.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to validate.
 * @throws FieldValidationException
 *   If validation errors are found, a FieldValidationException is thrown. The
 *   'errors' property contains the array of errors, keyed by field name,
 *   language and delta.
 */
function field_attach_validate($obj_type, $object) {
  $errors = array();
  // Check generic, field-type-agnostic errors first.
  _field_invoke_default('validate', $obj_type, $object, $errors);
  // Check field-type specific errors.
  _field_invoke('validate', $obj_type, $object, $errors);

  // Let other modules validate the object.
  // Avoid module_invoke_all() to let $errors be taken by reference.
  foreach (module_implements('field_attach_validate') as $module) {
    $function = $module . '_field_attach_validate';
    $function($obj_type, $object, $errors);
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
 * on the correct form elements. Fieldable object types should call
 * this function during their own form validation function.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object being submitted. The 'bundle', 'id' and (if applicable)
 *   'revision' keys should be present. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure.
 * @param $form_state
 *   An associative array containing the current state of the form.
 */
function field_attach_form_validate($obj_type, $object, $form, &$form_state) {
  // Extract field values from submitted values.
  _field_invoke_default('extract_form_values', $obj_type, $object, $form, $form_state);

  // Perform field_level validation.
  try {
    field_attach_validate($obj_type, $object);
  }
  catch (FieldValidationException $e) {
    // Pass field-level validation errors back to widgets for accurate error
    // flagging.
    _field_invoke_default('form_errors', $obj_type, $object, $form, $e->errors);
  }
}

/**
 * Perform necessary operations on field data submitted by a form.
 *
 * Currently, this accounts for drag-and-drop reordering of
 * field values, and filtering of empty values.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object being submitted. The 'bundle', 'id' and (if applicable)
 *   'revision' keys should be present. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure to fill in.
 * @param $form_state
 *   An associative array containing the current state of the form.
 */
function field_attach_submit($obj_type, $object, $form, &$form_state) {
  // Extract field values from submitted values.
  _field_invoke_default('extract_form_values', $obj_type, $object, $form, $form_state);

  _field_invoke_default('submit', $obj_type, $object, $form, $form_state);

  // Let other modules act on submitting the object.
  // Avoid module_invoke_all() to let $form_state be taken by reference.
  foreach (module_implements('field_attach_submit') as $module) {
    $function = $module . '_field_attach_submit';
    $function($obj_type, $object, $form, $form_state);
  }
}

/**
 * Perform necessary operations just before fields data get saved.
 *
 * We take no specific action here, we just give other
 * modules the opportunity to act.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to process.
 */
function field_attach_presave($obj_type, $object) {
  _field_invoke('presave', $obj_type, $object);

  // Let other modules act on presaving the object.
  module_invoke_all('field_attach_presave', $obj_type, $object);
}

/**
 * Save field data for a new object.
 *
 * The passed in object must already contain its id and (if applicable)
 * revision id attributes.
 * Default values (if any) will be saved for fields not present in the
 * $object.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 * @return
 *   Default values (if any) will be added to the $object parameter for fields
 *   it leaves unspecified.
 */
function field_attach_insert($obj_type, $object) {
  _field_invoke_default('insert', $obj_type, $object);
  _field_invoke('insert', $obj_type, $object);

  list($id, $vid, $bundle, $cacheable) = entity_extract_ids($obj_type, $object);

  // Let any module insert field data before the storage engine, accumulating
  // saved fields along the way.
  $skip_fields = array();
  foreach (module_implements('field_storage_pre_insert') as $module) {
    $function = $module . '_field_storage_pre_insert';
    $function($obj_type, $object, $skip_fields);
  }

  // Collect the storage backends used by the remaining fields in the objects.
  $storages = array();
  foreach (field_info_instances($obj_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $field_name = $field['field_name'];
    if (!empty($object->$field_name)) {
      // Collect the storage backend if the field has not been written yet.
      if (!isset($skip_fields[$field_id])) {
        $storages[$field['storage']['type']][$field_id] = $field_id;
      }
    }
  }

  // Field storage backends save any remaining unsaved fields.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_write', $obj_type, $object, FIELD_STORAGE_INSERT, $fields);
  }

  // Let other modules act on inserting the object.
  module_invoke_all('field_attach_insert', $obj_type, $object);

  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id", 'cache_field');
  }
}

/**
 * Save field data for an existing object.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 */
function field_attach_update($obj_type, $object) {
  _field_invoke('update', $obj_type, $object);

  list($id, $vid, $bundle, $cacheable) = entity_extract_ids($obj_type, $object);

  // Let any module update field data before the storage engine, accumulating
  // saved fields along the way.
  $skip_fields = array();
  foreach (module_implements('field_storage_pre_update') as $module) {
    $function = $module . '_field_storage_pre_update';
    $function($obj_type, $object, $skip_fields);
  }

  // Collect the storage backends used by the remaining fields in the objects.
  $storages = array();
  foreach (field_info_instances($obj_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $field_name = $field['field_name'];
    // Leave the field untouched if $object comes with no $field_name property,
    // but empty the field if it comes as a NULL value or an empty array.
    // Function property_exists() is slower, so we catch the more frequent
    // cases where it's an empty array with the faster isset().
    if (isset($object->$field_name) || property_exists($object, $field_name)) {
      // Collect the storage backend if the field has not been written yet.
      if (!isset($skip_fields[$field_id])) {
        $storages[$field['storage']['type']][$field_id] = $field_id;
      }
    }
  }

  // Field storage backends save any remaining unsaved fields.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_write', $obj_type, $object, FIELD_STORAGE_UPDATE, $fields);
  }

  // Let other modules act on updating the object.
  module_invoke_all('field_attach_update', $obj_type, $object);

  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id", 'cache_field');
  }
}

/**
 * Delete field data for an existing object. This deletes all
 * revisions of field data for the object.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object whose field data to delete.
 */
function field_attach_delete($obj_type, $object) {
  _field_invoke('delete', $obj_type, $object);

  list($id, $vid, $bundle, $cacheable) = entity_extract_ids($obj_type, $object);

  // Collect the storage backends used by the fields in the objects.
  $storages = array();
  foreach (field_info_instances($obj_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $storages[$field['storage']['type']][$field_id] = $field_id;
  }

  // Field storage backends delete their data.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_delete', $obj_type, $object, $fields);
  }

  // Let other modules act on deleting the object.
  module_invoke_all('field_attach_delete', $obj_type, $object);

  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id", 'cache_field');
  }
}

/**
 * Delete field data for a single revision of an existing object. The
 * passed object must have a revision id attribute.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 */
function field_attach_delete_revision($obj_type, $object) {
  _field_invoke('delete_revision', $obj_type, $object);

  list($id, $vid, $bundle, $cacheable) = entity_extract_ids($obj_type, $object);

  // Collect the storage backends used by the fields in the objects.
  $storages = array();
  foreach (field_info_instances($obj_type, $bundle) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['id'];
    $storages[$field['storage']['type']][$field_id] = $field_id;
  }

  // Field storage backends delete their data.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_delete_revision', $obj_type, $object, $fields);
  }

  // Let other modules act on deleting the revision.
  module_invoke_all('field_attach_delete_revision', $obj_type, $object);
}

/**
 * Retrieve objects matching a given set of conditions.
 *
 * Note that the query 'conditions' only apply to the stored values.
 * In a regular field_attach_load() call, field values additionally go through
 * hook_field_load() and hook_field_attach_load() invocations, which can add
 * to or affect the raw stored values. The results of field_attach_query()
 * might therefore differ from what could be expected by looking at a regular,
 * fully loaded object.
 *
 * @param $field_id
 *   The id of the field to query.
 * @param $conditions
 *   An array of query conditions. Each condition is a numerically indexed
 *   array, in the form: array(column, value, operator).
 *   Not all storage engines are required to support queries on all column, or
 *   with all operators below. A FieldQueryException will be raised if an
 *   unsupported condition is specified.
 *   Supported columns:
 *     - any of the columns for $field_name's field type: condition on field
 *       value,
 *     - 'type': condition on object type (e.g. 'node', 'user'...),
 *     - 'bundle': condition on object bundle (e.g. node type),
 *     - 'entity_id': condition on object id (e.g node nid, user uid...),
 *     - 'deleted': condition on whether the field's data is
 *        marked deleted for the object (defaults to FALSE if not specified)
 *     The field_attach_query_revisions() function additionally supports:
 *     - 'revision_id': condition on object revision id (e.g node vid).
 *   Supported operators:
 *     - '=', '!=', '>', '>=', '<', '<=', 'STARTS_WITH', 'ENDS_WITH',
 *       'CONTAINS': these operators expect the value as a literal of the same
 *       type as the column,
 *     - 'IN', 'NOT IN': this operator expects the value as an array of
 *       literals of the same type as the column.
 *     - 'BETWEEN': this operator expects the value as an array of two literals
 *       of the same type as the column.
 *     The operator can be ommitted, and will default to 'IN' if the value is
 *     an array, or to '=' otherwise.
 *   Example values for $conditions:
 *   @code
 *   array(
 *     array('type', 'node'),
 *   );
 *   array(
 *     array('bundle', array('article', 'page')),
 *     array('value', 12, '>'),
 *   );
 *   @endcode
 * @param $options
 *   An associative array of additional options:
 *   - limit: The number of results that is requested. This is only a
 *   hint to the storage engine(s); callers should be prepared to
 *   handle fewer or more results. Specify FIELD_QUERY_NO_LIMIT to retrieve
 *   all available objects. This option has a default value of 0 so
 *   callers must make an explicit choice to potentially retrieve an
 *   enormous result set.
 *   - cursor: A reference to an opaque cursor that allows a caller to
 *   iterate through multiple result sets. On the first call, pass 0;
 *   the correct value to pass on the next call will be written into
 *   the value on return. When there is no more query data available,
 *   the value will be filled in with FIELD_QUERY_COMPLETE. If cursor
 *   is passed as NULL, the first result set is returned and no next
 *   cursor is returned.
 *   - count: If TRUE, return a single count of all matching objects;
 *   limit and cursor are ignored.
 *   - age: Internal use only. Use field_attach_query_revisions()
 *   instead of passing FIELD_LOAD_REVISION.
 *     - FIELD_LOAD_CURRENT (default): query the most recent revisions for all
 *     objects. The results will be keyed by object type and object id.
 *     - FIELD_LOAD_REVISION: query all revisions. The results will be keyed by
 *     object type and object revision id.
 * @return
 *   An array keyed by object type (e.g. 'node', 'user'...), then by object id
 *   or revision id (depending of the value of the $age parameter).
 *   The values are pseudo-objects with the bundle, id, and revision
 *   id fields filled in.
 *
 *   Throws a FieldQueryException if the field's storage doesn't support the
 *   specified conditions.
 */
function field_attach_query($field_id, $conditions, $options = array()) {
  // Merge in default options.
  $default_options = array(
    'limit' => 0,
    'cursor' => 0,
    'count' => FALSE,
    'age' => FIELD_LOAD_CURRENT,
  );
  $options += $default_options;

  // Give a chance to 3rd party modules that bypass the storage engine to
  // handle the query.
  $skip_field = FALSE;
  foreach (module_implements('field_storage_pre_query') as $module) {
    $function = $module . '_field_storage_pre_query';
    $results = $function($field_id, $conditions, $options, $skip_field);
    // Stop as soon as a module claims it handled the query.
    if ($skip_field) {
      break;
    }
  }
  // If the request hasn't been handled, let the storage engine handle it.
  if (!$skip_field) {
    $field = field_info_field_by_id($field_id);
    $function = $field['storage']['module'] . '_field_storage_query';
    $results = $function($field_id, $conditions, $options);
  }

  return $results;
}

/**
 * Retrieve object revisions matching a given set of conditions.
 *
 * See field_attach_query() for more informations.
 *
 * @param $field_id
 *   The id of the field to query.
 * @param $conditions
 *   See field_attach_query().
 * @param $options
 *   An associative array of additional options. See field_attach_query().
 * @return
 *   See field_attach_query().
 */
function field_attach_query_revisions($field_id, $conditions, $options = array()) {
  $options['age'] = FIELD_LOAD_REVISION;
  return field_attach_query($field_id, $conditions, $options);
}

/**
 * Allow formatters to act on fieldable objects prior to rendering.
 */
function field_attach_prepare_view($obj_type, $objects, $build_mode = 'full') {
  // First let the field types do their preparation.
  _field_invoke_multiple('prepare_view', $obj_type, $objects);
  // Then let the formatters do their own specific massaging.
  // field_default_prepare_view() takes care of dispatching to the correct
  // formatters according to the display settings for the build mode.
  _field_invoke_multiple_default('prepare_view', $obj_type, $objects, $build_mode);
}

/**
 * Returns a renderable array for the fields on an object.
 *
 * Each field is displayed according to the display options specified in the
 * $instance definition for the given $build_mode.
 *
 * Sample structure:
 * @code
 * array(
 *   'field_foo' => array(
 *     '#theme' => 'field',
 *     '#title' => the label of the field instance,
 *     '#label_display' => the label display mode,
 *     '#object' => the fieldable object being displayed,
 *     '#object_type' => the type of the object being displayed,
 *     '#language' => the language of the field values being displayed,
 *     '#build_mode' => the build mode,
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
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to render.
 * @param $build_mode
 *   Build mode, e.g. 'full', 'teaser'...
 * @param $langcode
 *   The language the field values are to be shown in. If no language is
 *   provided the current language is used.
 * @return
 *   A renderable array for the field values.
 */
function field_attach_view($obj_type, $object, $build_mode = 'full', $langcode = NULL) {
  // If no language is provided use the current UI language.
  $options = array('language' => field_multilingual_valid_language($langcode, FALSE));

  // Let field modules sanitize their data for output.
  $null = NULL;
  _field_invoke('sanitize', $obj_type, $object, $null, $null, $options);

  $output = _field_invoke_default('view', $obj_type, $object, $build_mode, $null, $options);

  // Add custom weight handling.
  list($id, $vid, $bundle) = entity_extract_ids($obj_type, $object);
  $output['#attached']['css'][] = drupal_get_path('module', 'field') . '/theme/field.css';
  $output['#pre_render'][] = '_field_extra_weights_pre_render';
  $output['#extra_fields'] = field_extra_fields($bundle);

  // Let other modules alter the renderable array.
  $context = array(
    'obj_type' => $obj_type,
    'object' => $object,
    'build_mode' => $build_mode,
    'langcode' => $langcode,
  );
  drupal_alter('field_attach_view', $output, $context);

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
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to render.
 * @param $element
 *   The structured array containing the values ready for rendering.
 * @param $variables
 *   The variables array is passed by reference and will be populated with field
 *   values.
 */
function field_attach_preprocess($obj_type, $object, $element, &$variables) {
  list(, , $bundle) = entity_extract_ids($obj_type, $object);

  foreach (field_info_instances($obj_type, $bundle) as $instance) {
    $field_name = $instance['field_name'];
    if (isset($element[$field_name]['#language'])) {
      $langcode = $element[$field_name]['#language'];
      $variables[$field_name] = isset($object->{$field_name}[$langcode]) ? $object->{$field_name}[$langcode] : NULL;
    }
  }

  // Let other modules make changes to the $variables array.
  $context = array(
    'obj_type' => $obj_type,
    'object' => $object,
    'element' => $element,
  );
  drupal_alter('field_attach_preprocess', $variables, $context);
}

/**
 * Retrieve the user-defined weight for a 'pseudo-field' component.
 *
 * @param $bundle
 *   The bundle name.
 * @param $pseudo_field
 *   The name of the 'pseudo-field'.
 * @return
 *   The weight for the 'pseudo-field', respecting the user settings stored by
 *   field.module.
 */
function field_attach_extra_weight($bundle, $pseudo_field) {
  $extra = field_extra_fields($bundle);
  if (isset($extra[$pseudo_field])) {
    return $extra[$pseudo_field]['weight'];
  }
}

/**
 * Implements hook_node_prepare_translation().
 *
 * TODO D7: We do not yet know if this really belongs in Field API.
 */
function field_attach_prepare_translation($node) {
  // Prevent against invalid 'nodes' built by broken 3rd party code.
  if (isset($node->type)) {
    $type = content_types($node->type);
    // Save cycles if the type has no fields.
    if (!empty($type['instances'])) {
      $default_additions = _field_invoke_default('prepare_translation', $node);
      $additions = _field_invoke('prepare_translation', $node);
      // Merge module additions after the default ones to enable overriding
      // of field values.
      $node = (object) array_merge((array) $node, $default_additions, $additions);
    }
  }
}

/**
 * Notify field.module that a new bundle was created.
 *
 * The default SQL-based storage doesn't need to do anything about it, but
 * others might.
 *
 * @param $obj_type
 *   The object type to which the bundle is bound.
 * @param $bundle
 *   The name of the newly created bundle.
 */
function field_attach_create_bundle($obj_type, $bundle) {
  // Clear the cache.
  field_cache_clear();

  // Let other modules act on creating the bundle.
  module_invoke_all('field_attach_create_bundle', $obj_type, $bundle);
}

/**
 * Notify field.module that a bundle was renamed.
 *
 * @param $obj_type
 *   The object type to which the bundle is bound.
 * @param $bundle_old
 *   The previous name of the bundle.
 * @param $bundle_new
 *   The new name of the bundle.
 */
function field_attach_rename_bundle($obj_type, $bundle_old, $bundle_new) {
  db_update('field_config_instance')
    ->fields(array('bundle' => $bundle_new))
    ->condition('object_type', $obj_type)
    ->condition('bundle', $bundle_old)
    ->execute();

  // Clear the cache.
  field_cache_clear();

  // Let other modules act on renaming the bundle.
  module_invoke_all('field_attach_rename_bundle', $obj_type, $bundle_old, $bundle_new);
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
 * @param $obj_type
 *   The object type to which the bundle is bound.
 * @param $bundle
 *   The bundle to delete.
 */
function field_attach_delete_bundle($obj_type, $bundle) {
  // First, delete the instances themseves.
  $instances = field_info_instances($obj_type, $bundle);
  foreach ($instances as $instance) {
    field_delete_instance($instance);
  }

  // Clear the cache.
  field_cache_clear();

  // Let other modules act on deleting the bundle.
  module_invoke_all('field_attach_delete_bundle', $obj_type, $bundle, $instances);
}


/**
 * @} End of "defgroup field_attach"
 */
