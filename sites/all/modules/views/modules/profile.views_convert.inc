<?php
// $Id: profile.views_convert.inc,v 1.2.4.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * @file
 * Field conversion for fields handled by this module.
 */

/**
 * Implements hook_views_convert().
 *
 * Intervene to convert field values from the Views 1 format to the
 * Views 2 format. Intervene only if $view->add_item() won't produce
 * the right results, usually needed to set field options or values.
 */
function profile_views_convert($display, $type, &$view, $field, $id = NULL) {
  static $profile_fields;
  if (!isset($profile_fields)) {
    $profile_fields = array();
    foreach (profile_views_get_fields() as $profile_field) {
      $profile_fields['profile_values_'. $profile_field->name] = $profile_field;
    }
  }
  switch ($type) {
    case 'filter':
      if (isset($tables[$field['tablename']])) {
        switch ($profile_fields[$field['tablename']]->type) {
          case 'vocabulary':
          case 'selection':
            $operators = array('AND' => 'in', 'OR' => 'in', 'NOR' => 'not in');
            $view->set_item_option($display, 'filter', $id, 'operator', $operators[$field['operator']]);
            break;
          default:
            $view->set_item_option($display, 'filter', $id, 'operator', $field['operator']);
            break;
        }
      }
      break;
  }
}