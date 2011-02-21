<?php
//$Id: node.views_convert.inc,v 1.7.4.2 2010/02/06 16:29:52 dereine Exp $

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
function node_views_convert($display, $type, &$view, $field, $id = NULL) {
  switch ($type) {
    case 'field':
      switch ($field['tablename']) {
        case 'node':
          switch ($field['field']) {
            case 'title':
              if ($field['options'] != 'no_link') {
                $view->set_item_option($display, 'field', $id, 'link_to_node', TRUE);
              }
              break;
            case 'created':
            case 'changed':
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
            case 'body':
              $field['field'] = $field['handler'] == 'views_handler_field_teaser' ? 'teaser' : $field['field'];
              $view->set_item_option($display, 'field', $id, 'field', $field['field']);
              $view->set_item_option($display, 'field', $id, 'table', 'node_revisions');
              break;
            case 'link':
            case 'edit':
            case 'delete':
            case 'view':
              $field['field'] = $field['field'] == 'link' ? 'view_node' : "$field[field]_node";
              $view->set_item_option($display, 'field', $id, 'field', $field['field']);
              if (!empty($field['options'])) {
                $view->set_item_option($display, 'field', $id, 'text', $field['options']);
              }
              break;
          }
          break;
      }
      break;
    case 'filter':
      switch ($field['tablename']) {
        case 'node':
          switch ($field['field']) {
            case 'type':
              $operators = array('OR' => 'IN', 'NOR' => 'NOT IN');
              $view->set_item_option($display, 'filter', $id, 'operator', $operators[$field['operator']]);
              break;
            case 'anon':
              $item = $view->get_item($display, 'filter', $id);
              $item['value'] = array(0);
              $item['table'] = 'users';
              $item['field'] = 'uid';
              $view->set_item($display, 'filter', $id, $item);
              break;
            case 'currentuid':
              $operators = array('=' => TRUE, '!=' => FALSE);
              $item = $view->get_item($display, 'filter', $id);
              $item['value'] = $operators[$field['operator']];
              $item['table'] = 'users';
              $item['field'] = 'uid_current';
              $view->set_item($display, 'filter', $id, $item);
              break;
            case 'currentuidtouched':
              $view->set_item_option($display, 'filter', $id, 'value', '=');
              $view->set_item_option($display, 'filter', $id, 'field', 'uid_touch');
              break;
            case 'distinct':
              $view->display_handler->set_option('distinct', $field['operator'] == '=' && $field['value'] == 'distinct');
              $view->set_item($display, 'filter', $id, NULL);
              break;
            case 'title':
              $item = $view->get_item($display, 'filter', $id);
              $item['operator'] = $field['operator'];
              $item['case'] = FALSE;
              $view->set_item($display, 'filter', $id, $item);
              break;
            case 'created':
            case 'changed':
              $item = $view->get_item($display, 'filter', $id);
              $item['operator'] = $field['operator'];
              $item['value'] = array(
                'type' => $field['value'] == 'now' ? 'offset' : 'date',
                'value' => $field['value'],
              );
              if (!empty($field['options'])) {
                $item['value']['value'] = intval($field['options']) .' seconds';
              }
              $view->set_item($display, 'filter', $id, $item);
              break;
            case 'body':
              $item = $view->get_item($display, 'filter', $id);
              $item['operator'] = $field['operator'];
              $item['case'] = FALSE;
              $item['table'] = 'node_revisions';
              $view->set_item($display, 'filter', $id, $item);
              break;
          }
          break;
        case 'history':
          switch ($field['field']) {
            case 'timestamp':
              $view->set_item_option($display, 'filter', $id, 'table', 'history');
              break;
          }
          break;
      }
      break;
    case 'sort':
      switch ($field['tablename']) {
        case 'node':
          switch ($field['field']) {
            case 'created':
            case 'changed':
              $field['options'] = $field['options'] == 'normal' ? 'second' : $field['options'];
              $view->set_item_option($display, 'sort', $id, 'granularity', $field['options']);
              break;
            case 'random':
              $view->set_item_option($display, 'sort', $id, 'table', 'views');
              break;
          }
          break;
      }
      break;
    case 'argument':
      $options = $field['argoptions'];
      switch ($field['type']) {
        case 'nodetype':
          $view->add_item($display, 'argument', 'node', 'type', $options, $field['id']);
          break;
        case 'nodeletter':
          if (!empty($field['options'])) {
            $options['glossary'] = TRUE;
            $options['limit'] = $field['options'];
          }
          $options['case'] = 'upper';
          $view->add_item($display, 'argument', 'node', 'title', $options, $field['id']);
          break;
        case 'year':
          $view->add_item($display, 'argument', 'node', 'created_year', $options, $field['id']);
          break;
        case 'month':
          $view->add_item($display, 'argument', 'node', 'created_month', $options, $field['id']);
          break;
        case 'week':
          $view->add_item($display, 'argument', 'node', 'created_week', $options, $field['id']);
          break;
        case 'monthyear':
          $view->add_item($display, 'argument', 'node', 'created_year_month', $options, $field['id']);
          break;
        case 'fulldate':
          $view->add_item($display, 'argument', 'node', 'created_fulldate', $options, $field['id']);
          break;
        case 'nid':
          if (!empty($field['options'])) {
            $options['not'] = TRUE;
          }
          $view->add_item($display, 'argument', 'node', 'nid', $options, $field['id']);
          break;
      }
      break;
  }
}
