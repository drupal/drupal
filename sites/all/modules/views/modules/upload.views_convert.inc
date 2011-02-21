<?php
//$Id: upload.views_convert.inc,v 1.4.4.1 2009/11/02 22:01:26 merlinofchaos Exp $

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
function upload_views_convert($display, $type, &$view, $field, $id = NULL) {
  switch ($type) {
    case 'field':
      switch ($field['tablename']) {
        case 'file_revisions':
          switch ($field['field']) {
            case 'fid':
              $relationship = $view->add_item($display, 'relationship', 'node', 'upload_fid', array(), 'node_upload_fid');
              $view->set_item_option($display, 'field', $id, 'relationship', $relationship);
              $view->set_item_option($display, 'field', $id, 'table', 'files');
              break;
          }
          break;
        case 'files':
          switch ($field['field']) {
            case 'all_files':
              $item = $view->get_item($display, 'field', $id);
              switch ($field['options']) {
                case 'link':
                  $item['link_to_file'] = TRUE;
                  break;
                case 'linkdesc':
                  $item['link_to_file'] = TRUE;
                case 'nolinkdesc':
                  $item['alter']['alter_text'] = TRUE;
                  $item['alter']['text'] = '['. $view->add_item($display, 'field', 'upload', 'description', array('exclude' => TRUE)) .']';
                  break;
              }
              if ($field['handler'] == 'views_handler_file_listed_files') {
                $item['only_listed'] = TRUE;
              }
              $item['table'] = 'node';
              $item['field'] = 'upload_fid';
              $view->set_item($display, 'field', $id, $item);
              break;
            case 'filename':
              if ($field['handler'] == 'views_handler_file_filename_download') {
                $view->set_item_option($display, 'field', $id, 'link_to_file', TRUE);
              }
              $relationship = $view->add_item($display, 'relationship', 'node', 'upload_fid', array(), 'node_upload_fid');
              $view->set_item_option($display, 'field', $id, 'relationship', $relationship);
              break;
          }
          break;
      }
      break;
    case 'filter':
      switch ($field['tablename']) {
        case 'file_revisions':
          switch ($field['field']) {
            case 'fid':
              $view->set_item_option($display, 'filter', $id, 'table', 'node');
              $view->set_item_option($display, 'filter', $id, 'field', 'upload_fid');
              break;
            case 'list':
              $view->set_item_option($display, 'filter', $id, 'table', 'upload');
              break;
          }
          break;
        case 'files':
          switch ($field['field']) {
            case 'filename':
            case 'filemime':
              $item = $view->get_item($display, 'filter', $id);
              $item['operator'] = $field['operator'];
              $item['case'] = FALSE;
              $item['relationship'] = $view->add_item($display, 'relationship', 'node', 'upload_fid', array(), 'node_upload_fid');
              $view->set_item($display, 'filter', $id, $item);
              break;
            case 'filesize':
              $relationship = $view->add_item($display, 'relationship', 'node', 'upload_fid', array(), 'node_upload_fid');
              $view->set_item_option($display, 'filter', $id, 'relationship', $relationship);
              $view->set_item_option($display, 'filter', $id, 'operator', $field['operator']);
              break;
          }
          break;
      }
      break;
    case 'sorts':
      switch ($field['tablename']) {
        case 'file_revisions':
          switch ($field['field']) {
            case 'fid':
              $relationship = $view->add_item($display, 'relationship', 'node', 'upload_fid', array(), 'node_upload_fid');
              $view->set_item_option($display, 'field', $id, 'relationship', $relationship);
              $view->set_item_option($display, 'field', $id, 'table', 'files');
              break;
          }
          break;
      }
      break;
  }
}
