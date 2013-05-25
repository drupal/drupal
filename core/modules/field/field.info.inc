<?php

/**
 * @file
 * Field Info API, providing information about available fields and field types.
 */

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\Language;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\field\Field;

/**
 * @defgroup field_info Field Info API
 * @{
 * Obtains information about Field API configuration.
 *
 * The Field Info API exposes information about field types, fields, instances,
 * bundles, widget types, display formatters, behaviors, and settings defined by
 * or with the Field API.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Clears the field info cache without clearing the field data cache.
 *
 * This is useful when deleted fields or instances are purged. We need to remove
 * the purged records, but no actual field data items are affected.
 */
function field_info_cache_clear() {
  drupal_static_reset('field_view_mode_settings');
  drupal_static_reset('field_available_languages');

  // @todo: Remove this when field_attach_*_bundle() bundle management
  // functions are moved to the entity API.
  entity_info_cache_clear();

  _field_info_collate_types_reset();
  Field::fieldInfo()->flush();
}

/**
 * Collates all information on field types, widget types and related structures.
 *
 * @return
 *   An associative array containing:
 *   - 'field types': Array of hook_field_info() results, keyed by field_type.
 *     Each element has the following components: label, description, settings,
 *     instance_settings, default_widget, default_formatter, and behaviors
 *     from hook_field_info(), as well as module, giving the module that exposes
 *     the field type.
 *   - 'storage types': Array of hook_field_storage_info() results, keyed by
 *     storage type names. Each element has the following components: label,
 *     description, and settings from hook_field_storage_info(), as well as
 *     module, giving the module that exposes the storage type.
 *
 * @see _field_info_collate_types_reset()
 */
function _field_info_collate_types() {
  $language_interface = language(Language::TYPE_INTERFACE);

  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;

  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['field_info_collate_types'] = &drupal_static(__FUNCTION__);
  }
  $info = &$drupal_static_fast['field_info_collate_types'];

  // The _info() hooks invoked below include translated strings, so each
  // language is cached separately.
  $langcode = $language_interface->langcode;

  if (!isset($info)) {
    if ($cached = cache('field')->get("field_info_types:$langcode")) {
      $info = $cached->data;
    }
    else {
      $info = array(
        'field types' => array(),
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

      cache('field')->set("field_info_types:$langcode", $info, CacheBackendInterface::CACHE_PERMANENT, array('field_info_types' => TRUE));
    }
  }

  return $info;
}

/**
 * Clears collated information on field and widget types and related structures.
 */
function _field_info_collate_types_reset() {
  drupal_static_reset('_field_info_collate_types');
  // Clear all languages.
  cache('field')->deleteTags(array('field_info_types' => TRUE));
}

/**
 * Determines the behavior of a widget with respect to an operation.
 *
 * @param string $op
 *   The name of the operation. Currently supported: 'default_value',
 *   'multiple_values'.
 * @param array $instance
 *   The field instance array.
 *
 * @return int
 *   One of these values:
 *   - FIELD_BEHAVIOR_NONE: Do nothing for this operation.
 *   - FIELD_BEHAVIOR_CUSTOM: Use the widget's callback function.
 *   - FIELD_BEHAVIOR_DEFAULT: Use field.module default behavior.
 */
function field_behaviors_widget($op, $instance) {
  $info = array();
  if ($component = entity_get_form_display($instance['entity_type'], $instance['bundle'], 'default')->getComponent($instance['field_name'])) {
    $info = field_info_widget_types($component['type']);
  }
  return isset($info[$op]) ? $info[$op] : FIELD_BEHAVIOR_DEFAULT;
}

/**
 * Returns a lightweight map of fields across bundles.
 *
 * The function only returns active, non deleted fields.
 *
 * @return
 *   An array keyed by field name. Each value is an array with two entries:
 *   - type: The field type.
 *   - bundles: The bundles in which the field appears, as an array with entity
 *     types as keys and the array of bundle names as values.
 * Example:
 * @code
 * array(
 *   'body' => array(
 *     'bundles' => array(
 *       'node' => array('page', 'article'),
 *     ),
 *     'type' => 'text_with_summary',
 *   ),
 * );
 * @endcode
 */
function field_info_field_map() {
  return Field::fieldInfo()->getFieldMap();
}

/**
 * Returns information about field types from hook_field_info().
 *
 * @param $field_type
 *   (optional) A field type name. If omitted, all field types will be returned.
 *
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
 * Returns information about field widgets from AnnotatedClassDiscovery.
 *
 * @param string $widget_type
 *   (optional) A widget type name. If omitted, all widget types will be
 *   returned.
 *
 * @return array
 *   Either a single widget type description, as provided by class annotations,
 *   or an array of all existing widget types, keyed by widget type name.
 */
function field_info_widget_types($widget_type = NULL) {
  if ($widget_type) {
    return drupal_container()->get('plugin.manager.field.widget')->getDefinition($widget_type);
  }
  else {
    return drupal_container()->get('plugin.manager.field.widget')->getDefinitions();
  }
}

/**
 * Returns information about field formatters from hook_field_formatter_info().
 *
 * @param string $formatter_type
 *   (optional) A formatter type name. If omitted, all formatter types will be
 *   returned.
 *
 * @return array
 *   Either a single formatter type description, as provided by class
 *   annotations, or an array of all existing formatter types, keyed by
 *   formatter type name.
 */
function field_info_formatter_types($formatter_type = NULL) {
  if ($formatter_type) {
    return drupal_container()->get('plugin.manager.field.formatter')->getDefinition($formatter_type);
  }
  else {
    return drupal_container()->get('plugin.manager.field.formatter')->getDefinitions();
  }
}

/**
 * Returns information about field storage from hook_field_storage_info().
 *
 * @param $storage_type
 *   (optional) A storage type name. If omitted, all storage types will be
 *   returned.
 *
 * @return
 *   Either a storage type description, as provided by
 *   hook_field_storage_info(), or an array of all existing storage types, keyed
 *   by storage type name.
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
 * Returns all field definitions.
 *
 * Use of this function should be avoided when possible, since it loads and
 * statically caches a potentially large array of information. Use
 * field_info_field_map() instead.
 *
 * When iterating over the fields present in a given bundle after a call to
 * field_info_instances($entity_type, $bundle), it is recommended to use
 * field_info_field() on each individual field instead.
 *
 * @return
 *   An array of field definitions, keyed by field name. Each field has an
 *   additional property, 'bundles', which is an array of all the bundles to
 *   which this field belongs, keyed by entity type.
 *
 * @see field_info_field_map()
 */
function field_info_fields() {
  $info = Field::fieldInfo()->getFields();

  $fields = array();
  foreach ($info as $key => $field) {
    if (!$field['deleted']) {
      $fields[$field['field_name']] = $field;
    }
  }

  return $fields;
}

/**
 * Returns data about an individual field, given a field name.
 *
 * @param $field_name
 *   The name of the field to retrieve. $field_name can only refer to a
 *   non-deleted, active field. For deleted fields, use
 *   field_info_field_by_id(). To retrieve information about inactive fields,
 *   use field_read_fields().
 *
 * @return
 *   The field array, as returned by field_read_fields(), with an
 *   additional element 'bundles', whose value is an array of all the bundles
 *   this field belongs to keyed by entity type. NULL if the field was not
 *   found.
 *
 * @see field_info_field_by_id()
 */
function field_info_field($field_name) {
  return Field::fieldInfo()->getField($field_name);
}

/**
 * Returns data about an individual field, given a field ID.
 *
 * @param $field_id
 *   The ID of the field to retrieve. $field_id can refer to a deleted field,
 *   but not an inactive one.
 *
 * @return
 *   The field array, as returned by field_read_fields(), with an additional
 *   element 'bundles', whose value is an array of all the bundles this field
 *   belongs to.
 *
 * @see field_info_field()
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
 * field_info_instances($entity_type, $bundle), it is recommended to use
 * field_info_field() on each individual field instead.
 *
 * @return
 *   An array, each key is a field ID and the values are field arrays as
 *   returned by field_read_fields(), with an additional element 'bundles',
 *   whose value is an array of all the bundle this field belongs to.
 *
 * @see field_info_field()
 * @see field_info_field_by_id()
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
 */
function field_info_instance($entity_type, $field_name, $bundle_name) {
  $info = Field::fieldInfo()->getBundleInstances($entity_type, $bundle_name);
  if (isset($info[$field_name])) {
    return $info[$field_name];
  }
}

/**
 * Returns a list and settings of pseudo-field elements in a given bundle.
 *
 * If $context is 'form', an array with the following structure:
 * @code
 *   array(
 *     'name_of_pseudo_field_component' => array(
 *       'label' => The human readable name of the component,
 *       'description' => A short description of the component content,
 *       'weight' => The weight of the component in edit forms,
 *     ),
 *     'name_of_other_pseudo_field_component' => array(
 *       // ...
 *     ),
 *   );
 * @endcode
 *
 * If $context is 'display', an array with the following structure:
 * @code
 *   array(
 *     'name_of_pseudo_field_component' => array(
 *       'label' => The human readable name of the component,
 *       'description' => A short description of the component content,
 *       // One entry per view mode, including the 'default' mode:
 *       'display' => array(
 *         'default' => array(
 *           'weight' => The weight of the component in displayed entities in
 *             this view mode,
 *           'visible' => TRUE if the component is visible, FALSE if hidden, in
 *             displayed entities in this view mode,
 *         ),
 *         'teaser' => array(
 *           // ...
 *         ),
 *       ),
 *     ),
 *     'name_of_other_pseudo_field_component' => array(
 *       // ...
 *     ),
 *   );
 * @endcode
 *
 * @param $entity_type
 *   The type of entity; e.g. 'node' or 'user'.
 * @param $bundle
 *   The bundle name.
 * @param $context
 *   The context for which the list of pseudo-fields is requested. Either 'form'
 *   or 'display'.
 *
 * @return
 *   The array of pseudo-field elements in the bundle.
 */
function field_info_extra_fields($entity_type, $bundle, $context) {
  $info = Field::fieldInfo()->getBundleExtraFields($entity_type, $bundle);

  return isset($info[$context]) ? $info[$context] : array();
}

/**
 * Returns a field type's default settings.
 *
 * @param $type
 *   A field type name.
 *
 * @return
 *   The field type's default settings, as provided by hook_field_info(), or an
 *   empty array if type or settings are not defined.
 */
function field_info_field_settings($type) {
  $info = field_info_field_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * Returns a field type's default instance settings.
 *
 * @param $type
 *   A field type name.
 *
 * @return
 *   The field type's default instance settings, as provided by
 *   hook_field_info(), or an empty array if type or settings are not defined.
 */
function field_info_instance_settings($type) {
  $info = field_info_field_types($type);
  return isset($info['instance_settings']) ? $info['instance_settings'] : array();
}

/**
 * Returns a field widget's default settings.
 *
 * @param $type
 *   A widget type name.
 *
 * @return
 *   The widget type's default settings, as provided by
 *   hook_field_widget_info(), or an empty array if type or settings are
 *   undefined.
 */
function field_info_widget_settings($type) {
  $info = field_info_widget_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * Returns a field formatter's default settings.
 *
 * @param $type
 *   A field formatter type name.
 *
 * @return
 *   The formatter type's default settings, as provided by
 *   hook_field_formatter_info(), or an empty array if type or settings are
 *   undefined.
 */
function field_info_formatter_settings($type) {
  $info = field_info_formatter_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * Returns a field storage type's default settings.
 *
 * @param $type
 *   A field storage type name.
 *
 * @return
 *   The storage type's default settings, as provided by
 *   hook_field_storage_info(), or an empty array if type or settings are
 *   undefined.
 */
function field_info_storage_settings($type) {
  $info = field_info_storage_types($type);
  return isset($info['settings']) ? $info['settings'] : array();
}

/**
 * @} End of "defgroup field_info".
 */
