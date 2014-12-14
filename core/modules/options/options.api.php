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
 * Provides the allowed values for an options field or widget.
 *
 * Callback for options_allowed_values().
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   The field storage definition.
 * @param \Drupal\Core\Entity\FieldableEntityInterface|NULL $entity
 *   (optional) A specific entity to use for either restricting the values or
 *   customizing the labels for particular bundles and entities. NULL when
 *   there is not a specific entity available, such as for Views filters.
 * @param bool $cacheable
 *   (optional) If $cacheable is FALSE, then the allowed values are not
 *   statically cached. See options_test_dynamic_values_callback() for an
 *   example of generating dynamic and uncached values. Defaults to TRUE.
 *
 * @return array
 *   The array of allowed values. Keys of the array are the raw stored values
 *   (number or text), values of the array are the display labels. If $entity
 *   is NULL, you should return the list of all the possible allowed values in
 *   any context so that other code (e.g. Views filters) can support the
 *   allowed values for all possible entities and bundles.
 *
 * @see options_allowed_values()
 * @see options_test_allowed_values_callback()
 * @see options_test_dynamic_values_callback()
 */
function callback_allowed_values_function(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, $cacheable = TRUE) {
  if (isset($entity) && ($entity->bundle() == 'not_a_programmer')) {
    $values = array(
      'Group 1' => array(
        1 => 'One',
      ),
      'Group 2' => array(
        2 => 'Two',
      ),
    );
  }
  else {
    $values = array(
      'Group 1' => array(
        0 => 'Zero',
      ),
      'Group 2' => array(
        1 => 'One',
      ),
    );
  }

  return $values;
}
