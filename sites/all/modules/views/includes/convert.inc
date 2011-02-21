<?php
// $Id: convert.inc,v 1.17.4.1 2009/11/02 22:01:26 merlinofchaos Exp $
/**
 * @file convert.inc
 *
 * Contains forms and routines to convert older views to newer views.
 */

/**
 * Page callback for the tools - Views 1 convert page
 */
function views_ui_admin_convert() {
  if (!db_table_exists('view_view')) {
    return t('There are no Views 1 views stored in the database to convert.');
  }
  $items = array();
  $sorts = array();

  $header = array(
    array('data' => t('View name'), 'field' => 'name', 'sort' => 'asc'),
    array('data' => t('Description')),
    array('data' => t('Operations')),
  );
  $current_views = views_get_all_views();

  $result = db_query("SELECT v.* FROM {view_view} v");
  foreach ($result as $view) {
    $ops = array();
    if (!isset($current_views[$view->name])) {
      $ops[] = l(t('Convert'), "admin/structure/views1/convert/$view->name");
    }
    else {
      $ops[] = t('Converted');
    }
    $ops[] = l(t('Delete'), "admin/structure/views1/delete/$view->name");

    $item = array();
    $item[] = check_plain($view->name);
    $item[] = check_plain($view->description);
    $item[] = implode(' | ', $ops);
    $items[] = $item;

    $ts = tablesort_init($header);
    switch ($ts['sql']) {
      case 'name':
      default:
        $sorts[] = $item[0];
        break;
      case 'title':
        $sorts[] = $item[1];
        break;
    }
  }

  if (!empty($ts)) {
    if (strtolower($ts['sort']) == 'desc') {
      arsort($sorts);
    }
    else {
      asort($sorts);
    }
  }

  $i = array();
  foreach ($sorts as $id => $title) {
    $i[] = $items[$id];
  }
  $output = t('The table below lists Views version 1 views that are stored in the database. You can either convert them to work in Views version 2, or delete them. The views are convertible only if there is no Views 2 view with the same name.');
  $output .= theme('table', array('header' => $header, 'rows' => $i));

  $output .= drupal_get_form('views_ui_convert_cleanup_form');
  return $output;
}

/**
 * Provide form to clean up Views 1 tables.
 */
function views_ui_convert_cleanup_form() {
  $form['verify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Remove all Views 1 tables'),
    '#description' => t('Check this box and then click clean up to drop all Views 1 tables. Warning: this operation will not be reversible! Do this only if you are sure you no longer need this data.'),
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Clean up'),
  );

  return $form;
}

function views_ui_convert_cleanup_form_submit($form, $form_state) {
  if (empty($form_state['values']['verify'])) {
    drupal_set_message('Please check the box to verify you want to destroy your Views 1 table data.');
    return;
  }

  $ret = array();
  if (db_table_exists('view_view')) {
    db_drop_table($ret, 'view_view');
  }
  if (db_table_exists('view_sort')) {
    db_drop_table($ret, 'view_sort');
  }
  if (db_table_exists('view_argument')) {
    db_drop_table($ret, 'view_argument');
  }
  if (db_table_exists('view_tablefield')) {
    db_drop_table($ret, 'view_tablefield');
  }
  if (db_table_exists('view_filter')) {
    db_drop_table($ret, 'view_filter');
  }
  if (db_table_exists('view_exposed_filter')) {
    db_drop_table($ret, 'view_exposed_filter');
  }

  drupal_set_message(t('All Views 1 tables have been removed.'));
}

/**
 * Page callback for the tools - Views 1 convert page
 */
function views_ui_convert1($name) {
  $old = views1_load($name);
  if (!$old) {
    return t('Unable to find view.');
  }

  $view = views1_import($old);

  if ($view) {
    views_ui_cache_set($view);
    drupal_goto('admin/structure/views/edit/' . $view->name);
  }
  else {
    return t('Unable to convert view.');
  }
}

/**
 * Page to delete a Views 1 view.
 */
function views_ui_delete1_confirm($form, &$form_state, $vid) {
  $form_state['vid'] = $vid;
  $form = array();

  $cancel = 'admin/structure/views/tools/convert';
  if (!empty($_REQUEST['cancel'])) {
    $cancel = $_REQUEST['cancel'];
  }
  return confirm_form($form,
                  t('Are you sure you want to delete the view %name?', array('%name' => $view->name)),
                  $cancel,
                  t('This action cannot be undone.'),
                  t('Delete'),
                  t('Cancel'));
}

/**
 * Submit handler to delete a view.
 */
function views_ui_delete1_confirm_submit(&$form, &$form_state) {
  views1_delete($form_state['vid']);
  drupal_set_message(t('The view has been deleted'));
  $form_state['redirect'] = 'admin/structure/views/tools/convert';
}

/**
 * Convert a Views 1 view to a Views 2 view.
 */
function views1_import($imported) {
  $view = views_new_view();

  $view->name = $imported->name;
  $view->description = $imported->description;

  if (!empty($imported->page) && !empty($imported->url)) {
    $page_display = $view->add_display('page');
  }
  if (!empty($imported->block)) {
    $block_display = $view->add_display('block');
  }
  $view->init_display();

  $handler = &$view->display['default']->handler;
  $handler->set_option('title', $imported->page_title);
  $handler->set_option('header', $imported->page_header);
  $handler->set_option('header_format', $imported->page_header_format);
  $handler->set_option('footer', $imported->page_footer);
  $handler->set_option('footer_format', $imported->page_footer_format);
  $handler->set_option('empty', $imported->page_empty);
  $handler->set_option('empty_format', $imported->page_empty_format);

  $handler->set_option('use_pager', $imported->use_pager);
  $handler->set_option('items_per_page', $imported->nodes_per_page);
  $handler->set_option('pager_element', 0);
  $handler->set_option('offset', 0);

  $access = array('type' => 'none', 'role' => array(), 'perm' => '');
  if ($imported->access) {
    $access['type'] = 'role';
    $access['role'] = drupal_map_assoc($imported->access);
  }

  $handler->set_option('access', $access);
  if (!empty($imported->page) && !empty($imported->url)) {
    $handler = &$view->display[$page_display]->handler;
    $url = str_replace('$arg', '%', $imported->url);
    $handler->set_option('path', $url);
    if ($imported->menu) {
      $menu = array('type' => 'normal');
      if ($imported->menu_tab) {
        $menu['type'] = 'tab';
      }
      if ($imported->menu_tab_default) {
        $menu['type'] = 'default tab';
      }
      $menu['title'] = $imported->menu_title ? $imported->menu_title : $imported->page_title;
      $handler->set_option('menu', $menu);

      if ($menu['type'] == 'default tab') {
        $tab_options = array('type' => 'none');
        switch ($imported->menu_tab_default_parent_type) {
          case 'tab':
          case 'normal':
            $tab_options['type'] = $imported->menu_tab_default_parent_type;
            break;
        }
      }
      $tab_options['title'] = $imported->menu_parent_title;
      $tab_options['weight'] = $imported->menu_parent_tab_weight;
      $handler->set_option('tab_options', $tab_options);
    }
  }

  views1_convert_style($view, $handler, $imported->page_type);

  if (!empty($imported->block)) {
    $handler = &$view->display[$block_display]->handler;

    if (!empty($imported->block_title)) {
      if (!empty($imported->page)) {
        $handler->set_override('title');
      }
      $handler->set_option('title', $imported->block_title);
    }

    if (!empty($imported->page)) {
      $handler->set_override('use_pager');
    }
    $handler->set_option('use_pager', FALSE);

    if ($imported->nodes_per_block != $imported->nodes_per_page) {
      $handler->set_option('items_per_page', $imported->nodes_per_block);
      $handler->set_option('offset', 0);
    }

    if (empty($imported->block_use_page_header)) {
      if (!empty($imported->page)) {
        $handler->set_override('header');
      }
      if (!empty($imported->block_header)) {
        $handler->set_option('header', $imported->block_header);
        $handler->set_option('header_format', $imported->block_header_format);
      }
    }
    if (empty($imported->block_use_page_footer)) {
      if (!empty($imported->page)) {
        $handler->set_override('footer');
      }
      if (!empty($imported->block_footer)) {
        $handler->set_option('footer', $imported->block_footer);
        $handler->set_option('footer_format', $imported->block_footer_format);
      }
    }
    if (empty($imported->block_use_page_empty)) {
      if (!empty($imported->page)) {
        $handler->set_override('empty');
      }
      if (!empty($imported->block_empty)) {
        $handler->set_option('empty', $imported->block_empty);
        $handler->set_option('empty_format', $imported->block_empty_format);
      }
    }

    $handler->set_option('use_more', $imported->block_more);

    if (!empty($imported->page)) {
      $handler->set_override('style_plugin');
    }
    views1_convert_style($view, $handler, $imported->block_type);
  }

  // For each of the fields, arguments, filters, and sorts in the old view,
  // check if a handler for this item exists in Views 2 and add it,
  // then see if any other modules want to adapt it using hook_views_convert().

  foreach ($imported->field as $field) {
    $id = $view->add_item('default', 'field', $field['tablename'], $field['field'], array('label' => $field['label']));
    if ($view->display_handler->get_option('style_plugin') == 'table') {
      $options = $view->display_handler->get_option('style_options');
      if (!empty($field['sortable'])) {
        $options['info'][$id]['sortable'] = TRUE;
        if (!empty($field['defaultsort'])) {
          $options['default'] = $id;
        }
      }
      $view->display_handler->set_option('style_options', $options);
    }
    foreach (module_implements('views_convert') as $module) {
      module_invoke($module, 'views_convert', 'default', 'field', $view, $field, $id);
    }
  }
  foreach ($imported->sort as $field) {
    $id = $view->add_item('default', 'sort', $field['tablename'], $field['field'], array('order' => $field['sortorder']));
    foreach (module_implements('views_convert') as $module) {
      module_invoke($module, 'views_convert', 'default', 'sort', $view, $field, $id);
    }
  }
  $actions = array('ignore', 'not found', 'ignore', 'summary asc', 'summary asc', 'summary desc', 'summary asc', 'empty');
  foreach ($imported->argument as $id => $field) {
    if (!empty($imported->view_args_php)) {
      $field['argoptions']['default_action'] = 'default';
      $field['argoptions']['default_argument_type'] = 'php';
      $field['argoptions']['default_argument_php'] = '$args = eval(\''. str_replace("'", "\\'", $imported->view_args_php) .'\');'."\n";
      $field['argoptions']['default_argument_php'] .= 'if (isset($args['. $field['position'] .'])) {'."\n";
      $field['argoptions']['default_argument_php'] .= '  return $args['. $field['position'] .'];'."\n";
      $field['argoptions']['default_argument_php'] .= '}';
      $field['argoptions']['validate_fail'] = $actions[$field['argdefault']];
    }
    else {
      $field['argoptions']['default_action'] = $actions[$field['argdefault']];
    }
    if (!empty($field['title'])) {
      $field['argoptions']['title'] = $field['title'];
    }
    if (!empty($field['wildcard'])) {
      $field['argoptions']['wildcard'] = $field['wildcard'];
    }
    if (!empty($field['wildcard_substitution'])) {
      $field['argoptions']['wildcard_substitution'] = $field['wildcard_substitution'];
    }
    // Arguments didn't used to be identified by table.name so we just have to
    // leave that out.
    foreach (module_implements('views_convert') as $module) {
      module_invoke($module, 'views_convert', 'default', 'argument', $view, $field, NULL);
    }
  }
  foreach ($imported->filter as $field) {
    $options = $field['value'] == '' ? array() : array('value' => $field['value']);
    $id = $view->add_item('default', 'filter', $field['tablename'], $field['field'], $options);
    foreach (module_implements('views_convert') as $module) {
      module_invoke($module, 'views_convert', 'default', 'filter', $view, $field, $id);
    }
  }
  // Exposed filters now get added to the filter array, not as a separate array.
  $count = 0;
  foreach ($imported->exposed_filter as $field) {
    list(, $id) = explode('.', $field['field'], 2);
    $item = $view->get_item('default', 'filter', $id);
    if (views_get_handler($item['table'], $item['field'], 'filter')) {
      $item['exposed'] = TRUE;

      // Use the count to emulate the old, hardcoded filter naming.
      $item['expose']['identifier'] = 'filter' . $count;
      $item['expose']['label'] = $field['label'];
      $item['expose']['operator'] = $field['operator'] ? 'op' . $count : '';
      $item['expose']['optional'] = $field['optional'];
      $item['expose']['single'] = $field['single'];
      $view->set_item('default', 'filter', $id, $item);
    }
    $count++;
    foreach (module_implements('views_convert') as $module) {
      module_invoke($module, 'views_convert', 'default', 'exposed_filter', $view, $field, $id);
    }
  }

  return $view;
}

function views1_convert_style(&$view, &$handler, $type) {
  switch ($type) {
    case 'list':
      $handler->set_option('style_plugin', 'list');
      $handler->set_option('style_options', array('type' => 'ul'));
      $handler->set_option('row_plugin', 'fields');
      break;
    case 'node':
      $handler->set_option('row_plugin', 'node');
      $handler->set_option('row_options', array('teaser' => FALSE, 'links' => TRUE));
      break;
    case 'teaser':
      $handler->set_option('row_plugin', 'node');
      $handler->set_option('row_options', array('teaser' => TRUE, 'links' => TRUE));
      break;
    case 'table':
      $options = array();
      $options['columns'] = array();
      $options['default'] = '';
      $options['info'] = array();
      $options['override'] = FALSE;
      $options['order'] = 'asc';

      $handler->set_option('style_plugin', 'table');
      $handler->set_option('style_options', $options);
      break;
    default:
      // Ask around if anybody else knows.
      foreach (module_implements('views_convert') as $module) {
        module_invoke($module, 'views_convert', $handler->display->id, 'style', $view, $type);
      }
  }
}
/**
 * Load a version 1 view from the database.
 *
 */
function views1_load($arg) {
  static $cache = array();
  $which = is_numeric($arg) ? 'vid' : 'name';
  if (isset($cache[$which][$arg])) {
    return $cache[$which][$arg];
  }

  $where = (is_numeric($arg) ? "v.vid =  %d" : "v.name = '%s'");
  $view = db_fetch_object(db_query("SELECT v.* FROM {view_view} v WHERE $where", $arg));

  if (!$view->name) {
    return NULL;
  }

  $view->access = ($view->access ? explode(', ', $view->access) : array());

  // load the sorting criteria too.
  $result = db_query("SELECT * FROM {view_sort} vs WHERE vid = $view->vid ORDER BY position ASC");

  $view->sort = array();
  while ($sort = db_fetch_array($result)) {
    if (substr($sort['field'], 0, 2) == 'n.') {
      $sort['field'] = 'node' . substr($sort['field'], 1);
    }
    $sort['id'] = $sort['field'];
    $bits = explode('.', $sort['field']);
    $sort['tablename'] = $bits[0];
    $sort['field'] = $bits[1];
    $view->sort[$sort['position']] = $sort;
  }

  $result = db_query("SELECT * FROM {view_argument} WHERE vid = $view->vid ORDER BY position ASC");

  $view->argument = array();
  while ($arg = db_fetch_array($result)) {
    $arg['id'] = $arg['type'];
    $view->argument[$arg['position']] = $arg;
  }

  $result = db_query("SELECT * FROM {view_tablefield} WHERE vid = $view->vid ORDER BY position ASC");

  $view->field = array();
  while ($arg = db_fetch_array($result)) {
    if ($arg['tablename'] == 'n') {
      $arg['tablename'] = 'node';
    }
    $arg['id'] = $arg['fullname'] = "$arg[tablename].$arg[field]";
    $arg['queryname'] = "$arg[tablename]_$arg[field]";
    $view->field[] = $arg;
  }

  $result = db_query("SELECT * FROM {view_filter} WHERE vid = $view->vid ORDER BY position ASC");

  // TODO - Is it safe to ignore this $filters variable? This function depends
  // on lots of additional code needed to call hook_implements and construct
  // all the views tables, so using it will add a lot of code to this file.
  //$filters = _views_get_filters();
  $view->filter = array();
  while ($filter = db_fetch_array($result)) {
    if (substr($filter['field'], 0, 2) == 'n.') {
      $filter['field'] = 'node' . substr($filter['field'], 1);
    }

    if ($filter['operator'] == 'AND' ||
        $filter['operator'] == 'OR' ||
        $filter['operator'] == 'NOR') {
        // TODO - need another way to identify this type of filter
        // without being able to call hook_implements().
        //|| $filters[$filter['field']]['value-type'] == 'array' ) {
      if ($filter['value'] !== NULL && $filter['value'] !== '') {
        $filter['value'] = explode(',', $filter['value']);
      }
      else {
        $filter['value'] = array();
      }
    }
    $filter['id'] = $filter['field'];
    $bits = explode('.', $filter['field']);
    $filter['tablename'] = $bits[0];
    $filter['field'] = $bits[1];
    $view->filter[$filter['position']] = $filter;
  }

  $result = db_query("SELECT * FROM {view_exposed_filter} WHERE vid = $view->vid ORDER BY position ASC");

  $view->exposed_filter = array();
  while ($arg = db_fetch_array($result)) {
    $arg['id'] = $arg['field'];
    $view->exposed_filter[] = $arg;
  }

  $cache['vid'][$view->vid] = $view;
  $cache['name'][$view->name] = $view;

  return $view;
}

/**
 * Delete a version 1 view from the database.
 *
 */
function views1_delete($arg) {
  static $cache = array();
  $where = (is_numeric($arg) ? "v.vid =  %d" : "v.name = '%s'");
  $view = db_fetch_object(db_query("SELECT v.* FROM {view_view} v WHERE $where", $arg));

  if (!$view->name) {
    return NULL;
  }



  db_delete('view_sort')
    ->condition('vid', $view->vid)
    ->execute();
  db_delete('view_argument')
    ->condition('vid', $view->vid)
    ->execute();
  db_delete('view_tablefield')
    ->condition('vid', $view->vid)
    ->execute();
  db_delete('view_filter')
    ->condition('vid', $view->vid)
    ->execute();
  db_delete('view_exposed_filter')
    ->condition('vid', $view->vid)
    ->execute();
  db_delete('view_view')
    ->condition('vid', $view->vid)
    ->execute();
}

