<?php
// $Id$

// TODO D7 : consistency - do field_attach_functions return a value or alter in place ?

// TOTO D7 : consistency - some of these functions process individual fields
// and others process the combined value of all fields.
// Should all iteration through available fields be done here instead of in Field?

/**
 * @defgroup field_storage Field Storage API
 * @{
 * Implement a storage engine for Field API data.
 *
 * The Field Attach API uses the Field Storage API to perform all
 * "database access."  Each Field Storage API hook function defines a
 * primitive database operation such as read, write, or delete.  The
 * default field storage module, field_sql_storage.module, uses the
 * local SQL database to implement these operations, but alternative
 * field storage engines can choose to represent the data in SQL
 * differently or use a completely different storage mechanism such as
 * a cloud-based database.
 *
 * The Drupal system variable field_storage_module identifies the
 * field storage module to use.
 */
/**
 * @} End of "defgroup field_storage"
 */

/**
 * @autoload field_attach_.* field_attach FieldException {
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
 * 'user', and $object is the object itself.  An individual object's
 * bundle, if any, is read from the object's bundle key property
 * identified by hook_fieldable_info() for $obj_type.
 *
 * Fieldable types call Field Attach API functions during their own
 * API calls; for example, node_load() calls field_attach_load().  A
 * fieldable type may is not required to use all of the Field Attach
 * API functions.
 *
 * Most Field Attach API functions define a corresponding hook
 * function that allows any module to act on Field Attach operations
 * for any object, and access or modify all the field, form, or
 * display data for that object and operation.  These all-module hooks
 * are distinct from those of the Field Types API, such as
 * hook_field_load(), that are only invoked for the module that
 * defines a specific field type.
 */

/**
 * Invoke a field hook.
 *
 * @param $op
 * - Possible operations include:
 *   - load
 *   - form
 *   - validate
 *   - presave
 *   - insert
 *   - update
 *   - delete
 *   - delete revision
 *   - sanitize
 *   - view
 *   - preprocess
 *   - prepare translation
 *
 * @param $obj_type
 * - Can be:
 *   - node
 *   - user
 *   - Others not yet implemented.
 *
 * @param $object
 * - The fully formed $obj_type object.
 *
 * @param $a
 *  - The $form in the 'form' operation.
 *  - The value of $teaser in the 'view' operation.
 *  - Otherwise NULL.
 *
 * @param $b
 *  - The $form_state in the 'submit' operation.
 *  - Otherwise NULL.
 *
 * @param $default
 *  - TRUE: render the default field implementation of the field hook.
 *  - FALSE: render the field module's implementation of the field hook.
 */
function _field_invoke($op, $obj_type, &$object, &$a = NULL, &$b = NULL, $default = FALSE) {
  list(, , $bundle) = field_attach_extract_ids($obj_type, $object);
  $instances = field_info_instances($bundle);

  $return = array();
  foreach ($instances as $instance) {
    $field_name = $instance['field_name'];
    $field = field_info_field($field_name);
    $items = isset($object->$field_name) ? $object->$field_name : array();

    // Make sure AHAH 'add more' button isn't sent to the fields for processing.
    // TODO D7 : needed ?
    unset($items[$field_name . '_add_more']);

    $function = $default ? 'field_default_' . $op : $field['module'] . '_field_' . $op;
    if (drupal_function_exists($function)) {
      $result = $function($obj_type, $object, $field, $instance, $items, $a, $b);
      if (is_array($result)) {
        $return = array_merge($return, $result);
      }
      else if (isset($result)) {
        $return[] = $result;
      }
    }
    // Put back the altered items in the object, if the field was present to
    // begin with (avoid replacing missing field with empty array(), those are
    // not semantically equivalent on update).
    if (isset($object->$field_name)) {
      $object->$field_name = $items;
    }
  }

  return $return;
}

/**
 * Invoke field.module's version of a field hook.
 */
function _field_invoke_default($op, $obj_type, &$object, &$a = NULL, &$b = NULL) {
  return _field_invoke($op, $obj_type, $object, $a, $b, TRUE);
}

/**
 * @} End of "defgroup field_attach"
 *
 * The rest of the functions in this file are not in a group, but
 * their automatically-generated autoloaders are (see field.autoload.inc).
 */

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
 *
 * TODO : document the resulting $form structure, like we do for
 * field_attach_view().
 */
function _field_attach_form($obj_type, $object, &$form, $form_state) {
  // TODO : something's not right here : do we alter the form or return a value ?
  $form += (array) _field_invoke_default('form', $obj_type, $object, $form, $form_state);

  // Let other modules make changes to the form.
  foreach (module_implements('field_attach_form') as $module) {
    $function = $module . '_field_attach_form';
    $function($obj_type, $object, $form, $form_state);
  }
}

/**
 * Load all fields for the most current version of each of a set of
 * objects of a single object type.
 *
 * @param $obj_type
 *   The type of objects for which to load fields; e.g. 'node' or
 *   'user'.
 * @param $objects
 *   An array of objects for which to load fields.  The keys for
 *   primary id and bundle name to load are identified by
 *   hook_fieldable_info for $obj_type.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all
 *   fields, or FIELD_LOAD_REVISION to load the version indicated by
 *   each object.  Defaults to FIELD_LOAD_CURRENT; use
 *   field_attach_load_revision() instead of passing FIELD_LOAD_REVISION.
 * @returns
 *   On return, the objects in $objects are modified by having the
 *   appropriate set of fields added.
 */
function _field_attach_load($obj_type, $objects, $age = FIELD_LOAD_CURRENT) {
  $queried_objects = array();

  // Fetch avaliable nodes from cache.
  foreach ($objects as $object) {
    list($id, $vid, $bundle, $cacheable) = field_attach_extract_ids($obj_type, $object);
    $cid = "field:$obj_type:$id:$vid";
    if ($cacheable && $cached = cache_get($cid, 'cache_field')) {
      foreach ($cached->data as $key => $value) {
        $object->$key = $value;
      }
    }
    else {
      $queried_objects[$id] = $objects[$id];
    }
  }
  // Fetch other nodes from the database.
  if ($queried_objects) {
    // We need the raw additions to be able to cache them, so
    // content_storage_load() and hook_field_load() must not alter
    // nodes directly but return their additions.
    $additions = module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_load', $obj_type, $queried_objects, $age);
    foreach ($additions as $id => $obj_additions) {
      foreach ($obj_additions as $key => $value) {
        $queried_objects[$id]->$key = $value;
      }
    }

    // TODO D7 : to be consistent we might want to make hook_field_load() accept
    // multiple objects too. Which forbids going through _field_invoke(), but
    // requires manually iterating the instances instead.
    foreach ($queried_objects as $id => $object) {
      list($id, $vid, $bundle, $cacheable) = field_attach_extract_ids($obj_type, $object);

      // Make sure empty fields are present as empty arrays.
      $instances = field_info_instances($bundle);
      foreach ($instances as $instance) {
        if (!isset($object->{$instance['field_name']})) {
          $queried_objects[$id]->{$instance['field_name']} = array();
          $additions[$id][$instance['field_name']] = array();
        }
      }

      $custom_additions = _field_invoke('load', $obj_type, $object);
      foreach ($custom_additions as $key => $value) {
        $queried_objects[$id]->$key = $value;
        $additions[$id][$key] = $value;
      }

      // Let other modules act on loading the object.
      // TODO : this currently doesn't get cached (we cache $additions).
      // This should either be called after we fetch from cache, or return an
      // array of additions.
      foreach (module_implements('field_attach_load') as $module) {
        $function = $module . '_field_attach_load';
        $function($obj_type, $queried_objects[$id]);
      }

      // Cache the data.
      if ($cacheable) {
        $cid = "field:$obj_type:$id:$vid";
        $data = isset($additions[$id]) ? $additions[$id] : array();
        cache_set($cid, $data, 'cache_field');
      }
    }
  }
}

/**
 * Load all fields for a previous version of each of a set of
 * objects of a single object type.
 *
 * @param $obj_type
 *   The type of objects for which to load fields; e.g. 'node' or
 *   'user'.
 * @param $objects
 *   An array of objects for which to load fields.  The keys for
 *   primary id, revision id, and bundle name to load are identified by
 *   hook_fieldable_info for $obj_type.
 * @returns
 *   On return, the objects in $objects are modified by having the
 *   appropriate set of fields added.
 */
function _field_attach_load_revision($obj_type, $objects) {
  return field_attach_load($obj_type, $objects, FIELD_LOAD_REVISION);
}

/**
 * Perform field validation against the field data in an object.
 * Field validation is distinct from widget validation; the latter
 * occurs during the Form API validation phase.
 *
 * NOTE: This functionality does not yet exist in its final state.
 * Eventually, field validation will occur during field_attach_insert
 * or _update which will throw an exception on failure.  For now,
 * fieldable entities must call this during their Form API validation
 * phase, and field validation will call form_set_error for any
 * errors.  See http://groups.drupal.org/node/18019.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to validate.
 */
function _field_attach_validate($obj_type, &$object, $form = NULL) {
  _field_invoke('validate', $obj_type, $object, $form);
  _field_invoke_default('validate', $obj_type, $object, $form);

  // Let other modules validate the object.
  foreach (module_implements('field_attach_validate') as $module) {
    $function = $module . '_field_attach_validate';
    $function($obj_type, $object, $form);
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
 *   The object being submitted. The 'bundle key', 'id key' and (if applicable)
 *   'revision key' should be present. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure to fill in.
 * @param $form_state
 *   An associative array containing the current state of the form.
 */
function _field_attach_submit($obj_type, &$object, $form, &$form_state) {
  _field_invoke_default('submit', $obj_type, $object, $form, $form_state);

  // Let other modules act on submitting the object.
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
function _field_attach_presave($obj_type, &$object) {
  // TODO : to my knowledge, no field module has any use for 'presave' on D6.
  // should we keep this ?
  _field_invoke('presave', $obj_type, $object);

  // Let other modules act on presaving the object.
  foreach (module_implements('field_attach_presave') as $module) {
    $function = $module . '_field_attach_presave';
    $function($obj_type, $object);
  }
}

/**
 * Save field data for a new object.  The passed in object must
 * already contain its id and (if applicable) revision id attributes.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 */
function _field_attach_insert($obj_type, &$object) {

  // Let other modules act on inserting the object.
  foreach (module_implements('field_attach_insert') as $module) {
    $function = $module . '_field_attach_insert';
    $function($obj_type, $object);
  }

  _field_invoke('insert', $obj_type, $object);
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_write', $obj_type, $object);

  list($id, $vid, $bundle, $cacheable) = field_attach_extract_ids($obj_type, $object);
  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id:", 'cache_field', TRUE);
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
function _field_attach_update($obj_type, &$object) {

  // Let other modules act on updating the object.
  foreach (module_implements('field_attach_update') as $module) {
    $function = $module . '_field_attach_update';
    $function($output, $obj_type, $object);
  }

  _field_invoke('update', $obj_type, $object);
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_write', $obj_type, $object, TRUE);

  list($id, $vid, $bundle, $cacheable) = field_attach_extract_ids($obj_type, $object);
  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id:$vid", 'cache_field');
  }
}

/**
 * Delete field data for an existing object.  This deletes all
 * revisions of field data for the object.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object whose field data to delete.
 */
function _field_attach_delete($obj_type, &$object) {
  _field_invoke('delete', $obj_type, $object);
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_delete', $obj_type, $object);

  // Let other modules act on deleting the object.
  foreach (module_implements('field_attach_delete') as $module) {
    $function = $module . '_field_attach_delete';
    $function($obj_type, $object);
  }

  list($id, $vid, $bundle, $cacheable) = field_attach_extract_ids($obj_type, $object);
  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id:", 'cache_field', TRUE);
  }
}

/**
 * Delete field data for a single revision of an existing object.  The
 * passed object must have a revision id attribute.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to save.
 */
function _field_attach_delete_revision($obj_type, &$object) {
  _field_invoke('delete revision', $obj_type, $object);
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_delete_revision', $obj_type, $object);

  // Let other modules act on deleting the revision.
  foreach (module_implements('field_attach_delete_revision') as $module) {
    $function = $module . '_field_attach_delete_revision';
    $function($obj_type, $object);
  }

  list($id, $vid, $bundle, $cacheable) = field_attach_extract_ids($obj_type, $object);
  if ($cacheable) {
    cache_clear_all("field:$obj_type:$id:$vid", 'cache_field');
  }
}

/**
 * Generate and return a structured content array tree suitable for
 * drupal_render() for all of the fields on an object.  The format of
 * each field's rendered content depends on the display formatter and
 * its settings.
 *
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object with fields to render.
 * @param $teaser
 *   Whether to display the teaser only, as on the main page.
 * @return
 *   A structured content array tree for drupal_render().
 */
function _field_attach_view($obj_type, &$object, $teaser = FALSE) {
  // Let field modules sanitize their data for output.
  _field_invoke('sanitize', $obj_type, $object);

  $output = _field_invoke_default('view', $obj_type, $object, $teaser);

  // Let other modules make changes after rendering the view.
  foreach (module_implements('field_attach_view') as $module) {
    $function = $module . '_field_attach_view';
    $function($output, $obj_type, $object, $teaser);
  }

  return $output;

}

/**
 * To be called in entity preprocessor.
 *
 * - Adds $FIELD_NAME_rendered variables
 *   containing the themed output for the whole field.
 * - Adds the formatted values in the 'view' key of the items.
 */
function _field_attach_preprocess($obj_type, &$object) {
  return _field_invoke_default('preprocess', $obj_type, $object);
}

/**
 * Implementation of hook_nodeapi_prepare_translation.
 *
 * TODO D7: We do not yet know if this really belongs in Field API.
 */
function _field_attach_prepare_translation(&$node) {
  // Prevent against invalid 'nodes' built by broken 3rd party code.
  if (isset($node->type)) {
    $type = content_types($node->type);
    // Save cycles if the type has no fields.
    if (!empty($type['instances'])) {
      $default_additions = _field_invoke_default('prepare translation', $node);
      $additions = _field_invoke('prepare translation', $node);
      // Merge module additions after the default ones to enable overriding
      // of field values.
      $node = (object) array_merge((array) $node, $default_additions, $additions);
    }
  }
}

/**
 * Notify field.module that a new bundle was created.
 *
 * The default SQL-based storage doesn't need to do anytrhing about it, but
 * others might.
 *
 * @param $bundle
 *   The name of the newly created bundle.
 */
function _field_attach_create_bundle($bundle) {
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_create_bundle', $bundle);

  // Clear the cache.
  field_cache_clear();

  foreach (module_implements('field_attach_create_bundle') as $module) {
    $function = $module . '_field_attach_create_bundle';
    $function($bundle);
  }
}

/**
 * Notify field.module that a bundle was renamed.
 *
 * @param $bundle_old
 *   The previous name of the bundle.
 * @param $bundle_new
 *   The new name of the bundle.
 */
function _field_attach_rename_bundle($bundle_old, $bundle_new) {
  module_invoke(variable_get('field_storage_module', 'field_sql_storage'), 'field_storage_rename_bundle', $bundle_old, $bundle_new);
  db_update('field_config_instance')
    ->fields(array('bundle' => $bundle_new))
    ->condition('bundle', $bundle_old)
    ->execute();

  // Clear the cache.
  field_cache_clear();

  foreach (module_implements('field_attach_rename_bundle') as $module) {
    $function = $module . '_field_attach_rename_bundle';
    $function($bundle_old, $bundle_new);
  }
}

/**
 * Notify field.module the a bundle was deleted.
 *
 * This deletes the data for the field instances as well as the field instances
 * themselves.  This function actually just marks the data and field instances
 * and deleted, leaving the garbage collection for a separate process, because
 * it is not always possible to delete this much data in a single page request
 * (particularly since for some field types, the deletion is more than just a
 * simple DELETE query).
 *
 * @param $bundle
 *   The bundle to delete.
 */
function _field_attach_delete_bundle($bundle) {
  // Let other modules act on deleting the bundle
  foreach (module_implements('field_attach_delete_bundle') as $module) {
    $function = $module . '_field_attach_delete_bundle';
    $function($bundle);
  }

  // Delete the instances themseves
  $instances = field_info_instances($bundle);
  foreach ($instances as $instance) {
    field_delete_instance($instance['field_name'], $bundle);
  }
}

/**
 * Helper function to extract id, vid, and bundle name from an object.
 * 
 * @param $obj_type
 *   The type of $object; e.g. 'node' or 'user'.
 * @param $object
 *   The object from which to extract values.
 * @return
 *   A numerically indexed array (not a hash table) containing these
 *   elements:
 *
 *   0: primary id of the object
 *   1: revision id of the object, or NULL if $obj_type is not versioned
 *   2: bundle name of the object
 *   3: whether $obj_type's fields should be cached (TRUE/FALSE)
 */
function _field_attach_extract_ids($object_type, $object) {
  // TODO D7 : prevent against broken 3rd party $node without 'type'.
  $info = field_info_fieldable_types($object_type);
  // Objects being created might not have id/vid yet.
  $id = isset($object->{$info['id key']}) ? $object->{$info['id key']} : NULL;
  $vid = ($info['revision key'] && isset($object->{$info['revision key']})) ? $object->{$info['revision key']} : NULL;
  // If no bundle key provided, then we assume a single bundle, named after the
  // type of the object.
  $bundle = $info['bundle key'] ? $object->{$info['bundle key']} : $object_type;
  $cacheable = isset($info['cacheable']) ? $info['cacheable'] : FALSE;
  return array($id, $vid, $bundle, $cacheable);
}

/**
 * @autoload} End of "@autoload field_attach"
 */
