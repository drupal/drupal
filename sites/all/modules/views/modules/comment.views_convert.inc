<?php
//$Id: comment.views_convert.inc,v 1.4.4.1 2009/11/02 22:01:26 merlinofchaos Exp $

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
function comment_views_convert($display, $type, &$view, $field, $id = NULL) {
  switch ($type) {
    case 'field':
      switch ($field['tablename']) {
        case 'comments':
          switch ($field['field']) {
            case 'subject':
              if ($field['options'] = 'nolink') {
                $view->set_item_option($display, 'field', $id, 'link_to_comment', FALSE);
              }
              break;
            case 'cid':
              $view->set_item_option($display, 'field', $id, 'link_to_comment', FALSE);
              break;
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
            case 'add':
              $view->set_item_option($display, 'field', $id, 'field', 'replyto_comment');
              break;
          }
          break;
        case 'node_comment_statistics':
          switch ($field['field']) {
            case 'last_comment_timestamp':
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
            case 'last_changed':
              $handlers = array(
                'views_handler_field_date_small' => 'small',
                'views_handler_field_date' => 'medium',
                'views_handler_field_date_large' => 'large',
                'views_handler_field_date_custom' => 'custom',
                'views_handler_field_since' => 'time ago',
              );
              $item = $view->get_item($display, 'field', $id);
              $item['date_format'] = $handlers[$field['handler']];
              if (!empty($field['options'])) {
                $item['custom_date_format'] = $field['options'];
              }
              $item['field'] = 'last_updated';
              $view->set_item($display, 'field', $id, $item);
              break;
          }
          break;
      }
      break;
    case 'filter':
      switch ($field['tablename']) {
        case 'node_comment_statistics':
          switch ($field['field']) {
            case 'comment_count':
              $view->set_item_option($display, 'filter', $id, 'operator', $field['operator']);
              break;
            case 'last_changed':
              $field['field'] = 'last_updated';
            case 'last_comment_timestamp':
              $item = $view->get_item($display, 'filter', $id);
              $item['operator'] = $field['operator'];
              $item['value'] = array(
                'type' => $field['value'] == 'now' ? 'offset' : 'date',
                'value' => $field['value'],
              );
              if (!empty($field['options'])) {
                $item['value']['value'] = intval($field['options']) .' seconds';
              }
              $item['field'] = $field['field'];
              $view->set_item($display, 'filter', $id, $item);
              break;
          }
          break;
      }
      break;
    case 'sort':
      switch ($field['tablename']) {
        case 'comments':
          switch ($field['field']) {
            case 'timestamp':
              $field['options'] = $field['options'] == 'normal' ? 'second' : $field['options'];
              $view->set_item_option($display, 'sort', $id, 'granularity', $field['options']);
              break;
          }
          break;
        case 'node_comment_statistics':
          switch ($field['field']) {
            case 'last_changed':
              $view->set_item_option($display, 'sort', $id, 'field', 'last_updated');
            case 'last_comment_timestamp':
              $field['options'] = $field['options'] == 'normal' ? 'second' : $field['options'];
              $view->set_item_option($display, 'sort', $id, 'granularity', $field['options']);
              break;
          }
          break;
      }
      break;
  }
}
