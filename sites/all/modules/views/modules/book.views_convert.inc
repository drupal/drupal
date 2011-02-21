<?php
// $Id: book.views_convert.inc,v 1.3.4.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * @file
 * Field conversion for fields handled by this module.
 */

/**
 * Implements hook_views_convert().
 */
function book_views_convert($display, $type, &$view, $field, $id = NULL) {
  switch ($type) {
    case 'field':
      switch ($field['tablename']) {
        case 'book_parent_node':
          switch ($field['field']) {
            case 'title':
              $item = $view->get_item($display, 'field', $id);
              if ($field['handler'] == 'views_handler_field_book_parent_title') {
                $item['link_to_node'] = TRUE;
              }
              $item['relationship'] = $view->add_item($display, 'relationship', 'book_parent', 'nid', array(), 'book_parent_nid');
              $item['table'] = 'node';
              $item['field'] = 'title';
              $view->set_item($display, 'field', $id, $item);
              break;
          }
          break;
      }
      break;
    case 'filter':
      switch ($field['tablename']) {
        case 'book':
          switch ($field['field']) {
            case 'parent':
              $operators = array('AND' => '=', 'OR' => '=', 'NOT' => '!=');
              $item = $view->get_item($display, 'filter', $id);
              $item['operator'] = $operators[$field['operator']];
              $item['relationship'] = $view->add_item($display, 'relationship', 'book_parent', 'nid', array(), 'book_parent_nid');
              $item['table'] = 'node';
              $item['field'] = 'nid';
              $view->set_item($display, 'filter', $id, $item);
              break;
          }
          break;
      }
      break;
    case 'sort':
      switch ($field['tablename']) {
        case 'book':
          switch ($field['field']) {
            case 'weight':
              $view->set_item_option($display, 'sort', $id, 'table', 'book_menu_links');
              break;
          }
          break;
        case 'book_parent_node':
          switch ($field['field']) {
            case 'title':
              $item = $view->get_item($display, 'sort', $id);
              $item['relationship'] = $view->add_item($display, 'relationship', 'book_parent', 'nid', array(), 'book_parent_nid');
              $item['table'] = 'node';
              $item['field'] = 'title';
              $view->set_item($display, 'sort', $id, $item);
              break;
          }
          break;
      }
      break;
    case 'argument':
      $options = $field['argoptions'];
      switch ($field['type']) {
        case 'book_parent':
          $options['relationship'] = $view->add_item($display, 'relationship', 'book_parent', 'nid', array(), 'book_parent_nid');
          $view->add_item($display, 'argument', 'node', 'nid', $options, $field['id']);
          break;
      }
      break;
  }
}
