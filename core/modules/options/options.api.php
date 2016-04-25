<?php

/**
 * @file
 * Hooks provided by the Options module.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Alters the list of options to be displayed for a field.
 *
 * This hook can notably be used to change the label of the empty option.
 *
 * @param array $options
 *   The array of options for the field, as returned by
 *   \Drupal\Core\TypedData\OptionsProviderInterface::getSettableOptions(). An
 *   empty option (_none) might have been added, depending on the field
 *   properties.
 *
 * @param array $context
 *   An associative array containing:
 *   - field_definition: The field definition
 *     (\Drupal\Core\Field\FieldDefinitionInterface).
 *   - entity: The entity object the field is attached to
 *     (\Drupal\Core\Entity\EntityInterface).
 *
 * @ingroup hooks
 * @see hook_options_list()
 */
function hook_options_list_alter(array &$options, array $context) {
  // Check if this is the field we want to change.
  if ($context['field']->id() == 'field_option') {
    // Change the label of the empty option.
    $options['_none'] = t('== Empty ==');
  }
}

/**
 * Provide the allowed values for a 'list_*' field.
 *
 * Callback for options_allowed_values().
 *
 * 'list_*' fields can specify a callback to define the set of their allowed
 * values using the 'allowed_values_function' storage setting.
 *
 * That function will be called:
 *  - either in the context of a specific entity, which is then provided as the
 *    $entity parameter,
 *  - or for the field generally without the context of any specific entity or
 *    entity bundle (typically, Views needing a list of values for an exposed
 *    filter), in which case the $entity parameter is NULL.
 * This lets the callback restrict the set of allowed values or adjust the
 * labels depending on some conditions on the containing entity.
 *
 * For consistency, the set of values returned when an $entity is provided
 * should be a subset of the values returned when no $entity is provided.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   The field storage definition.
 * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
 *   (optional) The entity context if known, or NULL if the allowed values are
 *   being collected without the context of a specific entity.
 * @param bool &$cacheable
 *   (optional) If an $entity is provided, the $cacheable parameter should be
 *   modified by reference and set to FALSE if the set of allowed values
 *   returned was specifically adjusted for that entity and cannot not be reused
 *   for other entities. Defaults to TRUE.
 *
 * @return array
 *   The array of allowed values. Keys of the array are the raw stored values
 *   (number or text), values of the array are the display labels. If $entity
 *   is NULL, you should return the list of all the possible allowed values in
 *   any context so that other code (e.g. Views filters) can support the allowed
 *   values for all possible entities and bundles.
 *
 * @ingroup callbacks
 * @see options_allowed_values()
 * @see options_test_allowed_values_callback()
 * @see options_test_dynamic_values_callback()
 */
function callback_allowed_values_function(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, &$cacheable = TRUE) {
  if (isset($entity) && ($entity->bundle() == 'not_a_programmer')) {
    $values = array(
      1 => 'One',
      2 => 'Two',
    );
  }
  else {
    $values = array(
      'Group 1' => array(
        0 => 'Zero',
        1 => 'One',
      ),
      'Group 2' => array(
        2 => 'Two',
      ),
    );
  }

  return $values;
}
