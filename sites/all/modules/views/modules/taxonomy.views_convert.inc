<?php
//$Id: taxonomy.views_convert.inc,v 1.4.4.1 2009/11/02 22:01:26 merlinofchaos Exp $

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
function taxonomy_views_convert($display, $type, &$view, $field, $id = NULL) {
  switch ($type) {
    case 'field':
      if ($field['tablename'] == 'term_node' || !strncmp($field['tablename'], 'term_node_', 10)) {
        switch ($field['field']) {
          case 'name':
            $item = $view->get_item($display, 'field', $id);
            if ($field['options'] != 'nolink') {
              $item['link_to_taxonomy'] = TRUE;
            }
            if (!empty($field['vocabulary'])) {
              $item['limit'] = TRUE;
              $item['vids'] = array($field['vocabulary']);
            }
            $view->set_item($display, 'field', $id, $item);
            break;
        }
      }
      elseif ($field['tablename'] == 'term_data') {
        switch ($field['field']) {
          case 'name':
            if ($field['field'] == 'views_handler_field_tid_link') {
              $view->set_item_option($display, 'field', $id, 'link_to_taxonomy', TRUE);
            }
            break;
        }
      }
      break;
    case 'filter':
      if ($field['tablename'] == 'term_node' || !strncmp($field['tablename'], 'term_node_', 10)) {
        switch ($field['field']) {
          case 'tid':
            $operators = array('AND' => 'and', 'OR' => 'or', 'NOR' => 'not');
            $item = $view->get_item($display, 'filter', $id);
            if ($vid = (integer) substr($field['tablename'], 10)) {
              $item['table'] = 'term_node';
              $item['vid'] = $vid;
            }
            else {
              $item['limit'] = FALSE;
            }
            $item['operator'] = $operators[$field['operator']];
            $item['type'] = 'select';
            $view->set_item($display, 'filter', $id, $item);
            break;
        }
      }
      elseif ($field['tablename'] == 'term_data') {
        switch ($field['field']) {
          case 'vid':
            $operators = array('AND' => 'IN', 'OR' => 'IN', 'NOR' => 'NOT IN');
            $view->set_item_option($display, 'filter', $id, 'operator', $operators[$field['operator']]);
            break;
        }
      }
      break;
    case 'argument':
      $options = $field['argoptions'];
      switch ($field['type']) {
        case 'taxid':
          if (!empty($field['options'])) {
            $options['depth'] = $field['options'];
          }
          $options['break_phrase'] = TRUE;
          $view->add_item($display, 'argument', 'node', 'term_node_tid_depth', $options, $field['id']);
          break;
        case 'taxletter':
          if (!empty($field['options'])) {
            $options['glossary'] = TRUE;
            $options['limit'] = $field['options'];
          }
          $view->add_item($display, 'argument', 'term_data', 'name', $options, $field['id']);
          break;
        case 'vocid':
          $view->add_item($display, 'argument', 'vocabulary', 'vid', $options, $field['id']);
          break;
      }
      break;
  }
}
