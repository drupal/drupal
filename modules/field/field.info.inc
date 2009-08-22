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
function _field_info_cache_clear() {
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

 * fieldable types: array of hook_fieldable_info() results, keyed by entity_type.
 *   * name, id key, revision key, bundle key, cacheable, bundles: from
 *     hook_fieldable_info()
 *   * module: module that exposes the entity type
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
        'fieldable types' => array(),
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

      // Populate formatters.
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

      // Populate information about 'fieldable' entities.
      foreach (module_implements('fieldable_info') as $module) {
        $fieldable_types = (array) module_invoke($module, 'fieldable_info');
        foreach ($fieldable_types as $name => $fieldable_info) {
          // Provide defaults.
          $fieldable_info += array(
            'cacheable' => TRUE,
            'translation_handlers' => array(),
            'bundles' => array(),
          );
          $fieldable_info['object keys'] += array(
            'revision' => '',
            'bundle' => '',
          );
          // If no bundle key provided, then we assume a single bundle, named
          // after the type of the object. Make sure the bundle created
          // has the human-readable name we need for bundle messages.
          if (empty($fieldable_info['object keys']['bundle']) && empty($fieldable_info['bundles'])) {
            $fieldable_info['bundles'] = array($name => array('label' => $fieldable_info['label']));
          }
          $info['fieldable types'][$name] = $fieldable_info;
          $info['fieldable types'][$name]['module'] = $module;
        }
      }
      drupal_alter('fieldable_info', $info['fieldable types']);

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
 *   - instances: Array of existing instances, keyed by bundle name and field
 *     name. This entry only lists non-deleted instances.
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
    $info['field'] = array();
    foreach ($info['field_ids'] as $field) {
      if (!$field['deleted']) {
        $info['fields'][$field['field_name']] = $field;
      }
    }

    // Populate 'instances'. Only non-deleted instances are considered.
    $info['instances'] = array();
    foreach (field_info_bundles() as $bundle => $bundle_info) {
      $info['instances'][$bundle] = array();
    }
    foreach ($definitions['instances'] as $instance) {
      $field = $info['fields'][$instance['field_name']];
      $instance = _field_info_prepare_instance($instance, $field);
      $info['instances'][$instance['bundle']][$instance['field_name']] = $instance;
      // Enrich field definitions with the list of bundles where they have
      // instances. NOTE: Deleted fields in $info['field_ids'] are not
      // enriched because all of their instances are deleted, too, and
      // are thus not in $definitions['instances'].
      $info['fields'][$instance['field_name']]['bundles'][] = $instance['bundle'];
      $info['field_ids'][$instance['field_id']]['bundles'][] = $instance['bundle'];
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
  $obj_type = field_info_bundle_entity($instance['bundle']);
  foreach (field_build_modes($obj_type) as $build_mode => $label) {
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
 *   hook_field_widget_info(), or an array of all existing widget
 *   types, keyed by widget type name.
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
 *   Either a formatter type description, as provided by hook_field_formatter_info(),
 *   or an array of all existing widget types, keyed by widget type name.
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
 * Return hook_fieldable_info() data.
 *
 * @param $obj_type
 *   (optional) A fieldable type name. If ommitted, all fieldable types will be
 *   returned.
 * @return
 *   Either a fieldable type description, as provided by hook_fieldable_info(),
 *   or an array of all existing fieldable types, keyed by fieldable type name.
 */
function field_info_fieldable_types($obj_type = NULL) {
  $info = _field_info_collate_types();
  $fieldable_types = $info['fieldable types'];
  if ($obj_type) {
    if (isset($fieldable_types[$obj_type])) {
      return $fieldable_types[$obj_type];
    }
  }
  else {
    return $fieldable_types;
  }
}

/**
 * Return an array of fieldable bundle names and labels, for an individual
 * object type or for all object types.
 */
function field_info_bundles($obj_type = NULL) {
  $info = _field_info_collate_types();
  $bundles = array();
  foreach ($info['fieldable types'] as $type => $fieldable_info) {
    if (empty($obj_type) || $obj_type == $type) {
      $bundles += $fieldable_info['bundles'];
    }
  }
  return $bundles;
}

/**
 * Identify the type of entity that created a bundle.
 * // TODO : might not be needed depending on how we solve
 * // the 'namespace bundle names' issue
 */
function field_info_bundle_entity($bundle) {
  $info = _field_info_collate_types();
  foreach ($info['fieldable types'] as $type => $fieldable_info) {
    if (isset($fieldable_info['bundles'][$bundle])) {
      return $type;
    }
  }
  return FALSE;
}

/**
 * Return array of all field data, keyed by field name.
 *
 * @return
 *   An array of Field objects. Each Field object has an additional
 *   property, bundles, which is an array of all the bundles to which
 *   this field belongs.
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
 * Return an array of instance data for a given bundle,
 * or for all known bundles, keyed by bundle name and field name.
 *
 * @param $bundle_name
 *   If set, return information on just this bundle.
 */
function field_info_instances($bundle_name = NULL) {
  $info = _field_info_collate_fields();
  if (!isset($bundle_name)) {
    return $info['instances'];
  }
  if (isset($info['instances'][$bundle_name])) {
    return $info['instances'][$bundle_name];
  }
  return array();
}

/**
 * Return an array of instance data for a specific field and bundle.
 */
function field_info_instance($field_name, $bundle_name) {
  $info = _field_info_collate_fields();
  if (isset($info['instances'][$bundle_name][$field_name])) {
    return $info['instances'][$bundle_name][$field_name];
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
 *   The field type's default settings, as provided by hook_field_info(), or an
 *   empty array.
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
 *   The field formatter's default settings, as provided by
 *   hook_field_info(), or an empty array.
 */
function field_info_formatter_settings($type) {
  $info = field_info_formatter_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * @} End of "defgroup field_info"
 */
