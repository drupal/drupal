<?php

/**
 * @file
 * Field CRUD API, handling field and field instance creation and deletion.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Core\Entity\Field;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\field\FieldException;

/**
 * @defgroup field_crud Field CRUD API
 * @{
 * Creates, updates, and deletes Field API fields, bundles, and instances.
 *
 * Modules use this API, often in hook_install(), to create custom data
 * structures. UI modules will use it to create a user interface.
 *
 * The Field CRUD API uses @link field Field API data structures @endlink.
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Creates a field.
 *
 * This function does not bind the field to any bundle; use
 * field_create_instance() for that.
 *
 * @param array $field
 *   A field definition. The field_name and type properties are required.
 *   Other properties, if omitted, will be given the following default values:
 *   - cardinality: 1
 *   - locked: FALSE
 *   - indexes: the field-type indexes, specified by the field type's
 *     hook_field_schema(). The indexes specified in $field are added
 *     to those default indexes. It is possible to override the
 *     definition of a field-type index by providing an index with the
 *     same name, or to remove it by redefining it as an empty array
 *     of columns. Overriding field-type indexes should be done
 *     carefully, for it might seriously affect the site's performance.
 *   - settings: each omitted setting is given the default value defined in
 *     hook_field_info().
 *   - storage:
 *     - type: the storage backend specified in the 'field_storage_default'
 *       system variable.
 *     - settings: each omitted setting is given the default value specified in
 *       hook_field_storage_info().
 *
 * @return \Drupal\field\Plugin\Core\Entity\Field
 *   The field entity.
 *
 * @throws Drupal\field\FieldException
 *
 * See: @link field Field API data structures @endlink.
 */
function field_create_field(array $field) {
  $field = entity_create('field_entity', $field);
  $field->save();
  return $field;
}

/**
 * Updates a field.
 *
 * Any module may forbid any update for any reason. For example, the
 * field's storage module might forbid an update if it would change
 * the storage schema while data for the field exists. A field type
 * module might forbid an update if it would change existing data's
 * semantics, or if there are external dependencies on field settings
 * that cannot be updated.
 *
 * @param mixed $field
 *   Either the \Drupal\field\Plugin\Core\Entity\Field object to update, or a
 *   field array structure. If the latter, $field['field_name'] must provided;
 *   it identifies the field that will be updated to match this structure. Any
 *   other properties of the field that are not specified in $field will be left
 *   unchanged, so it is not necessary to pass in a fully populated $field
 *   structure.
 *
 * @throws Drupal\field\FieldException
 *
 * @see field_create_field()
 */
function field_update_field($field) {
  // Module developers can still pass in an array of properties.
  if (is_array($field)) {
    $field_loaded = entity_load('field_entity', $field['field_name']);
    if (empty($field_loaded)) {
      throw new FieldException('Attempt to update a non-existent field.');
    }
    // Merge incoming values.
    foreach ($field as $key => $value) {
      $field_loaded[$key] = $value;
    }
    $field = $field_loaded;
  }

  $field->save();
}

/**
 * Reads a single field record directly from the database.
 *
 * Generally, you should use the field_info_field() instead.
 *
 * This function will not return deleted fields. Use field_read_fields() instead
 * for this purpose.
 *
 * @param $field_name
 *   The field name to read.
 * @param array $include_additional
 *   The default behavior of this function is to not return a field that is
 *   inactive. Setting $include_additional['include_inactive'] to TRUE will
 *   override this behavior.
 *
 * @return
 *   A field definition array, or FALSE.
 */
function field_read_field($field_name, $include_additional = array()) {
  $fields = field_read_fields(array('field_name' => $field_name), $include_additional);
  return $fields ? current($fields) : FALSE;
}

/**
 * Reads in fields that match an array of conditions.
 *
 * @param array $conditions
 *   An array of conditions to match against. Keys are names of properties found
 *   in field configuration files, and values are conditions to match.
 * @param array $include_additional
 *   The default behavior of this function is to not return fields that are
 *   inactive or have been deleted. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this behavior.
 *
 * @return
 *   An array of fields matching $params. If
 *   $include_additional['include_deleted'] is TRUE, the array is keyed by
 *   field ID, otherwise it is keyed by field name.
 */
function field_read_fields($conditions = array(), $include_additional = array()) {
  // Include inactive fields if specified in the $include_additional parameter.
  $include_inactive = isset($include_additional['include_inactive']) && $include_additional['include_inactive'];
  // Include deleted fields if specified either in the $include_additional or
  // the $conditions parameters.
  $include_deleted = (isset($include_additional['include_deleted']) && $include_additional['include_deleted']) || (isset($conditions['deleted']) && $conditions['deleted']);

  // Get fields stored in configuration.
  if (isset($conditions['field_name'])) {
    // Optimize for the most frequent case where we do have a specific ID.
    $fields = entity_load_multiple('field_entity', array($conditions['field_name']));
  }
  else {
    // No specific ID, we need to examine all existing fields.
    $fields = entity_load_multiple('field_entity');
  }

  // Merge deleted fields (stored in state) if needed.
  if ($include_deleted) {
    $deleted_fields = Drupal::state()->get('field.field.deleted') ?: array();
    foreach ($deleted_fields as $id => $config) {
      $fields[$id] = entity_create('field_entity', $config);
    }
  }

  // Translate "do not include inactive instances" into actual conditions.
  if (!$include_inactive) {
    $conditions['active'] = TRUE;
    $conditions['storage.active'] = TRUE;
  }

  // Collect matching fields.
  $matching_fields = array();
  foreach ($fields as $field) {
    foreach ($conditions as $key => $value) {
      // Extract the actual value against which the condition is checked.
      switch ($key) {
        case 'storage.active':
          $checked_value = $field->storage['active'];
          break;

        case 'field_name';
          $checked_value = $field->id;
          break;

        default:
          $checked_value = $field->$key;
          break;
      }

      // Skip to the next field as soon as one condition does not match.
      if ($checked_value != $value) {
        continue 2;
      }
    }

    module_invoke_all('field_read_field', $field);

    // When returning deleted fields, key the results by UUID since they can
    // include several fields with the same ID.
    $key = $include_deleted ? $field->uuid : $field->id;
    $matching_fields[$key] = $field;
  }

  return $matching_fields;
}

/**
 * Marks a field and its instances and data for deletion.
 *
 * @param $field_name
 *   The field name to delete.
 */
function field_delete_field($field_name) {
  if ($field = field_info_field($field_name)) {
    $field->delete();
  }
}

/**
 * Creates an instance of a field, binding it to a bundle.
 *
 * @param array $instance
 *   A field instance definition array. The field_name, entity_type and
 *   bundle properties are required. Other properties, if omitted,
 *   will be given the following default values:
 *   - label: the field name
 *   - description: empty string
 *   - required: FALSE
 *   - default_value_function: empty string
 *   - settings: each omitted setting is given the default value specified in
 *     hook_field_info().
 *   - widget:
 *     - type: the default widget specified in hook_field_info().
 *     - settings: each omitted setting is given the default value specified in
 *       hook_field_widget_info().
 *
 * @return \Drupal\field\Plugin\Core\Entity\FieldInstance
 *   The field instance entity.
 *
 * @throws Drupal\field\FieldException
 *
 * See: @link field Field API data structures @endlink.
 */
function field_create_instance(array $instance) {
  $instance = entity_create('field_instance', $instance);
  $instance->save();
  return $instance;
}

/**
 * Updates an instance of a field.
 *
 * @param mixed $instance
 *   Either the \Drupal\field\Plugin\Core\Entity\FieldInstance to update, or an
 *   associative array representing an instance structure. If the latter, the
 *   required keys and values are:
 *   - entity_type: The type of the entity the field is attached to.
 *   - bundle: The bundle this field belongs to.
 *   - field_name: The name of an existing field.
 *   Read-only ID properties are assigned automatically. Any other properties
 *   properties specified in $instance overwrite the existing values for
 *   the instance.
 *
 * @throws Drupal\field\FieldException
 *
 * @see field_create_instance()
 */
function field_update_instance($instance) {
  // Module developers can still pass in an array of properties.
  if (is_array($instance)) {
    $instance_loaded = entity_load('field_instance', $instance['entity_type'] . '.' . $instance['bundle'] . '.' . $instance['field_name']);
    if (empty($instance_loaded)) {
      throw new FieldException('Attempt to update a non-existent instance.');
    }
    // Merge incoming values.
    foreach ($instance as $key => $value) {
      $instance_loaded[$key] = $value;
    }
    $instance = $instance_loaded;
  }

  $instance->save();
}

/**
 * Reads a single instance record from the database.
 *
 * Generally, you should use field_info_instance() instead, as it provides
 * caching and allows other modules the opportunity to append additional
 * formatters, widgets, and other information.
 *
 * @param $entity_type
 *   The type of entity to which the field is bound.
 * @param $field_name
 *   The field name to read.
 * @param $bundle
 *   The bundle to which the field is bound.
 * @param array $include_additional
 *   The default behavior of this function is to not return an instance that has
 *   been deleted, or whose field is inactive. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this behavior.
 *
 * @return
 *   An instance structure, or FALSE.
 */
function field_read_instance($entity_type, $field_name, $bundle, $include_additional = array()) {
  $instances = field_read_instances(array('entity_type' => $entity_type, 'field_name' => $field_name, 'bundle' => $bundle), $include_additional);
  return $instances ? current($instances) : FALSE;
}

/**
 * Reads in field instances that match an array of conditions.
 *
 * @param $param
 *   An array of properties to use in selecting a field instance. Keys are names
 *   of properties found in field instance configuration files, and values are
 *   conditions to match.
 * @param $include_additional
 *   The default behavior of this function is to not return field instances that
 *   have been marked deleted, or whose field is inactive. Setting
 *   $include_additional['include_inactive'] or
 *   $include_additional['include_deleted'] to TRUE will override this behavior.
 *
 * @return
 *   An array of instances matching the arguments.
 */
function field_read_instances($conditions = array(), $include_additional = array()) {
  // Include instances of inactive fields if specified in the
  // $include_additional parameter.
  $include_inactive = isset($include_additional['include_inactive']) && $include_additional['include_inactive'];
  // Include deleted instances if specified either in the $include_additional
  // or the $conditions parameters.
  $include_deleted = (isset($include_additional['include_deleted']) && $include_additional['include_deleted']) || (isset($conditions['deleted']) && $conditions['deleted']);

  // Get instances stored in configuration.
  if (isset($conditions['entity_type']) && isset($conditions['bundle']) && isset($conditions['field_name'])) {
    // Optimize for the most frequent case where we do have a specific ID.
    $instances = entity_load_multiple('field_instance', array($conditions['entity_type'] . '.' . $conditions['bundle'] . '.' . $conditions['field_name']));
  }
  else {
    // No specific ID, we need to examine all existing instances.
    $instances = entity_load_multiple('field_instance');
  }

  // Merge deleted instances (stored in state) if needed.
  if ($include_deleted) {
    $state = Drupal::state();
    $deleted_fields = $state->get('field.field.deleted');
    $deleted_instances = $state->get('field.instance.deleted') ?: array();
    foreach ($deleted_instances as $id => $config) {
      $instances[$id] = entity_create('field_instance', $config);
    }
  }

  // Translate "do not include inactive fields" into actual conditions.
  if (!$include_inactive) {
    $conditions['field.active'] = TRUE;
    $conditions['field.storage.active'] = TRUE;
  }

  // Collect matching instances.
  $matching_instances = array();
  foreach ($instances as $instance) {
    // Only include instances on unknown entity types if 'include_inactive'.
    if (!$include_inactive && !entity_get_info($instance->entity_type)) {
      continue;
    }

    // Some conditions are checked against the field.
    $field = $instance->getField();

    // Only keep the instance if it matches all conditions.
    foreach ($conditions as $key => $value) {
      // Extract the actual value against which the condition is checked.
      switch ($key) {
        case 'field_name':
          $checked_value = $field->id;
          break;

        case 'field.active':
          $checked_value = $field->active;
          break;

        case 'field.storage.active':
          $checked_value = $field->storage['active'];
          break;

        case 'field_id':
          $checked_value = $instance->field_uuid;
          break;

        default:
          $checked_value = $instance->$key;
          break;
      }

      // Skip to the next instance as soon as one condition does not match.
      if ($checked_value != $value) {
        continue 2;
      }
    }

    module_invoke_all('field_read_instance', $instance);

    $matching_instances[] = $instance;
  }

  return $matching_instances;
}

/**
 * Marks a field instance and its data for deletion.
 *
 * @param \Drupal\field\Plugin\Core\Entity\FieldInstance $instance
 *   The field instance.
 * @param $field_cleanup
 *   If TRUE, the field will be deleted as well if its last instance is being
 *   deleted. If FALSE, it is the caller's responsibility to handle the case of
 *   fields left without instances. Defaults to TRUE.
 */
function field_delete_instance(FieldInstance $instance, $field_cleanup = TRUE) {
  $instance->delete($field_cleanup);
}

/**
 * @} End of "defgroup field_crud".
 */

/**
 * @defgroup field_purge Field API bulk data deletion
 * @{
 * Cleans up after Field API bulk deletion operations.
 *
 * Field API provides functions for deleting data attached to individual
 * entities as well as deleting entire fields or field instances in a single
 * operation.
 *
 * Deleting field data items for an entity with field_attach_delete() involves
 * three separate operations:
 * - Invoking the Field Type API hook_field_delete() for each field on the
 *   entity. The hook for each field type receives the entity and the specific
 *   field being deleted. A file field module might use this hook to delete
 *   uploaded files from the filesystem.
 * - Invoking the Field Storage API hook_field_storage_delete() to remove data
 *   from the primary field storage. The hook implementation receives the entity
 *   being deleted and deletes data for all of the entity's bundle's fields.
 * - Invoking the global Field Attach API hook_field_attach_delete() for all
 *   modules that implement it. Each hook implementation receives the entity
 *   being deleted and can operate on whichever subset of the entity's bundle's
 *   fields it chooses to.
 *
 * These hooks are invoked immediately when field_attach_delete() is called.
 * Similar operations are performed for field_attach_delete_revision().
 *
 * When a field, bundle, or field instance is deleted, it is not practical to
 * invoke these hooks immediately on every affected entity in a single page
 * request; there could be thousands or millions of them. Instead, the
 * appropriate field data items, instances, and/or fields are marked as deleted
 * so that subsequent load or query operations will not return them. Later, a
 * separate process cleans up, or "purges", the marked-as-deleted data by going
 * through the three-step process described above and, finally, removing deleted
 * field and instance records.
 *
 * Purging field data is made somewhat tricky by the fact that, while
 * field_attach_delete() has a complete entity to pass to the various deletion
 * hooks, the Field API purge process only has the field data it has previously
 * stored. It cannot reconstruct complete original entities to pass to the
 * deletion hooks. It is even possible that the original entity to which some
 * Field API data was attached has been itself deleted before the field purge
 * operation takes place.
 *
 * Field API resolves this problem by using "pseudo-entities" during purge
 * operations. A pseudo-entity contains only the information from the original
 * entity that Field API knows about: entity type, ID, revision ID, and bundle.
 * It also contains the field data for whichever field instance is currently
 * being purged. For example, suppose that the node type 'story' used to contain
 * a field called 'subtitle' but the field was deleted. If node 37 was a story
 * with a subtitle, the pseudo-entity passed to the purge hooks would look
 * something like this:
 *
 * @code
 *   $entity = stdClass Object(
 *     [nid] => 37,
 *     [vid] => 37,
 *     [type] => 'story',
 *     [subtitle] => array(
 *       [0] => array(
 *         'value' => 'subtitle text',
 *       ),
 *     ),
 *   );
 * @endcode
 *
 * See @link field Field API @endlink for information about the other parts of
 * the Field API.
 */

/**
 * Purges a batch of deleted Field API data, instances, or fields.
 *
 * This function will purge deleted field data in batches. The batch size
 * is defined as an argument to the function, and once each batch is finished,
 * it continues with the next batch until all have completed. If a deleted field
 * instance with no remaining data records is found, the instance itself will
 * be purged. If a deleted field with no remaining field instances is found, the
 * field itself will be purged.
 *
 * @param $batch_size
 *   The maximum number of field data records to purge before returning.
 */
function field_purge_batch($batch_size) {
  // Retrieve all deleted field instances. We cannot use field_info_instances()
  // because that function does not return deleted instances.
  $instances = field_read_instances(array('deleted' => TRUE), array('include_deleted' => TRUE));
  $factory = Drupal::service('entity.query');
  $info = entity_get_info();
  foreach ($instances as $instance) {
    $entity_type = $instance['entity_type'];

    // EntityFieldQuery currently fails on conditions on comment bundle.
    // Remove when http://drupal.org/node/731724 is fixed.
    if ($entity_type == 'comment') {
      continue;
    }

    $ids = (object) array(
      'entity_type' => $entity_type,
      'bundle' => $instance['bundle'],
    );
    // field_purge_data() will need the field array.
    $field = field_info_field_by_id($instance['field_id']);
    // Retrieve some entities.
    $query = $factory->get($entity_type)
      ->condition('id:' . $field['uuid'] . '.deleted', 1)
      ->range(0, $batch_size);
    // If there's no bundle key, all results will have the same bundle.
    if (!empty($info[$entity_type]['entity_keys']['bundle'])) {
      $query->condition($info[$entity_type]['entity_keys']['bundle'], $ids->bundle);
    }
    $results = $query->execute();

    if ($results) {
      $entities = array();
      foreach ($results as $revision_id => $entity_id) {
        // This might not be the revision id if the entity type does not support
        // revisions but _field_create_entity_from_ids() checks that and
        // disregards this value so there is no harm setting it.
        $ids->revision_id = $revision_id;
        $ids->entity_id = $entity_id;
        $entities[$entity_id] = _field_create_entity_from_ids($ids);
      }
      field_attach_load($entity_type, $entities, FIELD_LOAD_CURRENT, array('field_id' => $field['uuid'], 'deleted' => 1));
      foreach ($entities as $entity) {
        // Purge the data for the entity.
        field_purge_data($entity, $field, $instance);
      }
    }
    else {
      // No field data remains for the instance, so we can remove it.
      field_purge_instance($instance);
    }
  }

  // Retrieve all deleted fields. Any that have no instances can be purged.
  $deleted_fields = Drupal::state()->get('field.field.deleted') ?: array();
  foreach ($deleted_fields as $field) {
    $field = new Field($field);
    $instances = field_read_instances(array('field_id' => $field['uuid']), array('include_deleted' => 1));
    if (empty($instances)) {
      field_purge_field($field);
    }
  }
}

/**
 * Purges the field data for a single field on a single pseudo-entity.
 *
 * This is basically the same as field_attach_delete() except it only applies to
 * a single field. The entity itself is not being deleted, and it is quite
 * possible that other field data will remain attached to it.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The pseudo-entity whose field data is being purged.
 * @param $field
 *   The (possibly deleted) field whose data is being purged.
 * @param $instance
 *   The deleted field instance whose data is being purged.
 */
function field_purge_data(EntityInterface $entity, $field, $instance) {
  // Each field type's hook_field_delete() only expects to operate on a single
  // field at a time, so we can use it as-is for purging.
  $options = array('field_id' => $instance['field_id'], 'deleted' => TRUE);
  _field_invoke('delete', $entity, $dummy, $dummy, $options);

  // Tell the field storage system to purge the data.
  module_invoke($field['storage']['module'], 'field_storage_purge', $entity, $field, $instance);

  // Let other modules act on purging the data.
  foreach (module_implements('field_attach_purge') as $module) {
    $function = $module . '_field_attach_purge';
    $function($entity, $field, $instance);
  }
}

/**
 * Purges a field instance record from the database.
 *
 * This function assumes all data for the instance has already been purged and
 * should only be called by field_purge_batch().
 *
 * @param $instance
 *   The instance record to purge.
 */
function field_purge_instance($instance) {
  // Notify the storage engine.
  $field = field_info_field_by_id($instance['field_id']);
  module_invoke($field['storage']['module'], 'field_storage_purge_instance', $instance);

  $state = Drupal::state();
  $deleted_instances = $state->get('field.instance.deleted');
  unset($deleted_instances[$instance['uuid']]);
  $state->set('field.instance.deleted', $deleted_instances);

  // Clear the cache.
  field_info_cache_clear();

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_purge_instance', $instance);
}

/**
 * Purges a field record from the database.
 *
 * This function assumes all instances for the field has already been purged,
 * and should only be called by field_purge_batch().
 *
 * @param $field
 *   The field record to purge.
 */
function field_purge_field($field) {
  $instances = field_read_instances(array('field_id' => $field['uuid']), array('include_deleted' => 1));
  if (count($instances) > 0) {
    throw new FieldException(t('Attempt to purge a field @field_name that still has instances.', array('@field_name' => $field['field_name'])));
  }

  $state = Drupal::state();
  $deleted_fields = $state->get('field.field.deleted');
  unset($deleted_fields[$field['uuid']]);
  $state->set('field.field.deleted', $deleted_fields);

  // Notify the storage engine.
  module_invoke($field['storage']['module'], 'field_storage_purge_field', $field);

  // Clear the cache.
  field_info_cache_clear();

  // Invoke external hooks after the cache is cleared for API consistency.
  module_invoke_all('field_purge_field', $field);
}

/**
 * @} End of "defgroup field_purge".
 */
