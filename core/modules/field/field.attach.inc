<?php

/**
 * @file
 * Field attach API, allowing entities (nodes, users, ...) to be 'fieldable'.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

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
 * hook_entity_type_alter(). For instance, node_entity_type_alter() exposes
 * each node type as its own bundle. This means that the set of fields of a
 * node is determined by the node type.
 *
 * The Field API reads the bundle name for a given entity from a particular
 * property of the entity object, and hook_entity_type_alter() defines which
 * property to use. For instance, \Drupal\node\Entity\Node specifies:
 * @code
 *   entity_keys = {
 *     "bundle" = "type"
 *   }
 * @endcode
 * This indicates that for a particular node object, the bundle name can be
 * found in $node->type. This property can be omitted if the entity type only
 * exposes a single bundle (all entities of this type have the same collection
 * of fields). This is the case for the 'user' entity type.
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
  $entity_type = $entity->getEntityTypeId();

  // Determine the list of fields to iterate on.
  $field_definitions = _field_invoke_get_field_definitions($entity_type, $entity->bundle(), $options);

  // Iterate through the fields and collect results.
  $return = array();
  foreach ($field_definitions as $field_definition) {
    // Let the function determine the target object on which the method should be
    // called.
    $target = call_user_func($target_function, $field_definition);

    if (method_exists($target, $method)) {
      $field_name = $field_definition->getName();
      $items = $entity->get($field_name);
      $items->filterEmptyItems();

      $result = $target->$method($items, $a, $b);
      if (isset($result)) {
        $return[$field_name] = $result;
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
 *   - field_name
 *   See field_invoke_method() for details.
 *
 * @return
 *   The array of selected field definitions.
 */
function _field_invoke_get_field_definitions($entity_type, $bundle, $options) {
  $definitions = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
  if (isset($options['field_name'])) {
    $definitions = array_intersect_key($definitions, array($options['field_name'] => TRUE));
  }
  return $definitions;
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
 * @} End of "defgroup field_attach".
 */
