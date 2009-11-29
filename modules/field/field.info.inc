<?php
// $Id$

/**
 * @file
 * Field Info API, providing information about available fields and field types.
 */

/**
 * @defgroup field_info Field Info API
 * @{
 * Obtain information about Field API configuration.
 *
 * The Field Info API exposes information about field types, fields,
 * instances, bundles, widget types, display formatters, behaviors,
 * and settings defined by or with the Field API.
 */

/**
 * Clear the field info cache without clearing the field data cache.
 *
 * This is useful when deleted fields or instances are purged.  We
 * need to remove the purged records, but no actual field data items
 * are affected.
 */
function field_info_cache_clear() {
  // @todo: Remove this when field_attach_*_bundle() bundle management
  // functions are moved to the entity API.
  entity_info_cache_clear();

  _field_info_collate_types(TRUE);
  drupal_static_reset('field_build_modes');
  _field_info_collate_fields(TRUE);
}

/**
 * Collate all information on field types, widget types and related structures.
 *
 * @param $reset
 *   If TRUE, clear the cache. The information will be rebuilt from the database
 *   next time it is needed. Defaults to FALSE.
 * @return
 *   If $reset is TRUE, nothing.
 *   If $reset is FALSE, an array containing the following elements:
 *
 * field types: array of hook_field_info() results, keyed by field_type.
 *   * label, description, settings, instance_settings, default_widget,
 *     default_formatter, behaviors: from hook_field_info()
 *   * module: the module that exposes the field type
 *
 * widget types: array of hook_field_widget_info() results, keyed by
 * widget_type.
 *   * label, field types, settings, behaviors: from hook_field_widget_info()
 *   * module: module that exposes the widget type
 *
 * formatter types: array of hook_field_formatter_info() results, keyed by
 * formatter_type.
 *   * label, field types, behaviors: from hook_field_formatter_info()
 *   * module: module that exposes the formatter type
 */
function _field_info_collate_types($reset = FALSE) {
  static $info;

  if ($reset) {
    $info = NULL;
    cache_clear_all('field_info_types', 'cache_field');
    return;
  }

  if (!isset($info)) {
    if ($cached = cache_get('field_info_types', 'cache_field')) {
      $info = $cached->data;
    }
    else {
      $info = array(
        'field types' => array(),
        'widget types' => array(),
        'formatter types' => array(),
        'storage types' => array(),
      );

      // Populate field types.
      foreach (module_implements('field_info') as $module) {
        $field_types = (array) module_invoke($module, 'field_info');
        foreach ($field_types as $name => $field_info) {
          // Provide defaults.
          $field_info += array(
            'settings' => array(),
            'instance_settings' => array(),
          );
          $info['field types'][$name] = $field_info;
          $info['field types'][$name]['module'] = $module;
        }
      }
      drupal_alter('field_info', $info['field types']);

      // Populate widget types.
      foreach (module_implements('field_widget_info') as $module) {
        $widget_types = (array) module_invoke($module, 'field_widget_info');
        foreach ($widget_types as $name => $widget_info) {
          // Provide defaults.
          $widget_info += array(
            'settings' => array(),
          );
          $info['widget types'][$name] = $widget_info;
          $info['widget types'][$name]['module'] = $module;
        }
      }
      drupal_alter('field_widget_info', $info['widget types']);

      // Populate formatter types.
      foreach (module_implements('field_formatter_info') as $module) {
        $formatter_types = (array) module_invoke($module, 'field_formatter_info');
        foreach ($formatter_types as $name => $formatter_info) {
          // Provide defaults.
          $formatter_info += array(
            'settings' => array(),
          );
          $info['formatter types'][$name] = $formatter_info;
          $info['formatter types'][$name]['module'] = $module;
        }
      }
      drupal_alter('field_formatter_info', $info['formatter types']);

      // Populate storage types.
      foreach (module_implements('field_storage_info') as $module) {
        $storage_types = (array) module_invoke($module, 'field_storage_info');
        foreach ($storage_types as $name => $storage_info) {
          // Provide defaults.
          $storage_info += array(
            'settings' => array(),
          );
          $info['storage types'][$name] = $storage_info;
          $info['storage types'][$name]['module'] = $module;
        }
      }
      drupal_alter('field_storage_info', $info['storage types']);

      cache_set('field_info_types', $info, 'cache_field');
    }
  }

  return $info;
}

/**
 * Collate all information on existing fields and instances.
 *
 * @param $reset
 *   If TRUE, clear the cache. The information will be rebuilt from the
 *   database next time it is needed. Defaults to FALSE.
 * @return
 *   If $reset is TRUE, nothing.
 *   If $reset is FALSE, an array containing the following elements:
 *   - fields: Array of existing fields, keyed by field name. This entry only
 *     lists non-deleted fields. Each field has an additional element,
 *     'bundles', which is an array of all non-deleted instances to which the
 *     field is assigned.
 *   - fields_id: Array of existing fields, keyed by field id. This entry lists
 *     both deleted and non-deleted fields. The bundles element is the same as
 *     for 'fields'.
 *   - instances: Array of existing instances, keyed by object type, bundle
 *     name and field name. This entry only lists non-deleted instances.
 */
function _field_info_collate_fields($reset = FALSE) {
  static $info;

  if ($reset) {
    $info = NULL;
    cache_clear_all('field_info_fields', 'cache_field');
    return;
  }

  if (!isset($info)) {
    if ($cached = cache_get('field_info_fields', 'cache_field')) {
      $definitions = $cached->data;
    }
    else {
      $definitions = array(
        'field_ids' => field_read_fields(array(), array('include_deleted' => 1)),
        'instances' => field_read_instances(),
      );
      cache_set('field_info_fields', $definitions, 'cache_field');
    }

    // Populate 'field_ids' with all fields.
    $info['field_ids'] = array();
    foreach ($definitions['field_ids'] as $key => $field) {
      $info['field_ids'][$key] = $definitions['field_ids'][$key] = _field_info_prepare_field($field);
    }

    // Populate 'fields' only with non-deleted fields.
    $info['fields'] = array();
    foreach ($info['field_ids'] as $field) {
      if (!$field['deleted']) {
        $info['fields'][$field['field_name']] = $field;
      }
    }

    // Populate 'instances'. Only non-deleted instances are considered.
    $info['instances'] = array();
    foreach (field_info_bundles() as $obj_type => $bundles) {
      foreach ($bundles as $bundle => $bundle_info) {
        $info['instances'][$obj_type][$bundle] = array();
      }
    }
    foreach ($definitions['instances'] as $instance) {
      $field = $info['fields'][$instance['field_name']];
      $instance = _field_info_prepare_instance($instance, $field);
      $info['instances'][$instance['object_type']][$instance['bundle']][$instance['field_name']] = $instance;
      // Enrich field definitions with the list of bundles where they have
      // instances. NOTE: Deleted fields in $info['field_ids'] are not
      // enriched because all of their instances are deleted, too, and
      // are thus not in $definitions['instances'].
      $info['fields'][$instance['field_name']]['bundles'][$instance['object_type']][] = $instance['bundle'];
      $info['field_ids'][$instance['field_id']]['bundles'][$instance['object_type']][] = $instance['bundle'];

      // Add storage details.
      $details = (array) module_invoke($field['storage']['module'], 'field_storage_details', $field, $instance);
      drupal_alter('field_storage_details', $details, $field, $instance);
      $info['instances'][$instance['object_type']][$instance['bundle']][$instance['field_name']]['storage_details'] = $details;
    }
  }

  return $info;
}

 /**
 * Prepare a field definition for the current run-time context.
 *
 * Since the field was last saved or updated, new field settings can be
 * expected.
 *
 * @param $field
 *   The raw field structure as read from the database.
 */
function _field_info_prepare_field($field) {
  // Make sure all expected field settings are present.
  $field['settings'] += field_info_field_settings($field['type']);
  $field['storage']['settings'] += field_info_storage_settings($field['storage']['type']);

  return $field;
}

/**
 * Prepare an instance definition for the current run-time context.
 *
 * Since the instance was last saved or updated, a number of things might have
 * changed: widgets or formatters disabled, new settings expected, new build
 * modes added...
 *
 * @param $instance
 *   The raw instance structure as read from the database.
 * @param $field
 *   The field structure for the instance.
 */
function _field_info_prepare_instance($instance, $field) {
  $field_type = field_info_field_types($field['type']);

  // Make sure all expected instance settings are present.
  $instance['settings'] += field_info_instance_settings($field['type']);

  // Set a default value for the instance.
  if (field_behaviors_widget('default value', $instance) == FIELD_BEHAVIOR_DEFAULT && !isset($instance['default_value'])) {
    $instance['default_value'] = NULL;
  }

  // Fallback to default widget if widget type is not available.
  if (!field_info_widget_types($instance['widget']['type'])) {
    $instance['widget']['type'] = $field_type['default_widget'];
  }
  // Make sure all expected widget settings are present.
  $instance['widget']['settings'] += field_info_widget_settings($instance['widget']['type']);

  foreach ($instance['display'] as $build_mode => $display) {
    if ($display['type'] != 'hidden') {
      // Fallback to default formatter if formatter type is not available.
      if (!field_info_formatter_types($instance['display'][$build_mode]['type'])) {
        $instance['display'][$build_mode]['type'] = $field_type['default_formatter'];
      }
      // Make sure all expected formatter settings are present.
      $instance['display'][$build_mode]['settings'] += field_info_formatter_settings($instance['display'][$build_mode]['type']);
    }
  }

  // Fallback to 'full' display settings for unspecified build modes.
  foreach (field_build_modes($instance['object_type']) as $build_mode => $label) {
    if (!isset($instance['display'][$build_mode])) {
      $instance['display'][$build_mode] = $instance['display']['full'];
    }
  }

  return $instance;
}

/**
 *  Helper function for determining the behavior of a widget
 *  with respect to a given operation.
 *
 *  @param $op
 *    The name of the operation.
 *    Currently supported: 'default value', 'multiple values'.
 *  @param $instance
 *    The field instance array.
 *  @return
 *    FIELD_BEHAVIOR_NONE    - do nothing for this operation.
 *    FIELD_BEHAVIOR_CUSTOM  - use the widget's callback function.
 *    FIELD_BEHAVIOR_DEFAULT - use field.module default behavior.
 */
function field_behaviors_widget($op, $instance) {
  $info = field_info_widget_types($instance['widget']['type']);
  return isset($info['behaviors'][$op]) ? $info['behaviors'][$op] : FIELD_BEHAVIOR_DEFAULT;
}

/**
 *  Helper function for determining the behavior of a formatter
 *  with respect to a given operation.
 *
 *  @param $op
 *    The name of the operation.
 *    Currently supported: 'multiple values'
 *  @param $display
 *    The $instance['display'][$build_mode] array.
 *  @return
 *    FIELD_BEHAVIOR_NONE    - do nothing for this operation.
 *    FIELD_BEHAVIOR_CUSTOM  - use the formatter's callback function.
 *    FIELD_BEHAVIOR_DEFAULT - use field module default behavior.
 */
function field_behaviors_formatter($op, $display) {
  $info = field_info_formatter_types($display['type']);
  return isset($info['behaviors'][$op]) ? $info['behaviors'][$op] : FIELD_BEHAVIOR_DEFAULT;
}

/**
 * Return hook_field_info() data.
 *
 * @param $field_type
 *   (optional) A field type name. If ommitted, all field types will be
 *   returned.
 * @return
 *   Either a field type description, as provided by hook_field_info(), or an
 *   array of all existing field types, keyed by field type name.
 */
function field_info_field_types($field_type = NULL) {
  $info = _field_info_collate_types();
  $field_types = $info['field types'];
  if ($field_type) {
    if (isset($field_types[$field_type])) {
      return $field_types[$field_type];
    }
  }
  else {
    return $field_types;
  }
}

/**
 * Return hook_field_widget_info() data.
 *
 * @param $widget_type
 *   (optional) A widget type name. If ommitted, all widget types will be
 *   returned.
 * @return
 *   Either a widget type description, as provided by
 *   hook_field_widget_info(), or an array of all existing widget types, keyed
 *   by widget type name.
 */
function field_info_widget_types($widget_type = NULL) {
  $info = _field_info_collate_types();
  $widget_types = $info['widget types'];
  if ($widget_type) {
    if (isset($widget_types[$widget_type])) {
      return $widget_types[$widget_type];
    }
  }
  else {
    return $widget_types;
  }
}

/**
 * Return hook_field_formatter_info() data.
 *
 * @param $formatter_type
 *   (optional) A formatter type name. If ommitted, all formatter types will be
 *   returned.
 * @return
 *   Either a formatter type description, as provided by
 *   hook_field_formatter_info(), or an array of all existing formatter types,
 *   keyed by formatter type name.
 */
function field_info_formatter_types($formatter_type = NULL) {
  $info = _field_info_collate_types();
  $formatter_types = $info['formatter types'];
  if ($formatter_type) {
    if (isset($formatter_types[$formatter_type])) {
      return $formatter_types[$formatter_type];
    }
  }
  else {
    return $formatter_types;
  }
}

/**
 * Return hook_field_storage_info() data.
 *
 * @param $storage_type
 *   (optional) A storage type name. If ommitted, all storage types will be
 *   returned.
 * @return
 *   Either a storage type description, as provided by
 *   hook_field_storage_info(), or an array of all existing storage types,
 *   keyed by storage type name.
 */
function field_info_storage_types($storage_type = NULL) {
  $info = _field_info_collate_types();
  $storage_types = $info['storage types'];
  if ($storage_type) {
    if (isset($storage_types[$storage_type])) {
      return $storage_types[$storage_type];
    }
  }
  else {
    return $storage_types;
  }
}

/**
 * Return information about existing bundles.
 *
 * @param $obj_type
 *   The type of object; e.g. 'node' or 'user'.
 * @return
 *   An array of bundles for the $obj_type keyed by bundle name,
 *   or, if no $obj_type was provided, the array of all existing bundles,
 *   keyed by object type.
 */
function field_info_bundles($obj_type = NULL) {
  $info = entity_get_info();

  if ($obj_type) {
    return isset($info[$obj_type]['bundles']) ? $info[$obj_type]['bundles'] : array();
  }

  $bundles = array();
  foreach ($info as $type => $entity_info) {
    $bundles[$type] = $entity_info['bundles'];
  }
  return $bundles;
}

/**
 * Return all field efinitions.
 *
 * @return
 *   An array of field definitions, keyd by field name. Each field has an
 *   additional property, 'bundles', which is an array of all the bundles to
 *   which this field belongs.
 */
function field_info_fields() {
  $info = _field_info_collate_fields();
  return $info['fields'];
}

/**
 * Return data about an individual field.
 *
 * @param $field_name
 *   The name of the field to retrieve. $field_name can only refer to a
 *   non-deleted field.
 * @return
 *   The named field object, or NULL. The Field object has an additional
 *   property, bundles, which is an array of all the bundles to which
 *   this field belongs.
 */
function field_info_field($field_name) {
  $info = _field_info_collate_fields();
  if (isset($info['fields'][$field_name])) {
    return $info['fields'][$field_name];
  }
}

/**
 * Return data about an individual field by its id.
 *
 * @param $field_id
 *   The id of the field to retrieve. $field_id can refer to a
 *   deleted field.
 * @return
 *   The named field object, or NULL. The Field object has an additional
 *   property, bundles, which is an array of all the bundles to which
 *   this field belongs.
 */
function field_info_field_by_id($field_id) {
  $info = _field_info_collate_fields();
  if (isset($info['field_ids'][$field_id])) {
    return $info['field_ids'][$field_id];
  }
}

/**
 * Retrieve instances.
 *
 * @param $obj_type
 *   The object type for which to return instances.
 * @param $bundle_name
 *   The bundle name for which to return instances.
 * @return
 *   If $obj_type is not set, return all instances keyed by object type and
 *   bundle name. If $obj_type is set, return all instances for that object
 *   type, keyed by bundle name. If $obj_type and $bundle_name are set, return
 *   all instances for that bundle.
 */
function field_info_instances($obj_type = NULL, $bundle_name = NULL) {
  $info = _field_info_collate_fields();
  if (!isset($obj_type)) {
    return $info['instances'];
  }
  if (!isset($bundle_name)) {
    return $info['instances'][$obj_type];
  }
  if (isset($info['instances'][$obj_type][$bundle_name])) {
    return $info['instances'][$obj_type][$bundle_name];
  }
  return array();
}

/**
 * Return an array of instance data for a specific field and bundle.
 *
 * @param $obj_type
 *   The object type for the instance.
 * @param $field_name
 *   The field name for the instance.
 * @param $bundle_name
 *   The bundle name for the instance.
 */
function field_info_instance($obj_type, $field_name, $bundle_name) {
  $info = _field_info_collate_fields();
  if (isset($info['instances'][$obj_type][$bundle_name][$field_name])) {
    return $info['instances'][$obj_type][$bundle_name][$field_name];
  }
}

/**
 * Return a field type's default settings.
 *
 * @param $type
 *   A field type name.
 * @return
 *   The field type's default settings, as provided by hook_field_info(), or an
 *   empty array.
 */
function field_info_field_settings($type) {
  $info = field_info_field_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * Return a field type's default instance settings.
 *
 * @param $type
 *   A field type name.
 * @return
 *   The field type's default instance settings, as provided by
 *   hook_field_info(), or an empty array.
 */
function field_info_instance_settings($type) {
  $info = field_info_field_types($type);
  return isset($info['instance_settings']) ? $info['instance_settings'] : array();
}

/**
 * Return a field widget's default settings.
 *
 * @param $type
 *   A widget type name.
 * @return
 *   The widget type's default settings, as provided by
 *   hook_field_widget_info(), or an empty array.
 */
function field_info_widget_settings($type) {
  $info = field_info_widget_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * Return a field formatter's default settings.
 *
 * @param $type
 *   A field formatter type name.
 * @return
 *   The formatter type's default settings, as provided by
 *   hook_field_formatter_info(), or an empty array.
 */
function field_info_formatter_settings($type) {
  $info = field_info_formatter_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * Return a field formatter's default settings.
 *
 * @param $type
 *   A field storage type name.
 * @return
 *   The storage type's default settings, as provided by
 *   hook_field_storage_info(), or an empty array.
 */
function field_info_storage_settings($type) {
  $info = field_info_storage_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * @} End of "defgroup field_info"
 */
