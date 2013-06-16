<?php

/**
 * @file
 * Hooks provided by the Options module.
 */

/**
 * Returns the list of options to be displayed for a field.
 *
 * Field types willing to enable one or several of the widgets defined in
 * options.module (select, radios/checkboxes, on/off checkbox) need to
 * implement this hook to specify the list of options to display in the
 * widgets.
 *
 * @param \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition
 *   The field definition.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object the field is attached to.
 *
 * @return
 *   The array of options for the field. Array keys are the values to be
 *   stored, and should be of the data type (string, number...) expected by
 *   the first 'column' for the field type. Array values are the labels to
 *   display within the widgets. The labels should NOT be sanitized,
 *   options.module takes care of sanitation according to the needs of each
 *   widget. The HTML tags defined in _field_filter_xss_allowed_tags() are
 *   allowed, other tags will be filtered.
 */
function hook_options_list(\Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition, \Drupal\Core\Entity\EntityInterface $entity) {
  // Sample structure.
  $options = array(
    0 => t('Zero'),
    1 => t('One'),
    2 => t('Two'),
    3 => t('Three'),
  );

  // Sample structure with groups. Only one level of nesting is allowed. This
  // is only supported by the 'options_select' widget. Other widgets will
  // flatten the array.
  $options = array(
    t('First group') => array(
      0 => t('Zero'),
    ),
    t('Second group') => array(
      1 => t('One'),
      2 => t('Two'),
    ),
    3 => t('Three'),
  );

  // In actual implementations, the array of options will most probably depend
  // on properties of the field. Example from taxonomy.module:
  $options = array();
  foreach ($field_definition->getFieldSetting('allowed_values') as $tree) {
    $terms = taxonomy_get_tree($tree['vid'], $tree['parent'], NULL, TRUE);
    if ($terms) {
      foreach ($terms as $term) {
        $options[$term->id()] = str_repeat('-', $term->depth) . $term->label();
      }
    }
  }

  return $options;
}

/**
 * Alters the list of options to be displayed for a field.
 *
 * This hook can notably be used to change the label of the empty option.
 *
 * @param array $options
 *   The array of options for the field, as returned by hook_options_list(). An
 *   empty option (_none) might have been added, depending on the field
 *   properties.
 *
 * @param array $context
 *   An associative array containing:
 *   - field_definition: The field definition
 *     (\Drupal\Core\Entity\Field\FieldDefinitionInterface).
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
