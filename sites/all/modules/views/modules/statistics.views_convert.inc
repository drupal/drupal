<?php
// $Id: statistics.views_convert.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $

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
function statistics_views_convert($display, $type, &$view, $field, $id = NULL) {
  switch ($type) {
    case 'field':
      switch ($field['tablename']) {
        case 'node_counter':
          switch ($field['field']) {
            case 'timestamp':
              $handlers = array(
                'views_handler_field_date_small' => 'small',
                'views_handler_field_date' => 'medium',
                'views_handler_field_date_large' => 'large',
                'views_handler_field_date_custom' => 'custom',
                'views_handler_field_since' => 'time ago',
              );
              $view->set_item_option($display, 'field', $id, 'date_format', $handlers[$field['handler']]);
              if (!empty($field['options'])) {
                $view->set_item_option($display, 'field', $id, 'custom_date_format', $field['options']);
              }
              break;
          }
          break;
      }
      break;
    case 'sort':
      switch ($field['tablename']) {
        case 'node_counter':
          switch ($field['field']) {
            case 'timestamp':
              $field['options'] = $field['options'] == 'normal' ? 'second' : $field['options'];
              $view->set_item_option($display, 'sort', $id, 'granularity', $field['options']);
              break;
          }
          break;
      }
      break;
  }
}
