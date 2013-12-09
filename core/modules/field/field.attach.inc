<?php

/**
 * @file
 * Field attach API, allowing entities (nodes, users, ...) to be 'fieldable'.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
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
 *   A function that receives a FieldDefinitionInterface object and returns the
 *   object on which the method should be invoked.
 * @param \Drupal\Core\Entity\EntityInterface $entity
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
 *     bundle.
 *
 * @return array
 *   An array of returned values.
 */
function field_invoke_method($method, $target_function, EntityInterface $entity, &$a = NULL, &$b = NULL, array $options = array()) {
  $entity_type = $entity->entityType();

  // Determine the list of fields to iterate on.
  $field_definitions = _field_invoke_get_field_definitions($entity_type, $entity->bundle(), $options);

  // Iterate through the fields and collect results.
  $return = array();
  foreach ($field_definitions as $field_definition) {
    // Let the function determine the target object on which the method should be
    // called.
    $target = call_user_func($target_function, $field_definition);

    if (method_exists($target, $method)) {
      $items = $entity->get($field_definition->getName());
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

  return $return;
}

/**
 * Invokes a method across fields on multiple entities.
 *
 * @param string $method
 *   The name of the method to invoke.
 * @param callable $target_function
 *   A function that receives a FieldDefinitionInterface object and a bundle
 *   name and returns the object on which the method should be invoked.
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
 *     bundle.
 *
 * @return array
 *   An array of returned values keyed by entity ID.
 *
 * @see field_invoke_method()
 */
function field_invoke_method_multiple($method, $target_function, array $entities, &$a = NULL, &$b = NULL, array $options = array()) {
  $grouped_items = array();
  $grouped_targets = array();
  $return = array();

  // Go through the entities and collect the instances on which the method
  // should be called.
  foreach ($entities as $entity) {
    $entity_type = $entity->entityType();
    $bundle = $entity->bundle();
    $id = $entity->id();

    // Determine the list of fields to iterate on.
    $field_definitions = _field_invoke_get_field_definitions($entity_type, $bundle, $options);

    foreach ($field_definitions as $field_definition) {
      $field_name = $field_definition->getName();
      $group_key = "$bundle:$field_name";

      // Let the closure determine the target object on which the method should
      // be called.
      if (empty($grouped_targets[$group_key])) {
        $target = call_user_func($target_function, $field_definition, $bundle);
        if (method_exists($target, $method)) {
          $grouped_targets[$group_key] = $target;
        }
        else {
          $grouped_targets[$group_key] = FALSE;
        }
      }

      // If there is a target, group the field items.
      if ($grouped_targets[$group_key]) {
        $items = $entity->get($field_name);
        $items->filterEmptyValues();
        $grouped_items[$group_key][$id] = $items;
      }
    }
    // Initialize the return value for each entity.
    $return[$id] = array();
  }

  // For each field, invoke the method and collect results.
  foreach ($grouped_items as $key => $entities_items) {
    $results = $grouped_targets[$key]->$method($entities_items, $a, $b);

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

  return $return;
}

/**
 * Retrieves a list of field definitions to operate on.
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
 *   The array of selected field definitions.
 */
function _field_invoke_get_field_definitions($entity_type, $bundle, $options) {
  // @todo Replace with \Drupal::entityManager()->getFieldDefinition() after
  //   [#2047229] lands.
  $entity = _field_create_entity_from_ids((object) array('entity_type' => $entity_type, 'bundle' => $bundle, 'entity_id' => NULL));
  $field_definitions = array();
  if (isset($options['field_name'])) {
    if ($entity->hasField($options['field_name'])) {
      $field_definitions[] = $entity->get($options['field_name'])->getFieldDefinition();
    }
  }
  else {
    foreach ($entity as $items) {
      $field_definitions[] = $items->getFieldDefinition();
    }
  }

  return $field_definitions;
}

/**
 * Defines a 'target function' for field_invoke_method().
 *
 * Used to invoke methods on a field's widget.
 *
 * @param \Drupal\entity\Entity\EntityFormDisplay $form_display
 *   An EntityFormDisplay object.
 *
 * @return callable $target_function
 *   A 'target function' for field_invoke_method().
 */
function _field_invoke_widget_target($form_display) {
  return function (FieldDefinitionInterface $field_definition) use ($form_display) {
    return $form_display->getRenderer($field_definition->getName());
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
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $field_name => $instance) {
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
