<?php

/**
 * @file
 * Field Info API, providing information about available fields and field types.
 */

/**
 * Retrieves the FieldInfo object for the current request.
 *
 * @return FieldInfo
 *   An instance of the FieldInfo class.
 */
function _field_info_field_cache() {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;

  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['field_info_field_cache'] = &drupal_static(__FUNCTION__);
  }
  $field_info = &$drupal_static_fast['field_info_field_cache'];

  if (!isset($field_info)) {
    // @todo The registry should save the need for an explicit include, but not
    // a couple upgrade tests (DisabledNodeTypeTestCase,
    // FilterFormatUpgradePathTestCase...) break in a strange way without it.
    include_once dirname(__FILE__) . '/field.info.class.inc';
    $field_info = new FieldInfo();
  }

  return $field_info;
}

/**
 * @defgroup field_info Field Info API
 * @{
 * Obtain information about Field API configuration.
 *
 * The Field Info API exposes information about field types, fields,
 * instances, bundles, widget types, display formatters, behaviors,
 * and settings defined by or with the Field API.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Clears the field info cache without clearing the field data cache.
 *
 * This is useful when deleted fields or instances are purged.  We
 * need to remove the purged records, but no actual field data items
 * are affected.
 */
function field_info_cache_clear() {
  drupal_static_reset('field_view_mode_settings');
  drupal_static_reset('field_available_languages');

  // @todo: Remove this when field_attach_*_bundle() bundle management
  // functions are moved to the entity API.
  entity_info_cache_clear();

  _field_info_collate_types(TRUE);
  _field_info_field_cache()->flush();
}

/**
 * Collates all information on existing fields and instances.
 *
 * Deprecated. This function is kept to ensure backwards compatibility, but has
 * a serious performance impact, and should be absolutely avoided.
 * See http://drupal.org/node/1915646.
 *
 * Use the regular field_info_*() API functions to access the information, or
 * field_info_cache_clear() to clear the cached data.
 */
function _field_info_collate_fields($reset = FALSE) {
  if ($reset) {
    _field_info_field_cache()->flush();
    return;
  }

  $cache = _field_info_field_cache();

  // Collect fields, and build the array of IDs keyed by field_name.
  $fields = $cache->getFields();
  $field_ids = array();
  foreach ($fields as $id => $field) {
    if (!$field['deleted']) {
      $field_ids[$field['field_name']] = $id;
    }
  }

  // Collect extra fields for all entity types.
  $extra_fields = array();
  foreach (field_info_bundles() as $entity_type => $bundles) {
    foreach ($bundles as $bundle => $info) {
      $extra_fields[$entity_type][$bundle] = $cache->getBundleExtraFields($entity_type, $bundle);
    }
  }

  return array(
    'fields' => $fields,
    'field_ids' => $field_ids,
    'instances' => $cache->getInstances(),
    'extra_fields' => $extra_fields,
  );
}

/**
 * Collates all information on field types, widget types and related structures.
 *
 * @param $reset
 *   If TRUE, clear the cache. The information will be rebuilt from the database
 *   next time it is needed. Defaults to FALSE.
 *
 * @return
 *   If $reset is TRUE, nothing.
 *   If $reset is FALSE, an array containing the following elements:
 *   - 'field types': Array of hook_field_info() results, keyed by field_type.
 *     Each element has the following components: label, description, settings,
 *     instance_settings, default_widget, default_formatter, and behaviors
 *     from hook_field_info(), as well as module, giving the module that exposes
 *     the field type.
 *   - 'widget types': Array of hook_field_widget_info() results, keyed by
 *     widget_type. Each element has the following components: label, field
 *     types, settings, weight, and behaviors from hook_field_widget_info(),
 *     as well as module, giving the module that exposes the widget type.
 *   - 'formatter types': Array of hook_field_formatter_info() results, keyed by
 *     formatter_type. Each element has the following components: label, field
 *     types, and behaviors from hook_field_formatter_info(), as well as
 *     module, giving the module that exposes the formatter type.
 *   - 'storage types': Array of hook_field_storage_info() results, keyed by
 *     storage type names. Each element has the following components: label,
 *     description, and settings from hook_field_storage_info(), as well as
 *     module, giving the module that exposes the storage type.
 *   - 'fieldable types': Array of hook_entity_info() results, keyed by
 *     entity_type. Each element has the following components: name, id key,
 *     revision key, bundle key, cacheable, and bundles from hook_entity_info(),
 *     as well as module, giving the module that exposes the entity type.
 */
function _field_info_collate_types($reset = FALSE) {
  global $language;
  static $info;

  // The _info() hooks invoked below include translated strings, so each
  // language is cached separately.
  $langcode = $language->language;

  if ($reset) {
    $info = NULL;
    // Clear all languages.
    cache_clear_all('field_info_types:', 'cache_field', TRUE);
    return;
  }

  if (!isset($info)) {
    if ($cached = cache_get("field_info_types:$langcode", 'cache_field')) {
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
      uasort($info['widget types'], 'drupal_sort_weight');

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

      // Set the cache if we can acquire a lock.
      if (lock_acquire("field_info_types:$langcode")) {
        cache_set("field_info_types:$langcode", $info, 'cache_field');
        lock_release("field_info_types:$langcode");
      }
    }
  }

  return $info;
}

/**
 * Prepares a field definition for the current run-time context.
 *
 * The functionality has moved to the FieldInfo class. This function is kept as
 * a backwards-compatibility layer. See http://drupal.org/node/1915646.
 *
 * @see FieldInfo::prepareField()
 */
function _field_info_prepare_field($field) {
  $cache = _field_info_field_cache();
  return $cache->prepareField($field);
}

/**
 * Prepares an instance definition for the current run-time context.
 *
 * The functionality has moved to the FieldInfo class. This function is kept as
 * a backwards-compatibility layer. See http://drupal.org/node/1915646.
 *
 * @see FieldInfo::prepareInstance()
 */
function _field_info_prepare_instance($instance, $field) {
  $cache = _field_info_field_cache();
  return $cache->prepareInstance($instance, $field['type']);
}

/**
 * Adapts display specifications to the current run-time context.
 *
 * The functionality has moved to the FieldInfo class. This function is kept as
 * a backwards-compatibility layer. See http://drupal.org/node/1915646.
 *
 * @see FieldInfo::prepareInstanceDisplay()
 */
function _field_info_prepare_instance_display($field, $display) {
  $cache = _field_info_field_cache();
  return $cache->prepareInstanceDisplay($display, $field['type']);
}

/**
 * Prepares widget specifications for the current run-time context.
 *
 * The functionality has moved to the FieldInfo class. This function is kept as
 * a backwards-compatibility layer. See http://drupal.org/node/1915646.
 *
 * @see FieldInfo::prepareInstanceWidget()
 */
function _field_info_prepare_instance_widget($field, $widget) {
  $cache = _field_info_field_cache();
  return $cache->prepareInstanceWidget($widget, $field['type']);
}

/**
 * Prepares 'extra fields' for the current run-time context.
 *
 * The functionality has moved to the FieldInfo class. This function is kept as
 * a backwards-compatibility layer. See http://drupal.org/node/1915646.
 *
 * @see FieldInfo::prepareExtraFields()
 */
function _field_info_prepare_extra_fields($extra_fields, $entity_type, $bundle) {
  $cache = _field_info_field_cache();
  return $cache->prepareExtraFields($extra_fields, $entity_type, $bundle);
}

/**
 * Determines the behavior of a widget with respect to an operation.
 *
 * @param $op
 *   The name of the operation. Currently supported: 'default value',
 *   'multiple values'.
 * @param $instance
 *   The field instance array.
 *
 * @return
 *   One of these values:
 *   - FIELD_BEHAVIOR_NONE: Do nothing for this operation.
 *   - FIELD_BEHAVIOR_CUSTOM: Use the widget's callback function.
 *   - FIELD_BEHAVIOR_DEFAULT: Use field.module default behavior.
 */
function field_behaviors_widget($op, $instance) {
  $info = field_info_widget_types($instance['widget']['type']);
  return isset($info['behaviors'][$op]) ? $info['behaviors'][$op] : FIELD_BEHAVIOR_DEFAULT;
}

/**
 * Returns information about field types from hook_field_info().
 *
 * @param $field_type
 *   (optional) A field type name. If omitted, all field types will be
 *   returned.
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
 * Returns information about field widgets from hook_field_widget_info().
 *
 * @param $widget_type
 *   (optional) A widget type name. If omitted, all widget types will be
 *   returned.
 *
 * @return
 *   Either a single widget type description, as provided by
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
 * Returns information about field formatters from hook_field_formatter_info().
 *
 * @param $formatter_type
 *   (optional) A formatter type name. If omitted, all formatter types will be
 *   returned.
 *
 * @return
 *   Either a single formatter type description, as provided by
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
 * Returns information about field storage from hook_field_storage_info().
 *
 * @param $storage_type
 *   (optional) A storage type name. If omitted, all storage types will be
 *   returned.
 *
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
 * Returns information about existing bundles.
 *
 * @param $entity_type
 *   The type of entity; e.g. 'node' or 'user'.
 *
 * @return
 *   An array of bundles for the $entity_type keyed by bundle name,
 *   or, if no $entity_type was provided, the array of all existing bundles,
 *   keyed by entity type.
 */
function field_info_bundles($entity_type = NULL) {
  $info = entity_get_info();

  if ($entity_type) {
    return isset($info[$entity_type]['bundles']) ? $info[$entity_type]['bundles'] : array();
  }

  $bundles = array();
  foreach ($info as $type => $entity_info) {
    $bundles[$type] = $entity_info['bundles'];
  }
  return $bundles;
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
  $cache = _field_info_field_cache();
  return $cache->getFieldMap();
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
 *   which this field belongs keyed by entity type.
 *
 * @see field_info_field_map()
 */
function field_info_fields() {
  $cache = _field_info_field_cache();
  $info = $cache->getFields();

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
  $cache = _field_info_field_cache();
  return $cache->getField($field_name);
}

/**
 * Returns data about an individual field, given a field ID.
 *
 * @param $field_id
 *   The id of the field to retrieve. $field_id can refer to a
 *   deleted field, but not an inactive one.
 *
 * @return
 *   The field array, as returned by field_read_fields(), with an
 *   additional element 'bundles', whose value is an array of all the bundles
 *   this field belongs to.
 *
 * @see field_info_field()
 */
function field_info_field_by_id($field_id) {
  $cache = _field_info_field_cache();
  return $cache->getFieldById($field_id);
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
  $cache = _field_info_field_cache();
  return $cache->getFields();
}

/**
 * Retrieves information about field instances.
 *
 * Use of this function to retrieve instances across separate bundles (i.e.
 * when the $bundle parameter is NULL) should be avoided when possible, since
 * it loads and statically caches a potentially large array of information. Use
 * field_info_field_map() instead.
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
 *   type, keyed by bundle name. If $entity_type and $bundle_name are set, return
 *   all instances for that bundle.
 *
 * @see field_info_field_map()
 */
function field_info_instances($entity_type = NULL, $bundle_name = NULL) {
  $cache = _field_info_field_cache();

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
  $cache = _field_info_field_cache();
  $info = $cache->getBundleInstances($entity_type, $bundle_name);
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
 *   The context for which the list of pseudo-fields is requested. Either
 *   'form' or 'display'.
 *
 * @return
 *   The array of pseudo-field elements in the bundle.
 */
function field_info_extra_fields($entity_type, $bundle, $context) {
  $cache = _field_info_field_cache();
  $info = $cache->getBundleExtraFields($entity_type, $bundle);

  return isset($info[$context]) ? $info[$context] : array();
}

/**
 * Returns the maximum weight of all the components in an entity.
 *
 * This includes fields, 'extra_fields', and other components added by
 * third-party modules (e.g. field_group).
 *
 * @param $entity_type
 *   The type of entity; e.g. 'node' or 'user'.
 * @param $bundle
 *   The bundle name.
 * @param $context
 *   The context for which the maximum weight is requested. Either 'form', or
 *   the name of a view mode.
 * @return
 *   The maximum weight of the entity's components, or NULL if no components
 *   were found.
 */
function field_info_max_weight($entity_type, $bundle, $context) {
  $weights = array();

  // Collect weights for fields.
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    if ($context == 'form') {
      $weights[] = $instance['widget']['weight'];
    }
    elseif (isset($instance['display'][$context]['weight'])) {
      $weights[] = $instance['display'][$context]['weight'];
    }
  }
  // Collect weights for extra fields.
  foreach (field_info_extra_fields($entity_type, $bundle, $context) as $extra) {
    $weights[] = $extra['weight'];
  }

  // Let other modules feedback about their own additions.
  $weights = array_merge($weights, module_invoke_all('field_info_max_weight', $entity_type, $bundle, $context));
  $max_weight = $weights ? max($weights) : NULL;

  return $max_weight;
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
