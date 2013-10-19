<?php

/**
 * @file
 * Hooks provided by the Options module.
 */

/**
 * Alters the list of options to be displayed for a field.
 *
 * This hook can notably be used to change the label of the empty option.
 *
 * @param array $options
 *   The array of options for the field, as returned by
 *   \Drupal\Core\TypedData\AllowedValuesInterface::getSettableOptions(). An
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
 * @see hook_options_list()
 */
function hook_options_list_alter(array &$options, array $context) {
  // Check if this is the field we want to change.
  if ($context['field']->id() == 'field_option') {
    // Change the label of the empty option.
    $options['_none'] = t('== Empty ==');
  }
}
