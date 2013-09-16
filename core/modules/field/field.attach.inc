<?php

/**
 * @file
 * Field attach API, allowing entities (nodes, users, ...) to be 'fieldable'.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity\Entity\EntityFormDisplay;

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
 * hook_field_attach_view_alter().
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
      $field = $instance->getField();
      $field_name = $field['field_name'];

      // Determine the list of languages to iterate on.
      $available_langcodes = field_available_languages($entity_type, $field);
      $langcodes = _field_language_suggestion($available_langcodes, $options['langcode'], $field_name);

      foreach ($langcodes as $langcode) {
        $items = $entity->getTranslation($langcode)->get($field_name);
        $items->filterEmptyValues();

        $result = $target->$method($items, $a, $b);

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
        $field = $instance->getField();
        $available_langcodes = field_available_languages($entity_type, $field);
        $langcode = !empty($options['langcode'][$id]) ? $options['langcode'][$id] : $options['langcode'];
        $langcodes = _field_language_suggestion($available_langcodes, $langcode, $field_name);
        foreach ($langcodes as $langcode) {
          // Group the items corresponding to the current field.
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
    // Iterate over all the field translations.
    foreach ($grouped_items[$instance_id] as $langcode => $items) {
      $results = $grouped_targets[$instance_id]->$method($items, $a, $b);

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
 * @param \Drupal\entity\Entity\EntityFormDisplay $form_display
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
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    $field_name = $instance['field_name'];
    if (isset($element[$field_name]['#language'])) {
      $langcode = $element[$field_name]['#language'];
      $variables[$field_name] = $entity->getTranslation($langcode)->{$field_name}->getValue();
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
 * @} End of "defgroup field_attach".
 */
