<?php

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
 * Clears the field info cache without clearing the field data cache.
 *
 * This is useful when deleted fields or instances are purged.  We
 * need to remove the purged records, but no actual field data items
 * are affected.
 */
function field_info_cache_clear() {
  drupal_static_reset('field_view_mode_settings');

  // @todo: Remove this when field_attach_*_bundle() bundle management
  // functions are moved to the entity API.
  entity_info_cache_clear();

  _field_info_collate_types(TRUE);
  _field_info_collate_fields(TRUE);
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
 *     types, settings, and behaviors from hook_field_widget_info(), as well
 *     as module, giving the module that exposes the widget type.
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

      cache_set("field_info_types:$langcode", $info, 'cache_field');
    }
  }

  return $info;
}

/**
 * Collates all information on existing fields and instances.
 *
 * @param $reset
 *   If TRUE, clear the cache. The information will be rebuilt from the
 *   database next time it is needed. Defaults to FALSE.
 *
 * @return
 *   If $reset is TRUE, nothing.
 *   If $reset is FALSE, an array containing the following elements:
 *   - fields: Array of existing fields, keyed by field ID. This element
 *     lists deleted and non-deleted fields, but not inactive ones.
 *     Each field has an additional element, 'bundles', which is an array
 *     of all non-deleted instances of that field.
 *   - field_ids: Array of field IDs, keyed by field name. This element
 *     only lists non-deleted, active fields.
 *   - instances: Array of existing instances, keyed by entity type, bundle
 *     name and field name. This element only lists non-deleted instances
 *     whose field is active.
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
      $definitions = array(
        'field_ids' => field_read_fields(array(), array('include_deleted' => 1)),
        'instances' => field_read_instances(),
      );

      // Populate 'fields' with all fields, keyed by ID.
      $info['fields'] = array();
      foreach ($definitions['field_ids'] as $key => $field) {
        $info['fields'][$key] = $definitions['field_ids'][$key] = _field_info_prepare_field($field);
      }

      // Build an array of field IDs for non-deleted fields, keyed by name.
      $info['field_ids'] = array();
      foreach ($info['fields'] as $key => $field) {
        if (!$field['deleted']) {
          $info['field_ids'][$field['field_name']] = $key;
        }
      }

      // Populate 'instances'. Only non-deleted instances are considered.
      $info['instances'] = array();
      foreach (field_info_bundles() as $entity_type => $bundles) {
        foreach ($bundles as $bundle => $bundle_info) {
          $info['instances'][$entity_type][$bundle] = array();
        }
      }
      foreach ($definitions['instances'] as $instance) {
        $field = $info['fields'][$instance['field_id']];
        $instance = _field_info_prepare_instance($instance, $field);
        $info['instances'][$instance['entity_type']][$instance['bundle']][$instance['field_name']] = $instance;
        // Enrich field definitions with the list of bundles where they have
        // instances. NOTE: Deleted fields in $info['field_ids'] are not
        // enriched because all of their instances are deleted, too, and
        // are thus not in $definitions['instances'].
        $info['fields'][$instance['field_id']]['bundles'][$instance['entity_type']][] = $instance['bundle'];
      }

      // Populate 'extra_fields'.
      $extra = module_invoke_all('field_extra_fields');
      drupal_alter('field_extra_fields', $extra);
      // Merge in saved settings.
      foreach ($extra as $entity_type => $bundles) {
        foreach ($bundles as $bundle => $extra_fields) {
          $extra_fields = _field_info_prepare_extra_fields($extra_fields, $entity_type, $bundle);
          $info['extra_fields'][$entity_type][$bundle] = $extra_fields;
        }
      }

      cache_set('field_info_fields', $info, 'cache_field');
    }
  }

  return $info;
}

/**
 * Prepares a field definition for the current run-time context.
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

  // Add storage details.
  $details = (array) module_invoke($field['storage']['module'], 'field_storage_details', $field);
  drupal_alter('field_storage_details', $details, $field, $instance);
  $field['storage']['details'] = $details;

  // Initialize the 'bundles' list.
  $field['bundles'] = array();

  return $field;
}

/**
 * Prepares an instance definition for the current run-time context.
 *
 * Since the instance was last saved or updated, a number of things might have
 * changed: widgets or formatters disabled, new settings expected, new view
 * modes added...
 *
 * @param $instance
 *   The raw instance structure as read from the database.
 * @param $field
 *   The field structure for the instance.
 *
 * @return
 *   Field instance array.
 */
function _field_info_prepare_instance($instance, $field) {
  // Make sure all expected instance settings are present.
  $instance['settings'] += field_info_instance_settings($field['type']);

  // Set a default value for the instance.
  if (field_behaviors_widget('default value', $instance) == FIELD_BEHAVIOR_DEFAULT && !isset($instance['default_value'])) {
    $instance['default_value'] = NULL;
  }

  $instance['widget'] = _field_info_prepare_instance_widget($field, $instance['widget']);

  foreach ($instance['display'] as $view_mode => $display) {
    $instance['display'][$view_mode] = _field_info_prepare_instance_display($field, $display);
  }

  // Fallback to 'hidden' for view modes configured to use custom display
  // settings, and for which the instance has no explicit settings.
  $entity_info = entity_get_info($instance['entity_type']);
  $view_modes = array_merge(array('default'), array_keys($entity_info['view modes']));
  $view_mode_settings = field_view_mode_settings($instance['entity_type'], $instance['bundle']);
  foreach ($view_modes as $view_mode) {
    if ($view_mode == 'default' || !empty($view_mode_settings[$view_mode]['custom_settings'])) {
      if (!isset($instance['display'][$view_mode])) {
        $instance['display'][$view_mode] = array(
          'type' => 'hidden',
          'label' => 'above',
          'settings' => array(),
          'weight' => 0,
        );
      }
    }
  }

  return $instance;
}

/**
 * Adapts display specifications to the current run-time context.
 *
 * @param $field
 *   The field structure for the instance.
 * @param $display
 *   Display specifications as found in
 *   $instance['display']['some_view_mode'].
 */
function _field_info_prepare_instance_display($field, $display) {
  $field_type = field_info_field_types($field['type']);

  // Fill in default values.
  $display += array(
    'label' => 'above',
    'type' => $field_type['default_formatter'],
    'settings' => array(),
    'weight' => 0,
  );
  if ($display['type'] != 'hidden') {
    $formatter_type = field_info_formatter_types($display['type']);
    // Fallback to default formatter if formatter type is not available.
    if (!$formatter_type) {
      $display['type'] = $field_type['default_formatter'];
      $formatter_type = field_info_formatter_types($display['type']);
    }
    $display['module'] = $formatter_type['module'];
    // Fill in default settings for the formatter.
    $display['settings'] += field_info_formatter_settings($display['type']);
  }

  return $display;
}

/**
 * Prepares widget specifications for the current run-time context.
 *
 * @param $field
 *   The field structure for the instance.
 * @param $widget
 *   Widget specifications as found in $instance['widget'].
 */
function _field_info_prepare_instance_widget($field, $widget) {
  $field_type = field_info_field_types($field['type']);

  // Fill in default values.
  $widget += array(
    'type' => $field_type['default_widget'],
    'settings' => array(),
    'weight' => 0,
  );

  $widget_type = field_info_widget_types($widget['type']);
  // Fallback to default formatter if formatter type is not available.
  if (!$widget_type) {
    $widget['type'] = $field_type['default_widget'];
    $widget_type = field_info_widget_types($widget['type']);
  }
  $widget['module'] = $widget_type['module'];
  // Fill in default settings for the widget.
  $widget['settings'] += field_info_widget_settings($widget['type']);

  return $widget;
}

/**
 * Prepares 'extra fields' for the current run-time context.
 *
 * @param $extra_fields
 *   The array of extra fields, as collected in hook_field_extra_fields().
 * @param $entity_type
 *   The entity type.
 * @param $bundle
 *   The bundle name.
 */
function _field_info_prepare_extra_fields($extra_fields, $entity_type, $bundle) {
  $entity_type_info = entity_get_info($entity_type);
  $bundle_settings = field_bundle_settings($entity_type, $bundle);
  $extra_fields += array('form' => array(), 'display' => array());

  $result = array();
  // Extra fields in forms.
  foreach ($extra_fields['form'] as $name => $field_data) {
    $settings = isset($bundle_settings['extra_fields']['form'][$name]) ? $bundle_settings['extra_fields']['form'][$name] : array();
    if (isset($settings['weight'])) {
      $field_data['weight'] = $settings['weight'];
    }
    $result['form'][$name] = $field_data;
  }

  // Extra fields in displayed entities.
  $data = $extra_fields['display'];
  foreach ($extra_fields['display'] as $name => $field_data) {
    $settings = isset($bundle_settings['extra_fields']['display'][$name]) ? $bundle_settings['extra_fields']['display'][$name] : array();
    $view_modes = array_merge(array('default'), array_keys($entity_type_info['view modes']));
    foreach ($view_modes as $view_mode) {
      if (isset($settings[$view_mode])) {
        $field_data['display'][$view_mode] = $settings[$view_mode];
      }
      else {
        $field_data['display'][$view_mode] = array(
          'weight' => $field_data['weight'],
          'visible' => TRUE,
        );
      }
    }
    unset($field_data['weight']);
    $result['display'][$name] = $field_data;
  }

  return $result;
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
 * Returns all field definitions.
 *
 * @return
 *   An array of field definitions, keyed by field name. Each field has an
 *   additional property, 'bundles', which is an array of all the bundles to
 *   which this field belongs keyed by entity type.
 */
function field_info_fields() {
  $fields = array();
  $info = _field_info_collate_fields();
  foreach ($info['fields'] as $key => $field) {
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
 *   non-deleted, active field. Use field_read_fields() to retrieve information
 *   on deleted or inactive fields.
 *
 * @return
 *   The field array, as returned by field_read_fields(), with an
 *   additional element 'bundles', whose value is an array of all the bundles
 *   this field belongs to keyed by entity type.
 *
 * @see field_info_field_by_id()
 */
function field_info_field($field_name) {
  $info = _field_info_collate_fields();
  if (isset($info['field_ids'][$field_name])) {
    return $info['fields'][$info['field_ids'][$field_name]];
  }
}

/**
 * Returns data about an individual field, given a field ID.
 *
 * @param $field_id
 *   The id of the field to retrieve. $field_id can refer to a
 *   deleted field.
 *
 * @return
 *   The field array, as returned by field_read_fields(), with an
 *   additional element 'bundles', whose value is an array of all the bundles
 *   this field belongs to.
 *
 * @see field_info_field()
 */
function field_info_field_by_id($field_id) {
  $info = _field_info_collate_fields();
  if (isset($info['fields'][$field_id])) {
    return $info['fields'][$field_id];
  }
}

/**
 * Returns the same data as field_info_field_by_id() for every field.
 *
 * This function is typically used when handling all fields of some entities
 * to avoid thousands of calls to field_info_field_by_id().
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
  $info = _field_info_collate_fields();
  return $info['fields'];
}

/**
 * Retrieves information about field instances.
 *
 * @param $entity_type
 *   The entity type for which to return instances.
 * @param $bundle_name
 *   The bundle name for which to return instances.
 *
 * @return
 *   If $entity_type is not set, return all instances keyed by entity type and
 *   bundle name. If $entity_type is set, return all instances for that entity
 *   type, keyed by bundle name. If $entity_type and $bundle_name are set, return
 *   all instances for that bundle.
 */
function field_info_instances($entity_type = NULL, $bundle_name = NULL) {
  $info = _field_info_collate_fields();
  if (!isset($entity_type)) {
    return $info['instances'];
  }
  if (!isset($bundle_name)) {
    return $info['instances'][$entity_type];
  }
  if (isset($info['instances'][$entity_type][$bundle_name])) {
    return $info['instances'][$entity_type][$bundle_name];
  }
  return array();
}

/**
 * Returns an array of instance data for a specific field and bundle.
 *
 * @param $entity_type
 *   The entity type for the instance.
 * @param $field_name
 *   The field name for the instance.
 * @param $bundle_name
 *   The bundle name for the instance.
 */
function field_info_instance($entity_type, $field_name, $bundle_name) {
  $info = _field_info_collate_fields();
  if (isset($info['instances'][$entity_type][$bundle_name][$field_name])) {
    return $info['instances'][$entity_type][$bundle_name][$field_name];
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
  $info = _field_info_collate_fields();
  if (isset($info['extra_fields'][$entity_type][$bundle][$context])) {
    return $info['extra_fields'][$entity_type][$bundle][$context];
  }
  return array();
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
 * @} End of "defgroup field_info"
 */
