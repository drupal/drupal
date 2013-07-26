<?php

/**
 * @file
 * Field attach API, allowing entities (nodes, users, ...) to be 'fieldable'.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityNG;
use Drupal\entity\Plugin\Core\Entity\EntityDisplay;
use Drupal\entity\Plugin\Core\Entity\EntityFormDisplay;
use Drupal\Core\Language\Language;
use Drupal\Core\Entity\Field\PrepareCacheInterface;

/**
 * @defgroup field_storage Field Storage API
 * @{
 * Implements a storage engine for Field API data.
 *
 * The Field Attach API uses the Field Storage API to perform all "database
 * access". Each Field Storage API hook function defines a primitive database
 * operation such as read, write, or delete. The default field storage module,
 * field_sql_storage.module, uses the local SQL database to implement these
 * operations, but alternative field storage backends can choose to represent
 * the data in SQL differently or use a completely different storage mechanism
 * such as a cloud-based database.
 *
 * Each field defines which storage backend it uses. The Drupal configuration
 * 'field.settings.default_storage' identifies the storage backend used by
 * default.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Argument for an update operation.
 *
 * This is used in hook_field_storage_write when updating an existing entity.
 */
const FIELD_STORAGE_UPDATE = 'update';

/**
 * Argument for an insert operation.
 *
 * This is used in hook_field_storage_write when inserting a new entity.
 */
const FIELD_STORAGE_INSERT = 'insert';

/**
 * @} End of "defgroup field_storage".
 */

/**
 * @defgroup field_attach Field Attach API
 * @{
 * Operates on Field API data attached to Drupal entities.
 *
 * Field Attach API functions load, store, display, generate Field API
 * structures, and perform a variety of other functions for field data attached
 * to individual entities.
 *
 * Field Attach API functions generally take $entity_type and $entity arguments
 * along with additional function-specific arguments. $entity_type is the type
 * of the fieldable entity, such as 'node' or 'user', and $entity is the entity
 * itself.
 *
 * An entity plugin's annotation is how entity types define if and how
 * Field API should operate on their entity objects. Notably, the 'fieldable'
 * property needs to be set to TRUE.
 *
 * The Field Attach API uses the concept of bundles: the set of fields for a
 * given entity is defined on a per-bundle basis. The collection of bundles for
 * an entity type is added to the entity definition with
 * hook_entity_info_alter(). For instance, node_entity_info_alter() exposes
 * each node type as its own bundle. This means that the set of fields of a
 * node is determined by the node type.
 *
 * The Field API reads the bundle name for a given entity from a particular
 * property of the entity object, and hook_entity_info_alter() defines which
 * property to use. For instance, node_entity_info_alter() specifies:
 * @code
 *   $info['entity_keys']['bundle'] = 'type'
 * @endcode
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
 * run before the corresponding Field Storage API and Field Type API operations.
 * They allow modules to define additional storage locations (e.g.
 * denormalizing, mirroring) for field data on a per-field basis. They also
 * allow modules to take over field storage completely by instructing other
 * implementations of the same hook and the Field Storage API itself not to
 * operate on specified fields.
 *
 * The pre-operation hooks do not make the Field Storage API irrelevant. The
 * Field Storage API is essentially the "fallback mechanism" for any fields that
 * aren't being intercepted explicitly by pre-operation hooks.
 *
 * @link field_language Field language API @endlink provides information about
 * the structure of field objects.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Invokes a method on all the fields of a given entity.
 *
 * @param string $method
 *   The name of the method to invoke.
 * @param callable $target_function
 *   A function that receives an $instance object and returns the object on
 *   which the method should be invoked.
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The fully formed $entity_type entity.
 * @param mixed $a
 *   (optional) A parameter for the invoked method. Defaults to NULL.
 * @param mixed $b
 *   (optional) A parameter for the invoked method. Defaults to NULL.
 * @param array $options
 *   (optional) An associative array of additional options, with the following
 *   keys:
 *   - field_name: The name of the field whose operation should be invoked. By
 *     default, the operation is invoked on all the fields in the entity's
 *     bundle. NOTE: This option is not compatible with the 'deleted' option;
 *     the 'field_id' option should be used instead.
 *   - field_id: The ID of the field whose operation should be invoked. By
 *     default, the operation is invoked on all the fields in the entity's'
 *     bundles.
 *   - deleted: If TRUE, the function will operate on deleted fields as well
 *     as non-deleted fields. If unset or FALSE, only non-deleted fields are
 *     operated on.
 *   - langcode: A language code or an array of language codes keyed by field
 *     name. It will be used to narrow down to a single value the available
 *     languages to act on.
 *
 * @return array
 *   An array of returned values.
 */
function field_invoke_method($method, $target_function, EntityInterface $entity, &$a = NULL, &$b = NULL, array $options = array()) {
  // Merge default options.
  $default_options = array(
    'deleted' => FALSE,
    'langcode' => NULL,
  );
  $options += $default_options;

  $entity_type = $entity->entityType();
  // Determine the list of instances to iterate on.
  $instances = _field_invoke_get_instances($entity_type, $entity->bundle(), $options);

  // Iterate through the instances and collect results.
  $return = array();
  foreach ($instances as $instance) {

    // Let the function determine the target object on which the method should be
    // called.
    $target = call_user_func($target_function, $instance);

    if (method_exists($target, $method)) {
      $field = field_info_field_by_id($instance['field_id']);
      $field_name = $field['field_name'];

      // Determine the list of languages to iterate on.
      $available_langcodes = field_available_languages($entity_type, $field);
      $langcodes = _field_language_suggestion($available_langcodes, $options['langcode'], $field_name);

      foreach ($langcodes as $langcode) {
        $items = $entity->getTranslation($langcode)->get($field_name);
        $items->filterEmptyValues();

        $result = $target->$method($entity, $langcode, $items, $a, $b);

        if (isset($result)) {
          // For methods with array results, we merge results together.
          // For methods with scalar results, we collect results in an array.
          if (is_array($result)) {
            $return = array_merge($return, $result);
          }
          else {
            $return[] = $result;
          }
        }
      }
    }
  }

  return $return;
}

/**
 * Invokes a method across fields on multiple entities.
 *
 * @param string $method
 *   The name of the method to invoke.
 * @param callable $target_function
 *   A function that receives an $instance object and returns the object on
 *   which the method should be invoked.
 * @param array $entities
 *   An array of entities, keyed by entity ID.
 * @param mixed $a
 *   (optional) A parameter for the invoked method. Defaults to NULL.
 * @param mixed $b
 *   (optional) A parameter for the invoked method. Defaults to NULL.
 * @param $options
 *   (optional) An associative array of additional options, with the following
 *   keys:
 *   - field_name: The name of the field whose operation should be invoked. By
 *     default, the operation is invoked on all the fields in the entity's
 *     bundle. NOTE: This option is not compatible with the 'deleted' option;
 *     the 'field_id' option should be used instead.
 *   - field_id: The ID of the field whose operation should be invoked. By
 *     default, the operation is invoked on all the fields in the entity's'
 *     bundles.
 *   - deleted: If TRUE, the function will operate on deleted fields as well
 *     as non-deleted fields. If unset or FALSE, only non-deleted fields are
 *     operated on.
 *   - langcode: A language code or an array of language codes keyed by field
 *     name. It will be used to narrow down to a single value the available
 *     languages to act on.
 *
 * @return array
 *   An array of returned values keyed by entity ID.
 *
 * @see field_invoke_method()
 */
function field_invoke_method_multiple($method, $target_function, array $entities, &$a = NULL, &$b = NULL, array $options = array()) {
  // Merge default options.
  $default_options = array(
    'deleted' => FALSE,
    'langcode' => NULL,
  );
  $options += $default_options;

  $instances = array();
  $grouped_entities = array();
  $grouped_items = array();
  $grouped_targets = array();
  $return = array();

  // Go through the entities and collect the instances on which the method
  // should be called.
  foreach ($entities as $entity) {
    $id = $entity->id();
    $entity_type = $entity->entityType();

    // Determine the list of instances to iterate on.
    $entity_instances = _field_invoke_get_instances($entity_type, $entity->bundle(), $options);

    foreach ($entity_instances as $instance) {
      $instance_id = $instance['id'];
      $field_name = $instance['field_name'];

      // Let the closure determine the target object on which the method should
      // be called.
      if (empty($grouped_targets[$instance_id])) {
        $grouped_targets[$instance_id] = call_user_func($target_function, $instance);
      }

      if (method_exists($grouped_targets[$instance_id], $method)) {
        // Add the instance to the list of instances to invoke the hook on.
        if (!isset($instances[$instance_id])) {
          $instances[$instance_id] = $instance;
        }

        // Unless a language code suggestion is provided we iterate on all the
        // available language codes.
        $field = field_info_field_by_id($instance['field_id']);
        $available_langcodes = field_available_languages($entity_type, $field);
        $langcode = !empty($options['langcode'][$id]) ? $options['langcode'][$id] : $options['langcode'];
        $langcodes = _field_language_suggestion($available_langcodes, $langcode, $field_name);
        foreach ($langcodes as $langcode) {
          // Group the entities and items corresponding to the current field.
          $grouped_entities[$instance_id][$langcode][$id] = $entities[$id];
          $items = $entity->getTranslation($langcode)->get($field_name);
          $items->filterEmptyValues();
          $grouped_items[$instance_id][$langcode][$id] = $items;
        }
      }
    }
    // Initialize the return value for each entity.
    $return[$id] = array();
  }

  // For each instance, invoke the method and collect results.
  foreach ($instances as $instance_id => $instance) {
    $field_name = $instance['field_name'];

    // Iterate over all the field translations.
    foreach ($grouped_items[$instance_id] as $langcode => $items) {
      $entities = $grouped_entities[$instance_id][$langcode];
      $results = $grouped_targets[$instance_id]->$method($entities, $langcode, $items, $a, $b);

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
  }

  return $return;
}

/**
 * Retrieves a list of instances to operate on.
 *
 * Helper for field_invoke_method().
 *
 * @param $entity_type
 *   The entity type.
 * @param $bundle
 *   The bundle name.
 * @param $options
 *   An associative array of options, as provided to field_invoke_method(). Only
 *   the following keys are considered:
 *   - deleted
 *   - field_name
 *   - field_id
 *   See field_invoke_method() for details.
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
 * Defines a 'target function' for field_invoke_method().
 *
 * Used to invoke methods on an instance's widget.
 *
 * @param \Drupal\entity\Plugin\Core\Entity\EntityFormDisplay $form_display
 *   An EntityFormDisplay object.
 *
 * @return callable $target_function
 *   A 'target function' for field_invoke_method().
 */
function _field_invoke_widget_target($form_display) {
  return function ($instance) use ($form_display) {
    return $form_display->getRenderer($instance['field_name']);
  };
}

/**
 * Adds form elements for all fields for an entity to a form structure.
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
 * field_attach_extract_form_values() on the same $form element.
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
 * @param \Drupal\Core\Entity\EntityInterface $entity
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
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 *
 * @see field_form_get_state()
 * @see field_form_set_state()
 */
function field_attach_form(EntityInterface $entity, &$form, &$form_state, $langcode = NULL, array $options = array()) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();

  // Set #parents to 'top-level' by default.
  $form += array('#parents' => array());

  // Get the entity_form_display object for this form.
  $form_display = $form_state['form_display'];

  // If no language is provided use the default site language.
  $options['langcode'] = field_valid_language($langcode);
  $form += (array) field_invoke_method('form', _field_invoke_widget_target($form_display), $entity, $form, $form_state, $options);

  $form['#entity_type'] = $entity->entityType();
  $form['#bundle'] = $entity->bundle();

  // Let other modules make changes to the form.
  // Avoid module_invoke_all() to let parameters be taken by reference.
  foreach (module_implements('field_attach_form') as $module) {
    $function = $module . '_field_attach_form';
    $function($entity, $form, $form_state, $langcode);
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
 *   The type of entities in $entities; e.g., 'node' or 'user'.
 * @param $entities
 *   An array of entities for which to load fields, keyed by entity ID. Each
 *   entity needs to have its 'bundle', 'id' and (if applicable) 'revision' keys
 *   filled in. The function adds the loaded field data directly in the entity
 *   objects of the $entities array.
 * @param $age
 *   FIELD_LOAD_CURRENT to load the most recent revision for all fields, or
 *   FIELD_LOAD_REVISION to load the version indicated by each entity. Defaults
 *   to FIELD_LOAD_CURRENT; use field_attach_load_revision() instead of passing
 *   FIELD_LOAD_REVISION.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - instance: A field instance entity, If provided, only values for the
 *     corresponding field will be loaded, and no cache is written. This
 *     option is only supported when all $entities are within the same bundle.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_load($entity_type, $entities, $age = FIELD_LOAD_CURRENT, $options = array()) {
  $load_current = $age == FIELD_LOAD_CURRENT;
  $load_deleted = !empty($options['instance']->deleted);

  // Merge default options.
  $options += array('instance' => NULL);
  // Set options for hook invocations.
  $hook_options = array(
    'deleted' => $load_deleted,
  );
  if ($options['instance']) {
    $hook_options['field_id'] = $options['instance']->field_uuid;
  }

  $info = entity_get_info($entity_type);
  // Only the most current revision of non-deleted fields for cacheable entity
  // types can be cached.
  $cache_read = $load_current && $info['field_cache'] && !$load_deleted;
  // In addition, do not write to the cache when loading a single field.
  $cache_write = $cache_read && !isset($options['instance']);

  if (empty($entities)) {
    return;
  }

  // Ensure we are working with a BC mode entity.
  foreach ($entities as $id => $entity) {
    $entities[$id] = $entity->getBCEntity();
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
    $cache = cache('field')->getMultiple($cids);
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
    // - Field class's prepareCache() method.
    // - hook_field_attach_load()

    // Invoke hook_field_storage_pre_load(): let any module load field
    // data before the storage engine, accumulating along the way.
    $skip_fields = array();
    foreach (module_implements('field_storage_pre_load') as $module) {
      $function = $module . '_field_storage_pre_load';
      $function($entity_type, $queried_entities, $age, $skip_fields, $hook_options);
    }

    // Collect the storage backends used by the remaining fields in the entities.
    $storages = array();
    foreach ($queried_entities as $entity) {
      $id = $entity->id();
      $vid = $entity->getRevisionId();

      // Determine the list of field instances to work on.
      if ($options['instance']) {
        $instances = array($options['instance']);
      }
      else {
        $instances = field_info_instances($entity_type, $entity->bundle());
      }

      foreach ($instances as $instance) {
        $field = $instance->getField();
        $field_name = $field->id();
        if (!isset($queried_entities[$id]->{$field_name})) {
          $queried_entities[$id]->{$field_name} = array();
        }
        if (!isset($skip_fields[$field->uuid])) {
          $storages[$field->storage['type']][$field->uuid][] = $load_current ? $id : $vid;
        }
      }
    }

    // Invoke hook_field_storage_load() on the relevant storage backends.
    foreach ($storages as $storage => $fields) {
      $storage_info = field_info_storage_types($storage);
      module_invoke($storage_info['module'], 'field_storage_load', $entity_type, $queried_entities, $age, $fields, $hook_options);
    }

    // Invoke the field type's prepareCache() method.
    if (empty($options['instance'])) {
      foreach ($queried_entities as $entity) {
        \Drupal::entityManager()
          ->getStorageController($entity_type)
          ->invokeFieldItemPrepareCache($entity);
      }
    }
    else {
      // Do not rely on invokeFieldItemPrepareCache(), which only works on
      // fields listed in getFieldDefinitions(), and will fail if we are loading
      // values for a deleted field. Instead, generate FieldItem objects
      // directly, and call their prepareCache() method.
      foreach ($queried_entities as $entity) {
        $field = $options['instance']->getField();
        $field_name = $field->id();
        // Call the prepareCache() method on each item.
        foreach ($entity->{$field_name} as $langcode => $values) {
          $definition = _field_generate_entity_field_definition($field, $options['instance']);
          $items = \Drupal::typedData()->create($definition, $values, $field_name, $entity);
          foreach ($items as $item) {
            if ($item instanceof PrepareCacheInterface) {
              $item->prepareCache();
            }
          }
          $entity->{$field_name}[$langcode] = $items->getValue();
        }
      }
    }

    // Invoke hook_field_attach_load(): let other modules act on loading the
    // entity.
    module_invoke_all('field_attach_load', $entity_type, $queried_entities, $age, $options);

    // Build cache data.
    if ($cache_write) {
      foreach ($queried_entities as $id => $entity) {
        $data = array();
        $instances = field_info_instances($entity_type, $entity->bundle());
        foreach ($instances as $instance) {
          $data[$instance['field_name']] = $queried_entities[$id]->{$instance['field_name']};
        }
        $cid = "field:$entity_type:$id";
        cache('field')->set($cid, $data);
      }
    }
  }
}

/**
 * Loads all fields for previous versions of a group of entities.
 *
 * Loading different versions of the same entities is not supported, and should
 * be done by separate calls to the function.
 *
 * field_attach_load_revision() is automatically called by the default entity
 * controller class, and thus, in most cases, doesn't need to be explicitly
 * called by the entity type module.
 *
 * @param $entity_type
 *   The type of entities in $entities; e.g. 'node' or 'user'.
 * @param $entities
 *   An array of entities for which to load fields, keyed by entity ID. Each
 *   entity needs to have its 'bundle', 'id' and (if applicable) 'revision' keys
 *   filled. The function adds the loaded field data directly in the entity
 *   objects of the $entities array.
 * @param $options
 *   An associative array of additional options. See field_attach_load() for
 *   details.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_load_revision($entity_type, $entities, $options = array()) {
  return field_attach_load($entity_type, $entities, FIELD_LOAD_REVISION, $options);
}

/**
 * Performs field validation against form-submitted field values.
 *
 * There are two levels of validation for fields in forms: widget validation and
 * and field validation.
 * - Widget validation steps are specific to a given widget's own form structure
 *   and UI metaphors. They are executed through FAPI's #element_validate
 *   property during normal form validation.
 * - Field validation steps are common to a given field type, independently of
 *   the specific widget being used in a given form. They are defined in the
 *   field type's implementation of hook_field_validate().
 *
 * This function performs field validation in the context of a form submission.
 * It converts field validation errors into form errors on the correct form
 * elements. Fieldable entity types should call this function during their own
 * form validation function.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being submitted. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure where field elements are attached to. This might be a
 *   full form structure, or a sub-element of a larger form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_form_validate(EntityInterface $entity, $form, &$form_state, array $options = array()) {
  // Only support NG entities.
  if (!($entity->getNGEntity() instanceof EntityNG)) {
    return;
  }

  $has_violations = FALSE;
  foreach ($entity as $field_name => $field) {
    $definition = $field->getDefinition();
    if (!empty($definition['configurable']) && (empty($options['field_name']) || $options['field_name'] == $field_name)) {
      $field_violations = $field->validate();
      if (count($field_violations)) {
        $has_violations = TRUE;

        // Place violations in $form_state.
        $langcode = field_is_translatable($entity->entityType(), field_info_field($field_name)) ? $entity->language()->id : Language::LANGCODE_NOT_SPECIFIED;
        $field_state = field_form_get_state($form['#parents'], $field_name, $langcode, $form_state);
        $field_state['constraint_violations'] = $field_violations;
        field_form_set_state($form['#parents'], $field_name, $langcode, $form_state, $field_state);
      }
    }
  }

  if ($has_violations) {
    // Map errors back to form elements.
    field_invoke_method('flagErrors', _field_invoke_widget_target($form_state['form_display']), $entity, $form, $form_state, $options);
  }
}

/**
 * Populates an entity object with values from a form submission.
 *
 * Currently, this accounts for drag-and-drop reordering of field values, and
 * filtering of empty values.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being submitted. The actual field values will be read
 *   from $form_state['values'].
 * @param $form
 *   The form structure where field elements are attached to. This might be a
 *   full form structure, or a sub-element of a larger form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_extract_form_values(EntityInterface $entity, $form, &$form_state, array $options = array()) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();

  // Extract field values from submitted values.
  $form_display = $form_state['form_display'];
  field_invoke_method('extractFormValues', _field_invoke_widget_target($form_display), $entity, $form, $form_state, $options);

  // Let other modules act on submitting the entity.
  // Avoid module_invoke_all() to let $form_state be taken by reference.
  foreach (module_implements('field_attach_extract_form_values') as $module) {
    $function = $module . 'field_attach_extract_form_values';
    $function($entity, $form, $form_state);
  }
}

/**
 * Save field data for a new entity.
 *
 * The passed-in entity must already contain its id and (if applicable)
 * revision id attributes.
 * Default values (if any) will be saved for fields not present in the
 * $entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to save.
 * @return
 *   Default values (if any) will be added to the $entity parameter for fields
 *   it leaves unspecified.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_insert(EntityInterface $entity) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();

  // Let any module insert field data before the storage engine, accumulating
  // saved fields along the way.
  $skip_fields = array();
  foreach (module_implements('field_storage_pre_insert') as $module) {
    $function = $module . '_field_storage_pre_insert';
    $function($entity, $skip_fields);
  }

  // Collect the storage backends used by the remaining fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['uuid'];
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
    module_invoke($storage_info['module'], 'field_storage_write', $entity, FIELD_STORAGE_INSERT, $fields);
  }
}

/**
 * Saves field data for an existing entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to save.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_update(EntityInterface $entity) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();

  // Let any module update field data before the storage engine, accumulating
  // saved fields along the way.
  $skip_fields = array();
  foreach (module_implements('field_storage_pre_update') as $module) {
    $function = $module . '_field_storage_pre_update';
    $function($entity, $skip_fields);
  }

  // Collect the storage backends used by the remaining fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['uuid'];
    // Collect the storage backend if the field has not been written yet.
    if (!isset($skip_fields[$field_id])) {
      $storages[$field['storage']['type']][$field_id] = $field_id;
    }
  }

  // Field storage backends save any remaining unsaved fields.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_write', $entity, FIELD_STORAGE_UPDATE, $fields);
  }

  $entity_info = $entity->entityInfo();
  if ($entity_info['field_cache']) {
    cache('field')->delete('field:' . $entity->entityType() . ':' . $entity->id());
  }
}

/**
 * Deletes field data for an existing entity. This deletes all revisions of
 * field data for the entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity whose field data to delete.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_delete(EntityInterface $entity) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();

  // Collect the storage backends used by the fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['uuid'];
    $storages[$field['storage']['type']][$field_id] = $field_id;
  }

  // Field storage backends delete their data.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_delete', $entity, $fields);
  }

  $entity_info = $entity->entityInfo();
  if ($entity_info['field_cache']) {
    cache('field')->delete('field:' . $entity->entityType() . ':' . $entity->id());
  }
}

/**
 * Delete field data for a single revision of an existing entity. The passed
 * entity must have a revision ID attribute.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to save.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_delete_revision(EntityInterface $entity) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();

  // Collect the storage backends used by the fields in the entities.
  $storages = array();
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    $field_id = $field['uuid'];
    $storages[$field['storage']['type']][$field_id] = $field_id;
  }

  // Field storage backends delete their data.
  foreach ($storages as $storage => $fields) {
    $storage_info = field_info_storage_types($storage);
    module_invoke($storage_info['module'], 'field_storage_delete_revision', $entity, $fields);
  }
}

/**
 * Prepares field data prior to display.
 *
 * This function lets field types and formatters load additional data needed for
 * display that is not automatically loaded during field_attach_load(). It
 * accepts an array of entities to allow query optimization when displaying
 * lists of entities.
 *
 * field_attach_prepare_view() and field_attach_view() are two halves of the
 * same operation. It is safe to call field_attach_prepare_view() multiple times
 * on the same entity before calling field_attach_view() on it, but calling any
 * Field API operation on an entity between passing that entity to these two
 * functions may yield incorrect results.
 *
 * @param $entity_type
 *   The type of entities in $entities; e.g. 'node' or 'user'.
 * @param array $entities
 *   An array of entities, keyed by entity ID.
 * @param array $displays
 *   An array of entity display objects, keyed by bundle name.
 * @param $langcode
 *   (Optional) The language the field values are to be shown in. If no language
 *   is provided the current language is used.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_prepare_view($entity_type, array $entities, array $displays, $langcode = NULL) {
  $options['langcode'] = array();

  // To ensure hooks are only run once per entity, only process items without
  // the _field_view_prepared flag.
  // @todo: resolve this more generally for both entity and field level hooks.
  $prepare = array();
  foreach ($entities as $id => $entity) {
    if (empty($entity->_field_view_prepared)) {
      // Ensure we are working with a BC mode entity.
      $entity = $entity->getBCEntity();

      // Add this entity to the items to be prepared.
      $prepare[$id] = $entity;

      // Determine the actual language code to display for each field, given the
      // language codes available in the field data.
      $options['langcode'][$id] = field_language($entity, NULL, $langcode);

      // Mark this item as prepared.
      $entity->_field_view_prepared = TRUE;
    }
  }

  // Then let the formatters do their own specific massaging. For each
  // instance, call the prepareView() method on the formatter object handed by
  // the entity display.
  $target_function = function ($instance) use ($displays) {
    if (isset($displays[$instance['bundle']])) {
      return $displays[$instance['bundle']]->getRenderer($instance['field_name']);
    }
  };
  $null = NULL;
  field_invoke_method_multiple('prepareView', $target_function, $prepare, $null, $null);
}

/**
 * Returns a renderable array for the fields on an entity.
 *
 * Each field is displayed according to the display options specified in the
 * $display parameter for the given view mode.
 *
 * field_attach_prepare_view() and field_attach_view() are two halves of the
 * same operation. It is safe to call field_attach_prepare_view() multiple times
 * on the same entity before calling field_attach_view() on it, but calling any
 * Field API operation on an entity between passing that entity to these two
 * functions may yield incorrect results.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to render.
 * @param \Drupal\entity\Plugin\Core\Entity\EntityDisplay $display
 *   The entity display object.
 * @param $langcode
 *   The language the field values are to be shown in. If no language is
 *   provided the current language is used.
 * @param array $options
 *   An associative array of additional options. See field_invoke_method() for
 *   details. Note: key "langcode" will be overridden inside this function.
 *
 * @return array
 *   A renderable array for the field values.
 *
 * @deprecated as of Drupal 8.0. Use the entity system instead.
 */
function field_attach_view(EntityInterface $entity, EntityDisplay $display, $langcode = NULL, array $options = array()) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();
  // Determine the actual language code to display for each field, given the
  // language codes available in the field data.
  $options['langcode'] = field_language($entity, NULL, $langcode);

  // For each instance, call the view() method on the formatter object handed
  // by the entity display.
  $target_function = function ($instance) use ($display) {
    return $display->getRenderer($instance['field_name']);
  };
  $null = NULL;
  $output = field_invoke_method('view', $target_function, $entity, $null, $null, $options);
  // Remove the BC layer now.
  $entity = $entity->getNGEntity();

  // Let other modules alter the renderable array.
  $view_mode = $display->originalMode;
  $context = array(
    'entity' => $entity,
    'view_mode' => $view_mode,
    'display_options' => $view_mode,
    'langcode' => $langcode,
  );
  drupal_alter('field_attach_view', $output, $context);

  // Reset the _field_view_prepared flag set in field_attach_prepare_view(),
  // in case the same entity is displayed with different settings later in
  // the request.
  unset($entity->_field_view_prepared);

  return $output;
}

/**
 * Populates the template variables with the available field values.
 *
 * The $variables array will be populated with all the field instance values
 * associated with the given entity type, keyed by field name; in case of
 * translatable fields the language currently chosen for display will be
 * selected.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity with fields to render.
 * @param $element
 *   The structured array containing the values ready for rendering.
 * @param $variables
 *   The variables array is passed by reference and will be populated with field
 *   values.
 */
function field_attach_preprocess(EntityInterface $entity, $element, &$variables) {
  // Ensure we are working with a BC mode entity.
  $entity = $entity->getBCEntity();
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    $field_name = $instance['field_name'];
    if (isset($element[$field_name]['#language'])) {
      $langcode = $element[$field_name]['#language'];
      $variables[$field_name] = isset($entity->{$field_name}[$langcode]) ? $entity->{$field_name}[$langcode] : NULL;
    }
  }

  // Let other modules make changes to the $variables array.
  $context = array(
    'entity' => $entity,
    'element' => $element,
  );
  drupal_alter('field_attach_preprocess', $variables, $context);
}

/**
 * Implements hook_entity_bundle_create().
 */
function field_entity_bundle_create($entity_type, $bundle) {
  // Clear the cache.
  field_cache_clear();
}

/**
 * Implements hook_entity_bundle_rename().
 */
function field_entity_bundle_rename($entity_type, $bundle_old, $bundle_new) {
  $instances = field_read_instances();
  foreach ($instances as $instance) {
    if ($instance->entity_type == $entity_type && $instance->bundle == $bundle_old) {
      $id_new = $instance['entity_type'] . '.' . $bundle_new . '.' . $instance['field_name'];
      $instance->id = $id_new;
      $instance->bundle = $bundle_new;
      $instance->allowBundleRename();
      $instance->save();
    }
  }

  // Clear the field cache.
  field_cache_clear();

  // Update bundle settings.
  $settings = variable_get('field_bundle_settings_' . $entity_type . '__' . $bundle_old, array());
  variable_set('field_bundle_settings_' . $entity_type . '__' . $bundle_new, $settings);
  variable_del('field_bundle_settings_' . $entity_type . '__' . $bundle_old);
}

/**
 * Implements hook_entity_bundle_delete().
 *
 * This deletes the data for the field instances as well as the field instances
 * themselves. This function actually just marks the data and field instances as
 * deleted, leaving the garbage collection for a separate process, because it is
 * not always possible to delete this much data in a single page request
 * (particularly since for some field types, the deletion is more than just a
 * simple DELETE query).
 */
function field_entity_bundle_delete($entity_type, $bundle) {
  // Get the instances on the bundle. field_read_instances() must be used
  // here since field_info_instances() does not return instances for disabled
  // entity types or bundles.
  $instances = field_read_instances(array('entity_type' => $entity_type, 'bundle' => $bundle), array('include_inactive' => TRUE));
  foreach ($instances as $instance) {
    $instance->delete();
  }

  // Clear the cache.
  field_cache_clear();

  // Clear bundle display settings.
  variable_del('field_bundle_settings_' . $entity_type . '__' . $bundle);
}

/**
 * @} End of "defgroup field_attach".
 */
