<?php

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
 * Return valid formatter type and settings.
 *
 * Backs up to default formatter and settings if the intended formatter is no
 * longer available. This might happen when the formatter has been renamed in
 * the module, or if the module has been disabled since then.
 */
function _field_get_formatter($display, $field) {
  if ($display['type'] != 'hidden') {
    $formatter_type = field_info_formatter_types($display['type']);
    if (empty($formatter_type)) {
      $field_type = field_info_field_types($field['type']);
      $display['type'] = $field_type['default_formatter'];
      $formatter_type = field_info_formatter_types($display['type']);
    }
    $function = $formatter_type['module'] . '_field_formatter_settings';
    if (drupal_function_exists($function)) {
      $display['settings'] += $function($display['type']);
    }
  }
  return $display;
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
          $info['field types'][$name] = $field_info;
          $info['field types'][$name]['module'] = $module;
        }
      }

      // Populate widget types.
      foreach (module_implements('field_widget_info') as $module) {
        $widget_types = (array) module_invoke($module, 'field_widget_info');
        foreach ($widget_types as $name => $widget_info) {
          $info['widget types'][$name] = $widget_info;
          $info['widget types'][$name]['module'] = $module;
        }
      }

      // Populate formatters.
      foreach (module_implements('field_formatter_info') as $module) {
        $formatter_types = (array) module_invoke($module, 'field_formatter_info');
        foreach ($formatter_types as $name => $formatter_info) {
          $info['formatter types'][$name] = $formatter_info;
          $info['formatter types'][$name]['module'] = $module;
        }
      }

      // Populate information about 'fieldable' entities.
      foreach (module_implements('fieldable_info') as $module) {
        $fieldable_types = (array) module_invoke($module, 'fieldable_info');
        foreach ($fieldable_types as $name => $fieldable_info) {
          // Provide defaults.
          $fieldable_info += array(
            'revision key' => '',
            'bundle key' => '',
            'cacheable' => TRUE,
            'bundles' => array(),
          );
          // If no bundle key provided, then we assume a single bundle, named
          // after the type of the object. Make sure the bundle created
          // has the human-readable name we need for bundle messages.
          if (empty($fieldable_info['bundle key'])) {
            $fieldable_info['bundles'] = array($name => $fieldable_info['name']);
          }
          $info['fieldable types'][$name] = $fieldable_info;
          $info['fieldable types'][$name]['module'] = $module;
        }
      }

      cache_set('field_info_types', $info, 'cache_field');
    }
  }

  return $info;
}

/**
 * Collate all information on existing fields and instances.
 *
 * @param $reset
 *   If TRUE, clear the cache. The information will be rebuilt from the database
 *   next time it is needed. Defaults to FALSE.
 * @return
 *   If $reset is TRUE, nothing.
 *   If $reset is FALSE, an array containing the following elements:
 *
 * fields: array of all defined Field objects, keyed by field name
 *
 * instances: array of bundled field info, keyed by bundle name
 *   * contains all FieldInstance objects for this bundle, keyed by field name.
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
      $info = $cached->data;
    }
    else {
      $info = array(
        'fields' => field_read_fields(),
        'instances' => array_fill_keys(array_keys(field_info_bundles()), array()),
      );

      // Populate instances.
      $instances = field_read_instances();
      foreach ($instances as $instance) {
        $info['instances'][$instance['bundle']][$instance['field_name']] = $instance;
      }

      cache_set('field_info_fields', $info, 'cache_field');
    }
  }

  return $info;
}

/**
 * @} End of "defgroup field_info"
 *
 * The rest of the functions in this file are not in a group, but
 * their automatically-generated autoloaders are (see field.autoload.inc).
 */

/**
 * @autoload field_(?:behaviors|info)_.* field_info FieldException {
 */

/**
 *  Helper function for determining the behavior of a field
 *  with respect to a given operation.
 *
 *  @param $op
 *    The name of the operation.
 *    Currently supported : none
 *    // TODO D7: no use cases (yet ?) - do we want to keep that function ?.
 *  @param $field
 *    The field array.
 *  @return
 *    FIELD_BEHAVIOR_NONE    - do nothing for this operation.
 *    FIELD_BEHAVIOR_CUSTOM  - use the field's callback function.
 *    FIELD_BEHAVIOR_DEFAULT - use field.module default behavior.
 */
function _field_behaviors_field($op, $field) {
  $info = field_info_field_types($field['type']);
  return isset($info['behaviors'][$op]) ? $info['behaviors'][$op] : FIELD_BEHAVIOR_DEFAULT;
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
function _field_behaviors_widget($op, $instance) {
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
function _field_behaviors_formatter($op, $display) {
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
function _field_info_field_types($field_type = NULL) {
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
function _field_info_widget_types($widget_type = NULL) {
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
function _field_info_formatter_types($formatter_type = NULL) {
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
function _field_info_fieldable_types($obj_type = NULL) {
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
function _field_info_bundles($obj_type = NULL) {
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
 * Identity the type of entity that created a bundle.
 * // TODO : might not be needed depending on how we solve
 * // the 'namespace bundle names' issue
 */
function _field_info_bundle_entity($bundle) {
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
 *   An array of Field objects.
 */
function _field_info_fields() {
  $info = _field_info_collate_fields();
  return $info['fields'];
}

/**
 * Return data about an individual field.
 *
 * @param $field_name
 */
function _field_info_field($field_name) {
  $info = _field_info_collate_fields();
  if (isset($info['fields'][$field_name])) {
    return $info['fields'][$field_name];
  }
}


/**
 * Return an array of instance data for a given bundle,
 * or for all known bundles, keyed by bundle name and field name.
 *
 * @param $bundle_name
 *   If set, return information on just this bundle.
 */
function _field_info_instances($bundle_name = NULL) {
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
function _field_info_instance($field_name, $bundle_name) {
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
function _field_info_field_settings($type) {
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
function _field_info_instance_settings($type) {
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
function _field_info_widget_settings($type) {
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
function _field_info_formatter_settings($type) {
  $info = field_info_formatter_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * @autoload} End of "@autoload field_info"
 */
