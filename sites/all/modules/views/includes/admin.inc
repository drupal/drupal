<?php
// $Id: admin.inc,v 1.161.4.86 2011/01/04 23:54:35 dereine Exp $
/**
 * @file admin.inc
 * Provides the Views' administrative interface.
 */

/**
 * Page callback to list views in the system.
 */
function views_ui_list_views($arg = NULL) {
  if ($arg != NULL) {
    return drupal_not_found();
  }

  $output = theme('views_ui_list_views');
  views_ui_check_advanced_help();
  return $output;
}

/**
 * Check to see if the advanced help module is installed, and if not put up
 * a message.
 *
 * Only call this function if the user is already in a position for this to
 * be useful.
 */
function views_ui_check_advanced_help() {
  if (variable_get('views_hide_help_message', FALSE)) {
    return;
  }

  if (!module_exists('advanced_help')) {
    $filename = db_query_range("SELECT filename FROM {system} WHERE type = 'module' AND name = 'advanced_help'", 0, 1)
      ->fetchField();
    if ($filename && file_exists($filename)) {
      drupal_set_message(t('If you <a href="@modules">enable the advanced help module</a>, Views will provide more and better help. <a href="@hide">Hide this message.</a>', array('@modules' => url('admin/modules'),'@hide' => url('admin/structure/views/tools'))));
    }
    else {
      drupal_set_message(t('If you install the advanced help module from !href, Views will provide more and better help. <a href="@hide">Hide this message.</a>', array('!href' => l('http://drupal.org/project/advanced_help', 'http://drupal.org/project/advanced_help'), '@hide' => url('admin/structure/views/tools'))));
    }
  }
}

/**
 * Preprocess the list views theme
 */
function template_preprocess_views_ui_list_views(&$vars) {
  $items = array();
  $sorts = array();

  $views = views_get_all_views();

  // Respond to a reset command by clearing session and doing a drupal goto
  // back to the base URL.
  if (isset($_GET['op']) && $_GET['op'] == t('Reset')) {
    unset($_SESSION['views']['#admin']);
    drupal_goto('admin/structure/views');
  }
  if (count($_GET) <= 1) {
    if (isset($_SESSION['views']['#admin']) && is_array($_SESSION['views']['#admin'])) {
      $_GET += $_SESSION['views']['#admin'];
    }
  }
  else {
    $_SESSION['views']['#admin'] = $_GET;
    unset($_SESSION['views']['#admin']['q']);
  }

  $form_state = array(
    'views' => $views,
    'method' => 'get',
    'rerender' => TRUE,
    'no_redirect' => TRUE,
    'always_process' => TRUE,
    'build_info' => array(
      'args' => array(),
    ),
  );

  $vars['widgets'] = drupal_build_form('views_ui_list_views_form', $form_state);
  unset($vars['widgets']['form_id']);
  unset($vars['widgets']['form_build_id']);
  $vars['widgets'] = drupal_render($vars['widgets']);

  $vars['help_type_icon'] = '';
  if (module_exists('advanced_help')) {
    $vars['help_type_icon'] = theme('advanced_help_topic', array('module' => 'views', 'topic' => 'view-type'));
  }

  $base_tables = views_fetch_base_tables();

  foreach ($views as $view) {
    if ($form_state['values']['tag'] != 'all') {
      if ($form_state['values']['tag'] == 'none') {
        if (!empty($view->tag)) {
          continue;
        }
      }
      elseif ($form_state['values']['tag'] != $view->tag) {
        continue;
      }
    }
    if ($form_state['values']['type'] != 'all' && $form_state['values']['type'] != $view->type) {
      continue;
    }

    if ($form_state['values']['base'] != 'all' && $form_state['values']['base'] != $view->base_table) {
      continue;
    }

    if ($form_state['values']['display'] != 'all' && empty($view->display[$form_state['values']['display']])) {
      continue;
    }

    if ($form_state['values']['status'] != 'all' && (!empty($view->disabled) == $form_state['values']['status'])) {
      continue;
    }

    $item = new stdClass();
    $item->ops = array();

    if (empty($view->disabled)) {
      $item->ops[] = l(t('Edit'), "admin/structure/views/edit/$view->name");
      $item->ops[] = l(t('Export'), "admin/structure/views/export/$view->name");
      $item->ops[] = l(t('Clone'), "admin/structure/views/clone/$view->name");
    }
    if ($view->type != t('Default')) {
      $text = $view->type == t('Overridden') ? t('Revert') : t('Delete');
      $item->ops[] = l($text, "admin/structure/views/delete/$view->name");
    }
    else {
      if (empty($view->disabled)) {
        $item->ops[] = l(t('Disable'), "admin/structure/views/disable/$view->name", array('query' => drupal_get_destination()));
      }
      else {
        $item->ops[] = l(t('Enable'), "admin/structure/views/enable/$view->name", array('query' => drupal_get_destination()));
      }
    }

    $item->ops = implode(' | ', $item->ops);
    if (empty($view->display)) {
      $item->path = t('Warning! Broken view!');
    }
    else {
      $view->init_display();   // Make sure all the handlers are set up
      $all_paths = array();
      foreach ($view->display as $display) {
        if (!empty($display->handler) && $display->handler->has_path()) {
          $one_path = $display->handler->get_option('path');
          if (empty($path_sort)) {
            $path_sort = strtolower($one_path);
          }
          if (empty($view->disabled) && strpos($one_path, '%') === FALSE) {
            $all_paths[] = l($one_path, $one_path);
          }
          else {
            $all_paths[] = check_plain($one_path);
          }
        }
      }
      if (!empty($all_paths)) {
        $item->path = implode(', ', array_unique($all_paths));
      }
    }

    $item->type = $view->type;
    $item->name = $view->name;

    if (!empty($view->tag)) {
      $item->tag = $view->tag;
    }

    $item->title = $view->get_title();
    $item->base = !empty($base_tables[$view->base_table]['title']) ? $base_tables[$view->base_table]['title'] : t('Broken');

    $item->displays = array();
    foreach ($view->display as $display) {
      if (!empty($display->handler->definition['admin'])) {
        $item->displays[$display->handler->definition['admin']] = TRUE;
      }
    }

    if ($item->displays) {
      ksort($item->displays);
      $item->displays = implode(', ', array_keys($item->displays));
    }

    $item->description = check_plain($view->description);
    $item->classes = empty($view->disabled) ? 'view-enabled' : 'view-disabled';
    $items[] = $item;

    $sort = intval(empty($view->disabled) xor $form_state['values']['sort'] == 'asc');

    switch ($form_state['values']['order']) {
      case 'name':
      default:
        $sort .= strtolower($view->name);
        break;
      case 'title':
        $sort .= strtolower($item->title);
        break;
      case 'path':
        $sort .= strtolower($raw_path); // $path;
        break;
      case 'type':
        $sort .= $view->type . $view->name;
        break;
      case 'tag':
        $sort .= strtolower($view->tag);
        break;
      case 'desc':
        $sort .= strtolower($view->description);
        break;
    }

    $sorts[] = $sort;
  }

  if ($form_state['values']['sort'] == 'desc') {
    arsort($sorts);
  }
  else {
    asort($sorts);
  }

  $i = array();
  foreach ($sorts as $id => $title) {
    $i[] = $items[$id];
  }

  views_add_css('views-list');
  $vars['views'] = $i;

  $getting_started = '';
  if (module_exists('advanced_help')) {
    $getting_started = theme('advanced_help_topic', array('module' => 'views', 'topic' => 'getting-started', 'type' => 'title'));
  }
  if (!$getting_started) {
    $getting_started = t('Install the advanced help module for the getting started');
  }

  $vars['help'] = t('Not sure what to do? Try the "!getting-started" page.', array('!getting-started' => $getting_started));
}

/**
 * Provide a form for sorting and filtering the list of views.
 */
function views_ui_list_views_form($form, &$form_state) {
  if (!variable_get('clean_url', FALSE)) {
    $form['q'] = array(
      '#type' => 'hidden',
      '#value' => $_GET['q'],
    );
  }

  $all = array('all' => t('- All -'));
  $none = array('none' => t('- None -'));

  $form['type'] = array(
    '#type' => 'select',
    '#title' => t('Storage'),
    '#options' => array(
      'all' => t('- All -'),
      t('Normal') => t('Normal'),
      t('Default') => t('Default'),
      t('Overridden') => t('Overridden'),
    ),
    '#default_value' => 'all',
  );

  $status = array(
    '0' => t('Disabled'),
    '1' => t('Enabled'),
  );
  $form['status'] = array(
    '#type' => 'select',
    '#title' => t('Status'),
    '#options' => array_merge($all, $status),
    '#default_value' => 'all',
  );

  $bases = array();
  foreach (views_fetch_base_tables() as $table => $info) {
    $bases[$table] = $info['title'];
  }

  $form['base'] = array(
    '#type' => 'select',
    '#title' => t('Type'),
    '#options' => array_merge($all, $bases),
    '#default_value' => 'all',
  );

  $tags = array();

  $extras = array();
  if (isset($form_state['views'])) {
    foreach ($form_state['views'] as $name => $view) {
      if (!empty($view->tag)) {
        $tags[$view->tag] = $view->tag;
      }
    }
  }

  asort($tags);

  $form['tag'] = array(
    '#type' => 'select',
    '#title' => t('Tag'),
    '#options' => array_merge($all, $none, $tags),
    '#default_value' => 'all',
  );

  $displays = array();
  foreach (views_fetch_plugin_data('display') as $id => $info) {
    if (!empty($info['admin'])) {
      $displays[$id] = $info['admin'];
    }
  }

  asort($displays);

  $form['display'] = array(
    '#type' => 'select',
    '#title' => t('Displays'),
    '#options' => array_merge($all, $displays),
    '#default_value' => 'all',
  );

  $form['order'] = array(
    '#type' => 'select',
    '#title' => t('Sort by'),
    '#options' => array(
      'name' => t('Name'),
      'title' => t('Title'),
      'tag' => t('Tag'),
      'path' => t('Path'),
      'type' => t('Type'),
      'desc' => t('Description'),
    ),
    '#default_value' => 'name',
  );

  $form['sort'] = array(
    '#type' => 'select',
    '#title' => t('Order'),
    '#options' => array(
      'asc' => t('Up'),
      'desc' => t('Down'),
    ),
    '#default_value' => 'asc',
  );

  // Autosubmit all form elements.
  ctools_add_js('auto-submit');
  $form['#attributes']['class'][] = 'ctools-auto-submit-full-form';

  $form['submit'] = array(
    '#name' => '', // so it won't in the $_GET args
    '#type' => 'submit',
    '#id' => 'edit-views-apply',
    '#value' => t('Apply'),
    '#attributes' => array('class' => array('ctools-use-ajax', 'ctools-auto-submit-click')),
  );

  if (!empty($_SESSION['views']['#admin'])) {
    $form['reset'] = array(
      '#type' => 'submit',
      '#id' => 'edit-views-reset',
      '#value' => t('Reset'),
    );
  }
  $form['#token'] = FALSE;

  return $form;
}

/**
 * Page callback for the live preview.
 *
 * @todo make this use a template
 */
function views_ui_preview($js, $view) {
  // Take off the items we know so that we can have just the args passed
  // in for later use.
  $func_args = func_get_args();
  array_shift($func_args); // $js
  array_shift($func_args); // $view
  $display_id = (count($func_args)) ? array_shift($func_args) : 'default';

  $form_state = array(
    'display_id' => $display_id,
    'view_args' => $func_args ? implode('/', $func_args) : '',
    'rerender' => TRUE,
    'no_redirect' => TRUE,
    'view' => &$view,
    'ajax' => $js,
    'build_info' => array(
      'args' => array(),
    ),
  );

  $form = drupal_build_form('views_ui_preview_form', $form_state);
  $output = drupal_render($form);
  $args = array();
  if (isset($form_state['view_args']) && $form_state['view_args'] !== '') {
    $args = explode('/', $form_state['view_args']);
  }

  $errors = $view->validate();
  if ($errors === TRUE) {
    $view->ajax = $js;
    $view->live_preview = TRUE;
    $view->set_exposed_input($_POST);

    // Store the current view URL for later use:
    $view->set_display($form_state['display_id']);
    $view->set_arguments($args);

    if ($view->display_handler->get_option('path')) {
      $path = $view->get_url();
    }

    // Make view links come back to preview.
    $view->override_path = 'admin/structure/views/nojs/preview/' . $view->name . '/' . $form_state['display_id'];

    // also override $_GET['q'] so we get the pager
    $_GET['q'] = $view->override_path;
    if ($form_state['view_args']) {
      $_GET['q'] .= '/' . $form_state['view_args'];
    }

    $preview = $view->preview($form_state['display_id'], $args);

    // Get information from the preview for display.
    if (!empty($view->build_info['query'])) {
      $rows = array();
      $query = $view->build_info['query'];
      // Only the sql default class has a method getArguments.
      $quoted = array();

      if (get_class($view->query) == 'views_plugin_query_default') {
        $arguments = $query->getArguments();
        $connection = Database::getConnection();
        foreach ((array)$query->arguments() as $key => $val) {
          $quoted[$key] = $connection->quote($val);
        }
      }
      $rows[] = array('<strong>' . t('Query') . '</strong>', '<pre>' . check_plain(strtr($query, $quoted)) . '</pre>');
      if (!empty($view->additional_queries)) {
        $queries = '<strong>' . t('These queries were run during view rendering:') . '</strong>';
        foreach ($view->additional_queries as $query) {
          if ($queries) {
            $queries .= "\n";
          }
          $queries .= t('[@time ms]', array('@time' => intval($query[1] * 100000) / 100)) . ' ' . $query[0];
        }

        $rows[] = array('<strong>' . t('Other queries') . '</strong>', '<pre>' . $queries . '</pre>');
      }

      $rows[] = array('<strong>' . t('Title') . '</strong>', filter_xss_admin($view->get_title()));
      if (isset($path)) {
        $path = l($path, $path);
      }
      else {
        $path = t('This display has no path.');
      }

      $rows[] = array('<strong>' . t('Path') . '</strong>', $path);

      $rows[] = array('<strong>' . t('Query build time') . '</strong>', t('@time ms', array('@time' => intval($view->build_time * 100000) / 100)));
      $rows[] = array('<strong>' . t('Query execute time') . '</strong>', t('@time ms', array('@time' => intval($view->execute_time * 100000) / 100)));
      $rows[] = array('<strong>' . t('View render time') . '</strong>', t('@time ms', array('@time' => intval($view->render_time * 100000) / 100)));
      drupal_alter('views_preview_info', $rows, $view);

      $info = theme('table', array('rows' => $rows));
    }
    else {
      $info = theme('table', array('rows' => array(array('<strong>' . t('Query') . '</strong>', t('No query was run')))));
    }
  }
  else {
    foreach ($errors as $error) {
      drupal_set_message($error, 'error');
    }
    $preview = t('Unable to preview due to validation errors.');
    $info = '';
  }

  $info = '<div class="views-query-info">' . $info . '</div>';

  if (variable_get('views_ui_query_on_top', FALSE)) {
    $output .= $info . $preview;
  }
  else {
    $output .= $preview . $info;
  }

  if (!$js) {
    views_add_css('views-admin');
    drupal_set_title($view->get_title());
    return $output;
  }
  else {
    $commands = array();
    if (!empty($view->js_settings)) {
      $commands[] = ajax_command_settings($view->js_settings);
    }
    $display = '';
    if ($messages = theme('status_messages')) {
      $display = '<div class="views-messages">' . $messages . '</div>';
    }
    $display .= $output;
    if ($display) {
      $commands[] = ajax_command_html('div#views-live-preview', $display);
    }
    return $js ? array('#type' => 'ajax', '#commands' => $commands) : $commands;
  }
}

/**
 * Form for generating argument information for the live preview.
 */
function views_ui_preview_form($form, &$form_state) {
  $view = &$form_state['view'];
  $view->init_display();
  $options = array();
  foreach ($view->display as $id => $display) {
    $options[$id] = $display->display_title;
  }

  $form['#attributes'] = array(
    'class' => array('clearfix'),
  );

  $form['display_id'] = array(
    '#type' => 'select',
    '#title' => t('Display'),
    '#options' => $options,
    '#default_value' => $form_state['display_id'],
    '#id' => 'preview-display-id',
  );

  $form['args'] = array(
    '#type' => 'textfield',
    '#title' => t('Arguments'),
    '#default_value' => $form_state['view_args'],
    '#description' => t('Separate arguments with a / as though they were a URL path.'),
    '#id' => 'preview-args',
  );

  $form['preview'] = array(
    '#type' => 'submit',
    '#value' => t('Preview'),
    '#id' => 'preview-submit',
  );

  $form['live_preview'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatic live preview'),
    '#default_value' => !variable_get('views_ui_disable_live_preview', 0),
  );

  $form['#action'] = url("admin/structure/views/nojs/preview/$view->name");
  return $form;
}

/**
 * Submit the preview form.
 *
 * This just takes the data and stores it on the form state in a
 * known location. The caller will be responsible for using it.
 */
function views_ui_preview_form_submit(&$form, &$form_state) {
  $form_state['display_id'] = $form_state['values']['display_id'];
  $form_state['view_args'] = $form_state['values']['args'];
}

/**
 * Page callback to add a new view.
 */
function views_ui_add_page() {
  $form_state = array(
    'view' => NULL,
    'build_info' => array(
      'args' => array(),
    ),
  );

  $form = drupal_build_form('views_ui_add_form', $form_state);
  return drupal_render($form);
}

/**
 * Page callback to add a new view.
 */
function views_ui_clone_page($view) {
  $form_state = array(
    'view' => $view->copy(),
    'build_info' => array(
      'args' => array(),
    ),
  );

  drupal_set_title(t('Clone view %view', array('%view' => $view->name)), PASS_THROUGH);
  return drupal_render(drupal_build_form('views_ui_add_form', $form_state));
}

/**
 * Form constructor callback to create the views Add Form, phase 1.
 */
function views_ui_add_form($form, &$form_state) {
  $view = $form_state['view'];
  $form = array();

  $form['human_name'] = array(
    '#type' => 'textfield',
    '#title' => t('View name'),
    '#description' => t('A descriptive name for this view. Spaces are allowed.'),
    '#default_value' => $view ? $view->human_name : '',
    '#required' => TRUE,
    '#size' => 32,
    '#maxlength' => 255,
  );

  $form['name'] = array(
    '#type' => 'machine_name',
    '#maxlength' => 32,
    '#machine_name' => array(
      'exists' => 'views_get_view',
      'source' => array('human_name'),
    ),
    '#description' => t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
  );

  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('View description'),
    '#description' => t('This description will appear on the Views administrative UI to tell you what the view is about.'),
    '#default_value' => $view ? $view->description : '',
    '#maxlength' => 255,
  );

  $form['tag'] = array(
    '#type' => 'textfield',
    '#title' => t('View tag'),
    '#description' => t('Enter an optional tag for this view; it is used only to help sort views on the administrative page.'),
    '#default_value' => $view ? $view->tag : '',
    '#autocomplete_path' => 'admin/views/ajax/autocomplete/tag',
  );

  $base_tables = array();
  foreach (views_fetch_base_tables() as $table => $info) {
    $base_tables[$table] = $info['title'] . '<div class="description">' . $info['description'] . '</div>';
  }

  $form['base_table'] = array(
    '#type' => 'radios',
    '#title' => t('View type'),
    '#description' => t('The view type is the primary table for which information is being retrieved. The view type controls what arguments, fields, sort criteria and filters are available, so once this is set it <strong>cannot be changed</strong>.'),
    '#default_value' => $view ? $view->base_table : 'node',
    '#options' => $base_tables,
  );

  if ($view) {
    $form['base_table']['#disabled'] = TRUE;
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
    '#validate' => array('views_ui_add_form_validate'),
    '#submit' => array('views_ui_add_form_submit'),
  );

  return $form;
}

/**
 * Validate the add view form.
 */
function views_ui_add_form_validate($form, &$form_state) {
  $name = $form_state['values']['name'];

  // View name must be alphanumeric or underscores, no other punctuation.
  if (preg_match('/[^a-zA-Z0-9_]/', $name) || is_numeric($name)) {
    form_error($form['name'], t('View name must be alphanumeric or underscores only, but cannot be numeric.'));
  }

  // View name must already exist.
  $view = views_get_view($form_state['values']['name']);
  if ($view && $view->type != t('Default')) {
    form_error($form['name'], t('You must use a unique name for this view.'));
  }
}

/**
 * Process the add view form
 */
function views_ui_add_form_submit($form, &$form_state) {
  $view = $form_state['view'] ? $form_state['view'] : views_new_view();
  $view->name = $form_state['values']['name'];
  $view->human_name = $form_state['values']['human_name'];
  $view->description = $form_state['values']['description'];
  $view->tag = $form_state['values']['tag'];
  $view->core = VERSION;
  if (empty($form['base_table']['#disabled'])) {
    $view->base_table = $form_state['values']['base_table'];
  }

  views_ui_cache_set($view);
  $form_state['redirect'] ='admin/structure/views/edit/' . $view->name;
}

/**
 * Page to delete a view.
 */
function views_ui_delete_confirm($form, &$form_state, $view) {
  $form_state['view'] = &$view;
  $form = array();

  $cancel = 'admin/structure/views';
  if (!empty($_REQUEST['cancel'])) {
    $cancel = $_REQUEST['cancel'];
  }

  if ($view->type == t('Overridden')) {
    $title = t('Are you sure you want to revert the view %name?', array('%name' => $view->name));
    $desc = t('Reverting the view will delete the view that is in the database, reverting it to the original default view. Any changes you have made will be lost and cannot be recovered.');
    $button = t('Revert');
  }
  else {
    $title = t('Are you sure you want to delete the view %name?', array('%name' => $view->name));
    $desc = t('Deleting a view cannot be undone.');
    $button = t('Delete');
  }

  return confirm_form($form,
                  $title,
                  $cancel,
                  $desc,
                  $button,
                  t('Cancel'));
}

/**
 * Submit handler to delete a view.
 */
function views_ui_delete_confirm_submit(&$form, &$form_state) {
  $form_state['view']->delete();
  ctools_object_cache_clear('view', $form_state['view']->name);
  drupal_set_message(t('The view has been deleted.'));
  $form_state['redirect'] = 'admin/structure/views';
}

/**
 * Page to delete a view.
 */
function views_ui_break_lock_confirm($form, &$form_state, $view) {
  $form_state['view'] = &$view;
  $form = array();

  if (empty($view->locked)) {
    return t('There is no lock on view %view to break.', array('%name' => $view->name));
  }

  $cancel = 'admin/structure/views/edit/' . $view->name;
  if (!empty($_REQUEST['cancel'])) {
    $cancel = $_REQUEST['cancel'];
  }

  $account = user_load($view->locked->uid);
  return confirm_form($form,
                  t('Are you sure you want to break the lock on view %name?',
                  array('%name' => $view->name)),
                  $cancel,
                  t('By breaking this lock, any unsaved changes made by !user will be lost!', array('!user' => theme('username', array('account' => $account)))),
                  t('Break lock'),
                  t('Cancel'));
}

/**
 * Submit handler to break_lock a view.
 */
function views_ui_break_lock_confirm_submit(&$form, &$form_state) {
  ctools_object_cache_clear_all('view', $form_state['view']->name);
  $form_state['redirect'] = 'admin/structure/views/edit/' . $form_state['view']->name;
  drupal_set_message(t('The lock has been broken and you may now edit this view.'));
}

/**
 * The main view edit page
 */
function views_ui_edit_page($view) {
  drupal_set_title(t('Edit view %view', array('%view' => $view->name)), PASS_THROUGH);
  $output = theme('views_ui_edit_view', array('view' => $view));
  views_ui_check_advanced_help();
  return $output;
}

/**
 * Export a view for cut & paste.
 */
function views_ui_export_page($form, &$form_state, $view) {
  $code = $view->export();
  $lines = substr_count($code, "\n");
  $form['code'] = array(
    '#type' => 'textarea',
    '#title' => $view->name,
    '#default_value' => $code,
    '#rows' => $lines,
  );
  return $form;
}

/**
 * Import a view from cut & paste
 */
function views_ui_import_page($form, &$form_state) {
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('View name'),
    '#description' => t('Enter the name to use for this view if it is different from the source view. Leave blank to use the name of the view.'),
  );

  $form['view'] = array(
    '#type' => 'textarea',
    '#title' => t('Paste view code here'),
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
    '#submit' => array('views_ui_import_submit'),
    '#validate' => array('views_ui_import_validate'),
  );
  return $form;
}

/**
 * Validate handler to import a view
 */
function views_ui_import_validate($form, &$form_state) {
  $view = '';
  views_include('view');
  ob_start();
  eval($form_state['values']['view']);
  ob_end_clean();

  if (!is_object($view)) {
    return form_error($form['view'], t('Unable to interpret view code.'));
  }

  if (empty($view->api_version) || $view->api_version < 2) {
    // Check for some value that would only exist on a Views 1 view.
    if (isset($view->url) || isset($view->page) || isset($view->block)) {
      views_include('convert');
      $view = views1_import($view);
      drupal_set_message(t('You are importing a view created in Views version 1. You may need to adjust some parameters to work correctly in version 2.'), 'warning');
    }
    else {
      form_error($form['view'], t('That view is not compatible with this version of Views.'));
    }
  }
  elseif ($view->api_version > views_api_version()) {
    form_error($form['view'], t('That view is created for the version @import_version of views, but you only have @api_version', array(
      '@import_version' => $view->api_version,
      '@api_version' => views_api_version())));
  }

  // View name must be alphanumeric or underscores, no other punctuation.
  if (!empty($form_state['values']['name']) && preg_match('/[^a-zA-Z0-9_]/', $form_state['values']['name'])) {
    form_error($form['name'], t('View name must be alphanumeric or underscores only.'));
  }

  if ($form_state['values']['name']) {
    $view->name = $form_state['values']['name'];
  }

  $test = views_get_view($view->name);
  if ($test && $test->type != t('Default')) {
    form_set_error('', t('A view by that name already exists; please choose a different name'));
  }

  $view->init_display();

  $broken = FALSE;
  // Make sure that all plugins and handlers needed by this view actually exist.
  foreach ($view->display as $id => $display) {
    if (empty($display->handler) || !empty($display->handler->broken)) {
      drupal_set_message(t('Display plugin @plugin is not available.', array('@plugin' => $display->display_plugin)), 'error');
      $broken = TRUE;
      continue;
    }

    $plugin = views_get_plugin('style', $display->handler->get_option('style_plugin'));
    if (!$plugin) {
      drupal_set_message(t('Style plugin @plugin is not available.', array('@plugin' => $display->handler->get_option('style_plugin'))), 'error');
      $broken = TRUE;
    }
    elseif ($plugin->uses_row_plugin()) {
      $plugin = views_get_plugin('row', $display->handler->get_option('row_plugin'));
      if (!$plugin) {
        drupal_set_message(t('Row plugin @plugin is not available.', array('@plugin' => $display->handler->get_option('row_plugin'))), 'error');
        $broken = TRUE;
      }
    }

    foreach (views_object_types() as $type => $info) {
      $handlers = $display->handler->get_handlers($type);
      if ($handlers) {
        foreach ($handlers as $id => $handler) {
          if ($handler->broken()) {
            drupal_set_message(t('@type handler @table.@field is not available.', array(
              '@type' => $info['stitle'],
              '@table' => $handler->table,
              '@field' => $handler->field,
            )), 'error');
            $broken = TRUE;
          }
        }
      }
    }
  }

  if ($broken) {
    form_set_error('', t('Unable to import view.'));
  }

  $form_state['view'] = &$view;
}

/**
 * Submit handler for view import
 */
function views_ui_import_submit($form, &$form_state) {
  // Store in cache and then go to edit.
  views_ui_cache_set($form_state['view']);
  $form_state['redirect'] = 'admin/structure/views/edit/' . $form_state['view']->name;
}

/**
 * The main edit view form, which is really just a save/cancel/delete button.
 */
function views_ui_edit_view_form($form, &$form_state, $view) {
  $form['buttons']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#validate' => array('views_ui_edit_view_form_validate'),
    '#submit' => array('views_ui_edit_view_form_submit'),
  );

  $form['buttons']['cancel'] = array(
    '#type' => 'submit',
    '#value' => t('Cancel'),
    '#submit' => array('views_ui_edit_view_form_cancel'),
  );

  if (is_numeric($view->vid)) {
    $form['buttons']['delete'] = array(
      '#type' => 'submit',
      '#value' => $view->type == t('Overridden') ? t('Revert') : t('Delete'),
      '#submit' => array('views_ui_edit_view_form_delete'),
    );
  }

  $form_state['view'] = &$view;
  return $form;
}

/**
 * Validate that a view is complete and whole.
 */
function views_ui_edit_view_form_validate($form, &$form_state) {
  // Do not validate cancel or delete or revert.
  if (empty($form_state['clicked_button']['#value']) || $form_state['clicked_button']['#value'] != t('Save')) {
    return;
  }

  $errors = $form_state['view']->validate();
  if ($errors !== TRUE) {
    foreach ($errors as $error) {
      form_set_error('', $error);
    }
  }
}

/**
 * Submit handler for the edit view form.
 */
function views_ui_edit_view_form_submit($form, &$form_state) {
  // Go through and remove displayed scheduled for removal.
  foreach ($form_state['view']->display as $id => $display) {
    if (!empty($display->deleted)) {
      unset($form_state['view']->display[$id]);
    }
  }
  // Rename display ids if needed.
  foreach ($form_state['view']->display as $id => $display) {
    if (!empty($display->new_id)) {
      $form_state['view']->display[$id]->id = $display->new_id;
    }
  }

  $form_state['view']->save();
  drupal_set_message(t('The view %name has been saved.', array('%name' => $form_state['view']->name)));

  // Make sure menu items get rebuilt as neces
  menu_rebuild();

  // Clear the views cache.
  cache_clear_all('*', 'cache_views');

  // Clear the page cache.
  cache_clear_all();

  // Remove this view from cache so we can edit it properly.
  ctools_object_cache_clear('view', $form_state['view']->name);
}

/**
 * Submit handler for the edit view form.
 */
function views_ui_edit_view_form_cancel($form, &$form_state) {
  // Remove this view from cache so edits will be lost.
  ctools_object_cache_clear('view', $form_state['view']->name);
  if (empty($form['view']->vid)) {
    // I seem to have to drupal_goto here because I can't get fapi to
    // honor the redirect target. Not sure what I screwed up here.
    drupal_goto('admin/structure/views');
  }
}

function views_ui_edit_view_form_delete($form, &$form_state) {
  unset($_REQUEST['destination']);
  // Redirect to the delete confirm page
  $form_state['redirect'] = array('admin/structure/views/delete/' . $form_state['view']->name, array('query' => drupal_get_destination() + array('cancel' => 'admin/structure/views/edit/' . $form_state['view']->name)));
}

/**
 * Preprocess the view edit page.
 */
function template_preprocess_views_ui_edit_view(&$vars) {
  $view = &$vars['view'];
  ctools_include('dependent');

  // The form has to be saved as variable because of strict warnings.
  $form = drupal_get_form('views_ui_edit_view_form', $view);
  $vars['save_button'] = drupal_render($form);

  $table = views_fetch_data($view->base_table);
  $vars['base_table'] = !empty($table['table']['base']['title']) ?
    $table['table']['base']['title'] : t('Unknown or missing table name');

  views_include('tabs');
  $tabs = new views_tabset;

  $vars['message'] = '<div class="message">' . t("Click on an item to edit that item's details.") . '</div>';

  if (!$view->set_display('default')) {
    drupal_set_message(t('This view has a broken default display and cannot be used.'), 'error');
  }

  foreach ($view->display as $display) {
    list($title, $body) = views_ui_display_tab($view, $display);
    // The first display is the default.
    $tabs->set($display->id, $title, $body);
  }

  // This is the area that will render beneath the links
  $form_state = array(
    'view' => &$view,
    'ajax' => FALSE,
    'build_info' => array(
      'args' => array(),
    ),
    'no_cache' => TRUE,
  );

  $form = drupal_build_form('views_ui_add_display_form', $form_state);
  $display_button = drupal_render($form);
  $form = drupal_get_form('views_ui_analyze_view_button', $view);
  $analyze_button = drupal_render($form);
  $form = drupal_get_form('views_ui_reorder_displays_button', $view);
  $reorder_button = drupal_render($form);
  $tabs->add_extra($display_button . $reorder_button . $analyze_button);

  $vars['tabs'] = $tabs->render();

  $form_state = array(
    'display_id' => 'default',
    'view_args' => '',
    'rerender' => FALSE,
    'no_redirect' => TRUE,
    'view' => &$view,
    'input' => array(),
    'build_info' => array(
      'args' => array(),
    ),
  );
  $preview_form = drupal_build_form('views_ui_preview_form', $form_state);
  $vars['preview'] = drupal_render($preview_form);

  $vars['locked'] = NULL;
  if (isset($view->locked) && is_object($view->locked)) {
    $account = array();
    $account['account'] = user_load($view->locked->uid);
    $account['name'] = $account['account']->name;
    $vars['locked'] = theme('username', array('account' => $account['account']));
    $vars['lock_age'] = format_interval(REQUEST_TIME - $view->locked->updated);
    $vars['break'] = url('admin/structure/views/break-lock/' . $view->name);
  }

  $vars['quick_links_raw'] = array(
    'views-export' => array(
      'title' => t('Export'),
      'attributes' => array('title' => t("Export this view")),
      'href' => "admin/structure/views/export/$view->name",
    ),
    'views-clone' => array(
      'title' => t('Clone'),
      'attributes' => array('title' => t("Create a copy of this view")),
      'href' => "admin/structure/views/clone/$view->name",
    ),
  );

  $paths = array();
  foreach ($view->display as $id => $display) {
    if (!empty($display->handler) && $display->handler->has_path()) {
      $path = $display->handler->get_path();
      if (strpos($path, '%') === FALSE && !isset($paths[$path])) {
        $vars['quick_links_raw']['views-view-' . $display->id] = array(
          'title' => t('View "@display"', array('@display' => $display->display_title)),
          'attributes' => array('title' => t("Go to the real page for this display")),
          'href' => $path,
        );
        // Displays can have the same path; no point in showing more than one link.
        $paths[$path] = TRUE;
      }
    }
  }

  if (module_exists('contextual')) {
    $build = array(
      '#prefix' => '<div class="contextual-links-wrapper">',
      '#suffix' => '</div>',
      '#theme' => 'links__contextual',
      '#links' => $vars['quick_links_raw'],
      '#attributes' => array('class' => array('contextual-links')),
      '#attached' => array(
        'library' => array(array('contextual', 'contextual-links')),
      ),
    );
    $vars['quick_links'] = drupal_render($build);
  }
  else {
    $vars['quick_links'] = theme('links__views_quick_links', array('links' => $vars['quick_links_raw']));
  }
  views_add_css('views-admin');
  views_add_js('ajax');
  drupal_add_library('system', 'jquery.form');

  // Also add any js files required by plugins:
  $plugins = views_fetch_plugin_data();
  foreach ($plugins as $type => $type_plugins) {
    foreach ($type_plugins as $name => $plugin) {
      if (!empty($plugin['js'])) {
        foreach ($plugin['js'] as $file) {
          drupal_add_js($file);
        }
      }
    }
  }

  $settings = array('views' => array('ajax' => array(
    'id' => '#views-ajax-pad',
    'title' => '#views-ajax-title',
    'defaultForm' => $vars['message'],
  )));

  drupal_add_js($settings, 'setting');
}

function template_preprocess_views_ui_edit_tab(&$vars) {
  $view = $vars['view'];
  $display = $vars['display'];
  $plugin = $display->handler->definition;

  $top = $left = $middle = $right = '';

  // If this form was submitted it was already handled, so force it not to
  // submit again.

  $vars['remove'] = '';
  $vars['clone'] = '';
  if (empty($plugin['no remove'])) {
    if (!empty($_POST['form_id']) && in_array($_POST['form_id'], array('views_ui_remove_display_form', 'views_ui_clone_display_form'))) {
      unset($_POST['form_id']);
    }
    $form_state = array('view' => &$view, 'display_id' => $display->id, 'ajax' => FALSE, 'build_info' => array('args' => array()));
    $remove_form = drupal_build_form('views_ui_remove_display_form', $form_state);
    $clone_form = drupal_build_form('views_ui_clone_display_form', $form_state);
    $vars['remove'] = drupal_render($remove_form);
    $vars['clone'] = drupal_render($clone_form);
  }

  // basic fields
  $vars['title'] = check_plain($display->display_title);
  $vars['description'] = check_plain($plugin['help']);

  // Special fields if this is the default display.
  $vars['default'] = ($display->id == 'default');
  $vars['details_class'] = drupal_clean_css_identifier('details');
  if (!empty($view->changed_sections['details'])) {
    $vars['details_changed'] = TRUE;
  }

  foreach (array('human_name', 'tag', 'description') as $property) {
    if (!empty($view->changed_sections[$property])) {
      $vars['details_changed'][$property] = TRUE;
    }
    switch ($property) {
      case 'human_name':
        $title = t('Human name');
        break;
      case 'tag':
        $title = t('Tag');
        break;
      case 'description':
        $title = t('Description');
        break;
    }
    $value = empty($view->{$property}) ? t('None') : check_plain($view->{$property});
    $vars['details'][$property] = $title . ': ' . l($value, "admin/structure/views/nojs/$property/$view->name", array('attributes' => array('class' => 'views-ajax-link')));
  }

  // Calculate options from display plugin.
  $options = $categories = array();
  $display->handler->options_summary($categories, $options);

  // Build all of the options we were returned and put them into the
  // category data fields.
  foreach ($options as $id => $option) {
    if (empty($categories[$option['category']]['data'])) {
      $categories[$option['category']]['data'] = array();
    }
    $categories[$option['category']]['data'][$id] = array();
    $data = &$categories[$option['category']]['data'][$id];
    $data['content'] = '';
    $data['links'] = '';
    $data['overridden'] = FALSE;
    $data['defaulted'] = FALSE;

    // If there are optional links, build them first so they float properly.
    if (!empty($option['links'])) {
      foreach ($option['links'] as $link_id => $link_value) {
        $data['links'] .= $display->handler->option_link($link_value, $link_id, 'views-button-configure');
      }
    }
    if (!empty($option['title'])) {
      $data['content'] .= $option['title'] . ': ';
    }

    $data['content'] .= $display->handler->option_link($option['value'], $id, '', empty($option['desc']) ? '' : $option['desc']);
    if (!empty($display->handler->options['defaults'][$id])) {
      $display_id = 'default';
      $data['defaulted'] = TRUE;
    }
    else {
      $display_id = $display->id;
      if (!$display->handler->is_default_display()) {
        if ($display->handler->defaultable_sections($id)) {
          $data['overridden'] = TRUE;
        }
      }
    }
    $data['class'][] = drupal_clean_css_identifier($display_id . '-' . $id);
    if (!empty($view->changed_sections[$display_id . '-' . $id])) {
      $data['changed'] = TRUE;
    }
  }

  $vars['categories'] = $categories;

  // Add a help icon
  if (isset($plugin['help topic']) && module_exists('advanced_help')) {
    $vars['display_help_icon'] = theme('advanced_help_topic', array('module' => $plugin['module'], 'topic' => $plugin['help topic']));
  }
  else {
    $vars['display_help_icon'] = '';
  }

  // Fetch style plugin info because it has some effect on how/what we render.
  $style_plugin = $display->handler->get_plugin();

  $vars['fields'] = '';
  $vars['areas'] = array();
  foreach (array('header', 'footer', 'empty') as $area) {
    $vars['areas'][$area] = theme('views_ui_edit_item', array(
      'type' => $area, 'view' => $view, 'display' => $display,
    ));
  }
  $vars['fields'] = theme('views_ui_edit_item', array('type' => 'field', 'view' => $view, 'display' => $display, 'no_fields' => !($style_plugin && $style_plugin->uses_fields())));
  $vars['relationships'] = theme('views_ui_edit_item', array('type' => 'relationship', 'view' => $view, 'display' => $display));
  $vars['arguments'] = theme('views_ui_edit_item', array('type' => 'argument', 'view' => $view, 'display' => $display));
  $vars['filters'] = theme('views_ui_edit_item', array('type' => 'filter', 'view' => $view, 'display' => $display));
  $vars['sorts'] = theme('views_ui_edit_item', array('type' => 'sort', 'view' => $view, 'display' => $display));
}

/**
 * Generate the summary output for a single display to render in a tab.
 */
function views_ui_display_tab($view, $display) {
  if (isset($display->handler)) {
    $plugin = $display->handler->definition;
  }
  if (empty($plugin)) {
    $title = isset($display->display_title) ? $display->display_title : t('Invalid');
    return array($title, t("Error: Display @display refers to a plugin named '@plugin', but that plugin doesn't exist!", array('@display' => $display->id, '@plugin' => $display->display_plugin)));

    // @todo We can do a better 'plugin does not exist' tab.
  }

  // The display should always be initialized prior to this call.
  if (empty($display->handler)) {
    return FALSE;
  }

  $body = theme('views_ui_edit_tab', array('view' => $view, 'display' => $display));
  return array($display->display_title, $body);
}

/**
 * Add information about a section to a display.
 */
function template_preprocess_views_ui_edit_item(&$vars) {
  $type = $vars['type'];
  $view = $vars['view'];
  $display = $vars['display'];

  $types = views_object_types();

  $vars['overridden'] = FALSE;
  $vars['defaulted'] = FALSE;
  $vars['item_help_icon'] = module_exists('advanced_help') ? theme('advanced_help_topic', array('module' => 'views', 'topic' => $type)) : '';

  if ($vars['no_fields']) {
    $vars['title'] = $types[$type]['title'];
    $vars['rearrange'] = NULL;
    $vars['add'] = NULL;
    return;
  }

  // Different types now have different rearrange forms, so we use this switch
  // to get the right one.
  switch ($type) {
    case 'filter':
      $rearrange_url = "admin/structure/views/nojs/rearrange-$type/$view->name/$display->id/$type";
      break;
    default:
      $rearrange_url = "admin/structure/views/nojs/rearrange/$view->name/$display->id/$type";
  }

  $vars['rearrange'] = l('<span>' . t('Rearrange') . '</span>', $rearrange_url, array('attributes' => array('class' => array('views-button-rearrange', 'views-ajax-link'), 'title' => t('Rearrange')), 'html' => TRUE));

  $vars['add'] = l('<span>' . t('Add') . '</span>', "admin/structure/views/nojs/add-item/$view->name/$display->id/$type", array('attributes' => array('class' => array('views-button-add', 'views-ajax-link'), 'title' => t('Add'), 'id' => 'views-add-' . $type), 'html' => true));

  if (!$display->handler->is_default_display()) {
    if (!$display->handler->is_defaulted($types[$type]['plural'])) {
      $vars['overridden'] = TRUE;
    }
    else {
      $vars['defaulted'] = TRUE;
    }
  }

  if ($display->display_plugin != 'default') {
    $vars['title'] = l($types[$type]['title'], "admin/structure/views/nojs/config-type/$view->name/$display->id/$type", array('attributes' => array('class' => array('views-ajax-link'), 'id' => 'views-title-' . $type)));
  }
  else {
    $vars['title'] = $types[$type]['title'];
  }

  $fields = array();

  static $relationships = NULL;
  if (!isset($relationships)) {
    // Get relationship labels
    $relationships = array();
    // @todo: get_handlers()
    $handlers = $display->handler->get_option('relationships');
    if ($handlers) {
      foreach ($handlers as $id => $info) {
        $handler = $display->handler->get_handler('relationship', $id);
        $relationships[$id] = $handler->label();
      }
    }
  }

  // Filters can now be grouped so we do a little bit extra:
  $groups = array();
  $grouping = FALSE;
  if ($type == 'filter') {
    $group_info = $view->display_handler->get_option('filter_groups');
    if (!empty($group_info['groups']) && count($group_info['groups']) > 1) {
      $grouping = TRUE;
      $groups = array(0 => array());
    }
  }

  foreach ($display->handler->get_option($types[$type]['plural']) as $id => $field) {
    $fields[$id] = array();

    $handler = $display->handler->get_handler($type, $id);
    if (empty($handler)) {
      $fields[$id]['class'][] = 'broken';
      $field_name = t('Broken/missing handler: @table > @field', array('@table' => $field['table'], '@field' => $field['field']));
      $fields[$id]['title'] = l($field_name, "admin/structure/views/nojs/config-item/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-ajax-link')), 'html' => TRUE));
      $fields[$id]['info'] = '';
      continue;
    }

    $field_name = $handler->ui_name(TRUE);
    if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
      $field_name = '(' . $relationships[$field['relationship']] . ') ' . $field_name;
    }

    $fields[$id]['title'] = l($field_name, "admin/structure/views/nojs/config-item/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-ajax-link')), 'html' => TRUE));
    $fields[$id]['class'][] = drupal_clean_css_identifier($display->id . '-' . $type . '-' . $id);
    if (!empty($view->changed_sections[$display->id . '-' . $type . '-' . $id])) {
      $fields[$id]['changed'] = TRUE;
    }
    $fields[$id]['info'] = $handler->admin_summary();

    if ($display->handler->use_group_by()) {
      $fields[$id]['links'] = l('<span>' . t('Group settings') . '</span>', "admin/structure/views/nojs/config-item-group/$view->name/$display->id/$type/$id", array('attributes' => array('class' => 'views-button-configure views-ajax-link', 'title' => t('Group settings')), 'html' => true));
    }

    if ($handler->has_extra_options()) {
      $fields[$id]['links'] = l('<span>' . t('Settings') . '</span>', "admin/structure/views/nojs/config-item-extra/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-button-configure', 'views-ajax-link'), 'title' => t('Settings')), 'html' => true));
    }

    if ($handler->needs_style_plugin()) {
      $style_plugin = views_fetch_plugin_data('style', $handler->options['style_plugin']);
      $style_title = empty($style_plugin['title']) ? t('Missing style plugin') : $style_plugin['title'];
      $pid = $id . '-style-plugin';

      if (!empty($style_plugin['uses options'])) {
        $fields[$pid]['links'] = l('<span>' . t('Change settings for this style') . '</span>', "admin/structure/views/nojs/config-style/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-button-configure', 'views-ajax-link'), 'title' => t('Settings')), 'html' => true));
      }

      $fields[$pid]['title'] = ' ' . t('&nbsp; Style: !style', array('!style' => l($style_title, "admin/structure/views/nojs/change-style/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-ajax-link'))))));
      $fields[$pid]['class'][] = drupal_clean_css_identifier($display->id . '-' . $type . '-' . $pid);
      if (!empty($view->changed_sections[$display->id . '-' . $type . '-' . $pid])) {
        $fields[$pid]['changed'] = TRUE;
      }
      $fields[$pid]['info'] = '';
    }

    if ($grouping) {
      $gid = $handler->options['group'];

      // Show in default group if the group does not exist.
      if (empty($group_info['groups'][$gid])) {
        $gid = 0;
      }
      $groups[$gid][] = $id;
    }
  }

  // If using grouping, re-order fields so that they show up properly in the list.
  if ($type == 'filter' && $grouping) {
    $store = $fields;
    $fields = array();
    foreach ($groups as $gid => $contents) {
      if (!empty($fields)) {
        $operator = ') ' . ($group_info['operator'] == 'OR' ? t('OR') : t('AND')) . ' (';
      }
      else {
        $operator = '(';
      }
      $fields[] = array(
        'class' => 'views-group-text',
        'title' => $operator,
        'info' => '',
      );
      $started = FALSE;
      foreach ($contents as $pid) {
        $operator = '&nbsp;&nbsp;';
        if ($started) {
          $operator .= ($group_info['groups'][$gid] == 'OR' ? t('OR') : t('AND')) . ' ';
        }
        $store[$pid]['title'] = $operator . $store[$pid]['title'];
        $started = TRUE;
        $fields[$pid] = $store[$pid];
      }
    }
    $fields[] = array(
      'class' => 'views-group-text',
      'title' => ')',
      'info' => '',
    );
  }

  $vars['fields'] = $fields;
}

/**
 * Regenerate the tabs for AJAX updates.
 */
function views_ui_regenerate_tabs(&$view, &$output, $display_id = NULL) {
  if (empty($display_id)) {
    $displays = array_keys($view->display);
  }
  elseif (!is_array($display_id)) {
    $displays = array($display_id);
    if ($display_id != 'default') {
      $displays[] = 'default';
    }
  }
  else {
    $displays = $display_id;
  }

  if (!$view->set_display('default')) {
    return;
  }

  foreach ($displays as $id) {
    list($title, $body) = views_ui_display_tab($view, $view->display[$id]);
    $output[] = ajax_command_html('#views-tab-' . $id, $body);
    $output[] = ajax_command_html('#views-tab-title-' . $id, check_plain($title));
  }
}

/**
 * Provide standard buttons for the forms to make it easy. Also provide
 * a hidden op operator because the forms plugin doesn't seem to properly
 * provide which button was clicked.
 */
function views_ui_standard_form_buttons(&$form, &$form_state, $form_id, $name = NULL, $third = NULL, $submit = NULL) {
  $form['buttons'] = array(
    '#prefix' => '<div class="clearfix"><div class="form-buttons">',
    '#suffix' => '</div></div>',
  );

  if (empty($name)) {
    $name = t('Update');
  }

  // Add the override and update button
  if ($name == t('Update default display')) {
    $form['buttons']['override_update'] = array(
      '#type' => 'submit',
      '#value' => t('Update and override'),
      '#submit' => array(
        'views_ui_edit_display_form_override_update_section',
        'views_ui_standard_submit',
        'views_ui_edit_display_form_override_update',
      ),
    );
  }

  if (empty($form_state['ok_button'])) {
    // but be sure submit button validates!
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => $name,
      '#submit' => array('views_ui_standard_submit', $form_id . '_submit'),
    );
    // Take sure that the validation handler exists.
    if (function_exists($form_id . '_validate')) {
      $form['buttons']['submit']['#validate'][] = $form_id . '_validate';
    }

  }

  $cancel_submit = function_exists($form_id . '_cancel') ? $form_id . '_cancel' : 'views_ui_standard_cancel';
  $form['buttons']['cancel'] = array(
    '#type' => 'submit',
    '#value' => empty($form_state['ok_button']) ? t('Cancel') : t('Ok'),
    '#submit' => array($cancel_submit),
    '#validate' => array(),
  );

  if ($third) {
    if (empty($submit)) {
      $submit = 'third';
    }
    $third_submit = function_exists($form_id . '_' . $submit) ? $form_id . '_' . $submit : 'views_ui_standard_cancel';

    $form['buttons'][$submit] = array(
      '#type' => 'submit',
      '#value' => $third,
      '#validate' => array(),
      '#submit' => array($third_submit),
    );
  }

  // Compatibility, to be removed later:
  // We used to set these items on the form, but now we want them on the $form_state:
  if (isset($form['#title'])) {
    $form_state['title'] = $form['#title'];
  }
  if (isset($form['#help_topic'])) {
    $form_state['help_topic'] = $form['#help_topic'];
  }
  if (isset($form['#help_module'])) {
    $form_state['help_module'] = $form['#help_module'];
  }
  if (isset($form['#url'])) {
    $form_state['url'] = $form['#url'];
  }
  if (isset($form['#js'])) {
    if (!empty($form_state['js settings']) && is_array($form_state['js settings'])) {
      $form_state['js settings'] = array_merge($form_state['js settings'], $form['#js']);
    }
    else {
      $form_state['js settings'] = $form['#js'];
    }
  }
  if (isset($form['#section'])) {
    $form_state['#section'] = $form['#section'];
  }
  // Finally, we never want these cached -- our object cache does that for us.
  $form['#no_cache'] = TRUE;

  // If this isn't an ajaxy form, then we want to set the title.
  if (!empty($form['#title'])) {
    drupal_set_title($form['#title']);
  }
  $form['#attached']['css'] = array(
    drupal_get_path('module', 'views') . "/css/views-admin.css",
  );
}

/**
 * Basic submit handler applicable to all 'standard' forms
 */
function views_ui_standard_submit($form, &$form_state) {
  if (!empty($form['#section'])) {
    $form_state['view']->changed_sections[$form['#section']] = TRUE;
  }
}

/**
 * Submit handler for cancel button
 */
function views_ui_standard_cancel($form, &$form_state) {
  if (!empty($form_state['view']->changed) && isset($form_state['view']->form_cache)) {
    unset($form_state['view']->form_cache);
    views_ui_cache_set($form_state['view']);
  }

  $form_state['redirect'] = 'admin/structure/views/edit/' . $form_state['view']->name;
}

// --------------------------------------------------------------------------
// Various subforms for editing the pieces of a view.

function views_ui_ajax_forms($key = NULL) {
  $forms = array(
    'display' => array(
      'form_id' => 'views_ui_edit_display_form',
      'args' => array('section'),
    ),
    'remove-display' => array(
      'form_id' => 'views_ui_remove_display_form',
      'args' => array(),
    ),
    'config-type' => array(
      'form_id' => 'views_ui_config_type_form',
      'args' => array('type'),
    ),
    'rearrange' => array(
      'form_id' => 'views_ui_rearrange_form',
      'args' => array('type'),
    ),
    'rearrange-filter' => array(
      'form_id' => 'views_ui_rearrange_filter_form',
      'args' => array('type'),
    ),
    'add-item' => array(
      'form_id' => 'views_ui_add_item_form',
      'args' => array('type'),
    ),
    'config-item' => array(
      'form_id' => 'views_ui_config_item_form',
      'args' => array('type', 'id'),
    ),
    'config-item-extra' => array(
      'form_id' => 'views_ui_config_item_extra_form',
      'args' => array('type', 'id'),
    ),
    'config-item-group' => array(
      'form_id' => 'views_ui_config_item_group_form',
      'args' => array('type', 'id'),
    ),
    'change-style' => array(
      'form_id' => 'views_ui_change_style_form',
      'args' => array('type', 'id'),
    ),
    'config-style' => array(
      'form_id' => 'views_ui_config_style_form',
      'args' => array('type', 'id'),
    ),
  );

  if ($key) {
    return !empty($forms[$key]) ? $forms[$key] : NULL;
  }

  return $forms;
}

/**
 * Build a form identifier that we can use to see if one form
 * is the same as another. Since the arguments differ slightly
 * we do a lot of spiffy concenation here.
 */
function views_ui_build_identifier($key, $view, $display_id, $args) {
  $form = views_ui_ajax_forms($key);
  // Automatically remove the single-form cache if it exists and
  // does not match the key.
  $identifier = implode('-', array($key, $view->name, $display_id));

  foreach ($form['args'] as $id) {
    $arg = (!empty($args)) ? array_shift($args) : NULL;
    $identifier .= '-' . $arg;
  }
  return $identifier;
}

/**
 * Build up a $form_state object suitable for use with drupal_build_form
 * based on known information about a form.
 */
function views_ui_build_form_state($js, $key, &$view, $display_id, $args) {
  $form = views_ui_ajax_forms($key);
  // Build up form state
  $form_state = array(
    'form_key' => $key,
    'form_id' => $form['form_id'],
    'view' => &$view,
    'ajax' => $js,
    'display_id' => $display_id,
    'no_redirect' => TRUE,
  );

  foreach ($form['args'] as $id) {
    $form_state[$id] = (!empty($args)) ? array_shift($args) : NULL;
  }

  return $form_state;
}

/**
 * Create the URL for one of our standard AJAX forms based upon known
 * information about the form.
 */
function views_ui_build_form_url($form_state) {
  $form = views_ui_ajax_forms($form_state['form_key']);
  $ajax = empty($form_state['ajax']) ? 'nojs' : 'ajax';
  $name = $form_state['view']->name;
  $url = "admin/structure/views/$ajax/$form_state[form_key]/$name/$form_state[display_id]";
  foreach ($form['args'] as $arg) {
    $url .= '/' . $form_state[$arg];
  }
  return $url;
}

/**
 * Add another form to the stack; clicking 'update' will go to this form
 * rather than closing the ajax pad.
 */
function views_ui_add_form_to_stack($key, &$view, $display_id, $args, $top = FALSE) {
  if (empty($view->stack)) {
    $view->stack = array();
  }

  $stack = array(views_ui_build_identifier($key, $view, $display_id, $args), $key, &$view, $display_id, $args);
  if ($top) {
    array_unshift($view->stack, $stack);
  }
  else {
    $view->stack[] = $stack;
  }
}

/**
 * Generic entry point to handle forms.
 *
 * We do this for consistency and to make it easy to chain forms
 * together. This only works for forms that use both $view
 * and $display_id, so we have a couple of ajax forms that we don't
 * use with this system.
 */
function views_ui_ajax_form($js, $key, $view, $display_id) {
  // Reset the cache of IDs. Drupal rather aggressively prevents id duplication
  // but this causes it to remember IDs that are no longer even being used.
  if (isset($_POST['ajax_html_ids'])) {
    unset($_POST['ajax_html_ids']);
  }

  $form = views_ui_ajax_forms($key);
  if (empty($form)) {
    return drupal_not_found();
  }

  views_include('ajax');
  $args = func_get_args();
  // Remove the known args
  array_splice($args, 0, 4);

  $form_state = views_ui_build_form_state($js, $key, $view, $display_id, $args);
  // check to see if this is the top form of the stack. If it is, pop
  // it off; if it isn't, the user clicked somewhere else and the stack is
  // now irrelevant.
  if (!empty($view->stack)) {
    $identifier = views_ui_build_identifier($key, $view, $display_id, $args);
    $top = array_shift($view->stack);
    if (array_shift($top) != $identifier) {
      $view->stack = array();
    }
  }

  // Automatically remove the form cache if it is set and the key does
  // not match. This way navigating away from the form without hitting
  // update will work.
  if (isset($view->form_cache) && $view->form_cache['key'] != $key) {
    unset($view->form_cache);
  }

  $output = views_ajax_form_wrapper($form_state['form_id'], $form_state);
  if ($form_state['submitted'] && empty($form_state['rerender'])) {
    // Sometimes we need to re-generate the form for multi-step type operations.
    $object = NULL;
    if (!empty($view->stack)) {
      $stack = $view->stack;
      $top = array_shift($stack);
      $top[0] = $js;
      $form_state = call_user_func_array('views_ui_build_form_state', $top);
      $form_state['input'] = array();
      $form_state['url'] = url(views_ui_build_form_url($form_state));
      if (!$js) {
        return drupal_goto(views_ui_build_form_url($form_state));
      }
      $output = views_ajax_form_wrapper($form_state['form_id'], $form_state);
    }
    elseif (!$js) {
      // if nothing on the stack, non-js forms just go back to the main view editor.
      return drupal_goto("admin/structure/views/edit/$view->name");
    }
    else {
      $output = array();
      $output[] = views_ajax_command_enable_buttons();
      $output[] = views_ajax_command_dismiss_form();
      $output[] = views_ajax_command_trigger_preview();
    }
    views_ui_regenerate_tabs($view, $output);
  }
  elseif ($js) {
    $output[] = views_ajax_command_disable_buttons();
  }

  return $js ? array('#type' => 'ajax', '#commands' => $output) : $output;
}

/**
 * AJAX callback to add a display.
 */
function views_ui_add_display($js, $view) {
  views_include('ajax');
  $form_state = array(
    'view' => &$view,
    'ajax' => $js,
  );

  $output = views_ajax_form_wrapper('views_ui_add_display_form', $form_state);

  if ($js) {
    if ($form_state['submitted']) {
      $id = $form_state['id'];

      // Make sure the new display is active
      if (!$view->set_display('default')) {
        views_ajax_error(t('Unable to initialize default display'));
      }

      // Render the new display
      list($title, $body) = views_ui_display_tab($view, $view->display[$id]);

      // Instruct the javascript on the browser to render the new tab.
      $output = array();
      $output[] = views_ajax_command_add_tab($id, $title, $body);
    }
    $output = array('#type' => 'ajax', '#commands' => $output);
  }

  // But the non-js variant will return output if it didn't redirect us.
  return $output;
}

/**
 * Form to add a display to a view.
 */
function views_ui_add_display_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['display']['display'] = array(
    '#type' => 'select',
    '#options' => views_fetch_plugin_names('display'),
    '#default_value' => 'page',
  );

  $form['display']['add_display'] = array(
    '#type' => 'submit',
    '#value' => t('Add display'),
    '#ajax' => array(
      'path' => "admin/structure/views/ajax/add-display/$view->name",
    ),
    '#submit' => array('views_ui_add_display_form_submit'),
  );

  $form['#id'] = 'views-add-display-form';
  $form['#attributes'] = array('class' => array('views-ajax-form'));
  $form['#action'] = url("admin/structure/views/nojs/add-display/$view->name");

  return $form;
}

/**
 * Submit handler to add a display to a view.
 */
function views_ui_add_display_form_submit($form, &$form_state) {
  // Create the new display
  $plugin = $form_state['values']['display'];
  $form_state['id'] = $form_state['view']->add_display($plugin);

  // Store in cache
  views_ui_cache_set($form_state['view']);

  // Send it back
  $form_state['redirect'] = array('admin/structure/views/edit/' . $form_state['view']->name, array('fragment' => 'views-tab-' . $form_state['id']));
}

/**
 * AJAX callback to add a display.
 */
function views_ui_clone_display($js, $view, $id) {
  views_include('ajax');
  $form_state = array(
    'view' => &$view,
    'ajax' => $js,
    'display_id' => $id,
  );

  $output = views_ajax_form_wrapper('views_ui_clone_display_form', $form_state);

  if ($js) {
    // If we don't have an output object, it was submitted. Set up the submission.
    if (empty($output)) {
      $id = $form_state['id'];

      // Make sure the new display is active
      if (!$view->set_display('default')) {
        views_ajax_render(t('Unable to initialize default display'));
      }

      // Render the new display
      list($title, $body) = views_ui_display_tab($view, $view->display[$id]);

      // Instruct the javascript on the browser to render the new tab.
      $output = new stdClass;
      $output->tab = array('#views-tab-' . $id => array('title' => $title, 'body' => $body));
    }
    // Render the command object. This automatically exits.
    views_ajax_render($output);
  }

  // But the non-js variant will return output if it didn't redirect us.
  return $output;
}

/**
 * From to clone a display from a view.
 */
function views_ui_clone_display_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];

  $form['clone_display'] = array(
    '#type' => 'submit',
    '#value' => t('Clone display'),
    '#submit' => array('views_ui_clone_display_form_submit'),
  );

  $form['#id'] = 'views-clone-display-form';
  $form['#action'] = url("admin/build/views/nojs/clone-display/$view->name/$display_id");
  $form['#attributes'] = array('class' => 'views-ajax-form');

  return $form;
}

/**
 * Submit handler to add a clone to a display from a view.
 */
function views_ui_clone_display_form_submit($form, &$form_state) {
  // Create the new display
  $id = $form_state['display_id'];
  $display = $form_state['view']->display[$id];

  $new_id = $form_state['view']->add_display($display->display_plugin);
  $form_state['id'] = $new_id;

  // Replace the new display by a copy of the old
  $form_state['view']->display[$new_id] = clone $display;
  $form_state['view']->display[$new_id]->id = $new_id;

  // Store in cache
  views_ui_cache_set($form_state['view']);

  // Send it back
  $form_state['redirect'] = array('admin/structure/views/edit/' . $form_state['view']->name, array('fragment' =>  'views-tab-' . $new_id));
}

/**
 * Form to remove a display from a view.
 */
function views_ui_remove_display_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];

  if (empty($view->display[$display_id]->deleted)) {
    $form['display'] = array(
      '#prefix' => '<div class="display-button remove-display">',
      '#suffix' => '</div>',
    );
    $form['remove_display'] = array(
      '#type' => 'submit',
      '#value' => t('Remove display'),
      '#submit' => array('views_ui_remove_display_form_submit'),
    );
  }
  else {
    $form['display'] = array(
      '#prefix' => '<div class="display-button restore-display">',
      '#suffix' => '</div>',
    );
    $form['restore_display'] = array(
      '#type' => 'submit',
      '#value' => t('Restore display'),
      '#submit' => array('views_ui_remove_display_form_restore'),
    );
  }
  $form['#action'] = url("admin/structure/views/nojs/remove-display/$view->name/$display_id");
  $form['#attributes'] = array('class' => array('views-ajax-form'));

  return $form;
}

/**
 * Submit handler to add a remove to a display from a view.
 */
function views_ui_remove_display_form_submit($form, &$form_state) {
  // Create the new display
  $plugin = views_fetch_plugin_data('display', $form_state['view']->display[$form_state['display_id']]->display_plugin);
  if (empty($plugin['no remove'])) {
    $id = $form_state['display_id'];
    $form_state['view']->display[$id]->deleted = TRUE;

    // Store in cache
    views_ui_cache_set($form_state['view']);
  }
}

/**
 * Submit handler to add a restore a removed display to a view.
 */
function views_ui_remove_display_form_restore($form, &$form_state) {
  // Create the new display
  $id = $form_state['display_id'];
  $form_state['view']->display[$id]->deleted = FALSE;

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Page callback to display analysis information on a view.
 */
function views_ui_analyze_view($js, $view) {
  views_include('ajax');
  $form_state = array(
    'view' => &$view,
    'ajax' => $js,
  );

  $output = views_ajax_form_wrapper('views_ui_analyze_view_form', $form_state);

  if ($js) {
    // If we don't have an output object, it was submitted. Set up the submission.
    if ($form_state['submitted'] && empty($form_state['rerender'])) {
      $output = array();
      $output[] = views_ajax_command_enable_buttons();
      $output[] = views_ajax_command_dismiss_form();
      views_ui_regenerate_tabs($view, $output);
    }
    $commands = $output;
    return $js ? array('#type' => 'ajax', '#commands' => $commands) : $commands;
  }
  return $output;
}

/**
 * This form doesn't particularly do much; it's really just providing a link
 * but a button seems like it would be nicer here.
 *
 * It has no submit or anything, as we will never actually submit this form
 * where the form is placed.
 */
function views_ui_analyze_view_button($form, &$form_state, $view) {
  $form['#action'] = url("admin/structure/views/nojs/analyze/$view->name");
  $form['#attributes'] = array('class' => array('views-ajax-form'));
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Analyze'),
    '#ajax' => array(
      'path' => "admin/structure/views/ajax/analyze/$view->name",
    ),
  );

  return $form;
}

/**
 * Form constructor callback to display analysis information on a view
 */
function views_ui_analyze_view_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['#title'] = t('View analysis');
  $form['#section'] = 'analyze';

  views_include('analyze');
  $messages = views_analyze_view($view);

  $form['analysis'] = array(
    '#prefix' => '<div class="form-item">',
    '#suffix' => '</div>',
    '#markup' => views_analyze_format_result($view, $messages),
  );

  // Inform the standard button function that we want an OK button.
  $form_state['ok_button'] = TRUE;
  views_ui_standard_form_buttons($form, $form_state, 'views_ui_analyze_view_form');
  return $form;
}

/**
 * Submit handler for views_ui_analyze_view_form
 */
function views_ui_analyze_view_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/structure/views/edit/' . $form_state['view']->name;
}

/**
 * Page callback to display analysis information on a view.
 */
function views_ui_reorder_view($js, $view) {
  views_include('ajax');
  $form_state = array(
    'view' => &$view,
    'ajax' => $js,
  );

  $output = views_ajax_form_wrapper('views_ui_reorder_displays_form', $form_state);

  if ($js) {
    if ($form_state['submitted'] && empty($form_state['rerender'])) {
      // I don't want preprocess to modify the views -> no references
      $vars = array('view' => $view);
      template_preprocess_views_ui_edit_view($vars);
      $output = array();
      $output[] = ajax_command_replace('.views-tabset', $vars['tabs']);
      // Not the right place to have html i know !
      $output[] = ajax_command_replace('.views-quick-links', '<div class="views-quick-links">'. $vars['quick_links'] .'</div>');
      $output[] = views_ajax_command_enable_buttons();
      $output[] = views_ajax_command_dismiss_form();
      // Doesn't work yet, maybe we should reload the page dunno
      views_ui_regenerate_tabs($view, $output);
    }
    $commands = $output;
    return $js ? array('#type' => 'ajax', '#commands' => $commands) : $commands;
  }
  return $output;
}

/**
 * Form constructor callback to reorder displays on a view
 */
function views_ui_reorder_displays_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['view'] = array('#type' => 'value', '#value' => $view);

  $form['#tree'] = TRUE;

  $last_display = end($view->display);

  foreach ($view->display as $display) {
    $form[$display->id] = array(
      'title'  => array('#markup' => $display->display_title),
      'weight' => array(
        '#type' => 'weight',
        '#value' => $display->position,
        '#delta' => $last_display->position,
        '#title' => t('Weight for @display', array('@display' => $display->display_title)),
        '#title_display' => 'invisible',
      ),
      '#tree' => TRUE,
      '#display' => $display,
      'removed' => array(
        '#type' => 'checkbox',
        '#id' => 'display-removed-' . $display->id,
        '#attributes' => array('class' => array('views-remove-checkbox')),
        '#default_value' => isset($display->deleted),
      ),
    );

    if (isset($display->deleted) && $display->deleted) {
      $form[$display->id]['deleted'] = array('#type' => 'value', '#value' => TRUE);
    }
    if ($display->id === 'default') {
      unset($form[$display->id]['weight']);
      unset($form[$display->id]['removed']);
    }

  }

  $form['#title'] = t('Displays Reorder');
  $form['#section'] = 'reorder';

  // Add javascript settings that will be added via $.extend for tabledragging
  $form['#js']['tableDrag']['reorder-displays']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  $form['#action'] = url('admin/structure/views/nojs/reorder-displays/'. $view->name);

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_reorder_displays_form');

  return $form;
}

/**
 * Display position sorting function
 */
function _views_position_sort($display1, $display2) {
  if ($display1->position != $display2->position) {
    return $display1->position < $display2->position ? -1 : 1;
  }

  return 0;
}

/**
 * Submit handler for rearranging display form
 */
function views_ui_reorder_displays_form_submit($form, &$form_state) {
  foreach($form_state['input'] as $display => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
      $order[$display] = $info['weight'];
    }
  }

  // Sort the order array
  asort($order);

  // Fixing up positions
  $position = 2;

  foreach(array_keys($order) as $display) {
    $order[$display] = $position++;
  }

  // Setting up position and removing deleted displays
  $displays = $form_state['view']->display;
  foreach($displays as $display_id => $display) {
    // Don't touch the default !!!
    if ($display_id === 'default') {
      continue;
    }
    if (isset($order[$display_id])) {
      $form_state['view']->display[$display_id]->position = $order[$display_id];
    }
    else {
      $form_state['view']->display[$display_id]->deleted = TRUE;
    }
  }

  // Sorting back the display array as the position is not enough
  uasort($form_state['view']->display, '_views_position_sort');

  // Store in cache
  views_ui_cache_set($form_state['view']);
  $form_state['redirect'] = array('admin/structure/views/edit/' . $form_state['view']->name, array('fragment' => 'views-tab-default'));
}

/**
 * Turn the reorder form into a proper table
 */
function theme_views_ui_reorder_displays_form($vars) {
  $form = $vars['form'];
  $rows = array();
  foreach (element_children($form) as $key) {
    if (isset($form[$key]['#display'])) {
      $display = &$form[$key];

      $row = array();
      $row[] = drupal_render($display['title']);
      $form[$key]['weight']['#attributes']['class'] = array('weight');
      $row[] = drupal_render($form[$key]['weight']);
      if (isset($display['removed'])) {
        $row[] = drupal_render($form[$key]['removed']) .
          l('<span>' . t('Remove') . '</span>',
            'javascript:void()',
            array(
              'attributes' => array(
                'id' => 'display-remove-link-' . $key,
                'class' => array('views-button-remove display-remove-link'),
                'alt' => t('Remove this display'),
                'title' => t('Remove this display')),
              'html' => TRUE));
      }
      else {
        $row[] = '';
      }
      $class = array();
      $styles = array();
      if (isset($form[$key]['weight']['#type'])) {
        $class[] = 'draggable';
      }
      if (isset($form[$key]['deleted']['#value']) && $form[$key]['deleted']['#value']) {
        $styles[] = 'display: none;';
      }
      $rows[] = array('data' => $row, 'class' => $class, 'id' => 'display-row-' . $key, 'style' => $styles);
    }
  }

  $header = array(t('Display'), t('Weight'), t('Remove'));
  $output = '';
  drupal_add_tabledrag('reorder-displays', 'order', 'sibling', 'weight');

  $output = drupal_render($form['override']);
  $output .= theme('table',
    array('header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'reorder-displays'),
  ));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * This form doesn't particularly do much; it's really just providing a link
 * but a button seems like it would be nicer here.
 *
 * It has no submit or anything, as we will never actually submit this form
 * where the form is placed.
 */
function views_ui_reorder_displays_button($form, &$form_state, $view) {
  $form['#action'] = url("admin/structure/views/nojs/reorder-displays/$view->name");
  $form['#attributes'] = array('class' => array('views-ajax-form'));
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Reorder'),
    '#ajax' => array(
      'path' => "admin/structure/views/ajax/reorder-displays/$view->name",
    ),
  );

  return $form;
}

/**
 * Page callback to edit details of a view.
 */
function views_ui_edit_details($property, $js, $view) {
  views_include('ajax');
  $form_state = array(
    'view' => &$view,
    'ajax' => $js,
    'property' => $property,
  );

  $output = views_ajax_form_wrapper('views_ui_edit_details_form', $form_state);

  if ($js) {
    // If we don't have an output object, it was submitted. Set up the submission.
    if ($form_state['submitted'] && empty($form_state['rerender'])) {
      $output = array();
      $output[] = views_ajax_command_enable_buttons();
      $output[] = views_ajax_command_dismiss_form();
      views_ui_regenerate_tabs($view, $output);
    }
    $commands = $output;
    return $js ? array('#type' => 'ajax', '#commands' => $commands) : $commands;
  }
  return $output;
}

/**
 * Form constructor callback to edit details of a view
 */
function views_ui_edit_details_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['#title'] = t('View details');
  $form['#section'] = 'details';

  switch ($form_state['property']) {
    case 'human_name':
      $form['human_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Human readable name'),
        '#description' => t('You can use a more descriptive name for this view here. Spaces are allowed'),
        '#default_value' => $view->human_name,
      );
      break;

    case 'tag':
      $form['tag'] = array(
        '#type' => 'textfield',
        '#title' => t('View tag'),
        '#description' => t('Enter an optional tag for this view; it is used only to help sort views on the administrative page.'),
        '#default_value' => $view->tag,
        '#autocomplete_path' => 'admin/views/ajax/autocomplete/tag',
      );
      break;

    case 'description':
      $form['description'] = array(
        '#type' => 'textfield',
        '#title' => t('View description'),
        '#description' => t('This description will appear on the Views administrative UI to tell you what the view is about.'),
        '#default_value' => $view->description,
      );
      break;
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_edit_details_form');
  return $form;
}

/**
 * Submit handler for views_ui_edit_details_form
 */
function views_ui_edit_details_form_submit($form, &$form_state) {
  $form_state['view']->{$form_state['property']} = $form_state['values'][$form_state['property']];
  views_ui_cache_set($form_state['view']);
  $form_state['redirect'] = 'admin/structure/views/edit/' . $form_state['view']->name;
}

/**
 * Form constructor callback to edit display of a view
 */
function views_ui_edit_display_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $section = $form_state['section'];

  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];

  // Get form from the handler.
  $display->handler->options_form($form, $form_state);
  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_edit_display_form', $name);
  return $form;
}

/**
 * Validate handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_validate($form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_validate($form, $form_state);

  if (form_get_errors()) {
    $form_state['rerender'] = TRUE;
  }
}

/**
 * Submit handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_submit($form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_submit($form, $form_state);

  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_override($form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_override($form, $form_state);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}
/**
 * Override handler and submit views_ui_edit_display_form
 */
function views_ui_edit_display_form_override_update(&$form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_override($form, $form_state);
  $display->handler->options_submit($form, $form_state);
  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler and submit views_ui_edit_display_form
 */
function views_ui_edit_display_form_override_update_section(&$form, &$form_state) {
  // Update the #section so it knows what to mark changed.
  $form['#section'] = str_replace('default-', $form_state['display_id'] . '-', $form['#section']);
}

/**
 * Form to config items in the views UI.
 */
function views_ui_config_type_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = views_object_types();
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];
  $form['#title'] = check_plain($display->display_title) . ': ';
  $form['#title'] .= t('Configure @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'config-item';

  if ($display->handler->defaultable_sections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    $display->handler->add_override_button($form, $form_state, $form_state['section']);
  }

  if (!empty($types[$type]['options']) && function_exists($types[$type]['options'])) {
    $options = $type . '_options';
    $form[$options] = array('#tree' => TRUE);
    $types[$type]['options']($form, $form_state);
  }

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_type_form', $name);
  return $form;
}

/**
 * Submit handler for type configuration form
 */
function views_ui_config_type_form_submit($form, &$form_state) {
  $types = views_object_types();
  $display = &$form_state['view']->display[$form_state['display_id']];

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Configure settings particular to filters.
 */
function views_ui_config_filters_form(&$form, &$form_state) {

}

/**
 * Form to rearrange items in the views UI.
 */
function views_ui_rearrange_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = views_object_types();
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];
  $form['#title'] = check_plain($display->display_title) . ': ';
  $form['#title'] .= t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'rearrange-item';

  if ($display->handler->defaultable_sections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    $display->handler->add_override_button($form, $form_state, $form_state['section']);
  }

  $count = 0;

  // Get relationship labels
  $relationships = array();
  foreach ($display->handler->get_handlers('relationship') as $id => $handler) {
    $relationships[$id] = $handler->label();
  }

  foreach ($display->handler->get_option($types[$type]['plural']) as $id => $field) {
    $form[$id] = array('#tree' => TRUE);
    $form[$id]['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$count,
    );
    $handler = $display->handler->get_handler($type, $id);
    if ($handler) {
      $name = $handler->ui_name() . ' ' . $handler->admin_summary();
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
      }

      $form[$id]['name'] = array(
        '#markup' => $name,
      );
    }
    else {
      $form[$id]['name'] = array('#value' => t('Broken field @id', array('@id' => $id)));
    }
    $form[$id]['removed'] = array(
      '#type' => 'checkbox',
      '#id' => 'views-removed-' . $id,
      '#attributes' => array('class' => array('views-remove-checkbox')),
      '#default_value' => 0,
    );
  }

  // Add javascript settings that will be added via $.extend for tabledragging
  $form['#js']['tableDrag']['arrange']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_rearrange_form');
  return $form;
}

/**
 * Turn the rearrange form into a proper table
 */
function theme_views_ui_rearrange_form($variables) {
  $form = $variables['form'];

  $rows = array();
  foreach (element_children($form) as $id) {
    if (isset($form[$id]['name'])) {
      $row = array();
      $row[] = drupal_render($form[$id]['name']);
      $form[$id]['weight']['#attributes']['class'] = array('weight');
      $row[] = drupal_render($form[$id]['weight']);
      $row[] = drupal_render($form[$id]['removed']) . l('<span>' . t('Remove') . '</span>', 'javascript:void()', array('attributes' => array('id' => 'views-remove-link-' . $id, 'class' => array('views-hidden', 'views-button-remove', 'views-remove-link'), 'alt' => t('Remove this item'), 'title' => t('Remove this item')), 'html' => TRUE));
      $rows[] = array('data' => $row, 'class' => array('draggable'), 'id' => 'views-row-' . $id);
    }
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No fields available.'), 'colspan' => '2'));
  }

  $header = array('', t('Weight'), t('Remove'));
  $output = drupal_render($form['override']);
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'arrange')));
  $output .= drupal_render_children($form);
  drupal_add_tabledrag('arrange', 'order', 'sibling', 'weight');

  return $output;
}

/**
 * Submit handler for rearranging form
 */
function views_ui_rearrange_form_submit($form, &$form_state) {
  $types = views_object_types();
  $display = &$form_state['view']->display[$form_state['display_id']];

  $old_fields = $display->handler->get_option($types[$form_state['type']]['plural']);
  $new_fields = $order = array();

  // Make an array with the weights
  foreach ($form_state['values'] as $field => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
      $order[$field] = $info['weight'];
    }
  }

  // Sort the array
  asort($order);

  // Create a new list of fields in the new order.
  foreach (array_keys($order) as $field) {
    $new_fields[$field] = $old_fields[$field];
  }
  $display->handler->set_option($types[$form_state['type']]['plural'], $new_fields);

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to rearrange items in the views UI.
 */
function views_ui_rearrange_filter_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = views_object_types();
  if (!$view->set_display($display_id)) {
    views_ajax_render(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];
  $form['#title'] = check_plain($display->display_title) . ': ';
  $form['#title'] .= t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'rearrange-item';

  if ($display->handler->defaultable_sections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    $display->handler->add_override_button($form, $form_state, $form_state['section']);
  }

  if (!empty($view->form_cache)) {
    $groups = $view->form_cache['groups'];
    $handlers = $view->form_cache['handlers'];
  }
  else {
    $groups = $display->handler->get_option('filter_groups');
    $handlers = $display->handler->get_option($types[$type]['plural']);
  }
  $count = 0;

  // Get relationship labels
  $relationships = array();
  foreach ($display->handler->get_handlers('relationship') as $id => $handler) {
    $relationships[$id] = $handler->label();
  }

  $group_options = array();

  /**
   * Filter groups is an array that contains:
   * array(
   *   'operator' => 'and' || 'or',
   *   'groups' => array(
   *     $group_id => 'and' || 'or',
   *   ),
   * );
   */

  $grouping = count(array_keys($groups['groups'])) > 1;

  $form['filter_groups']['#tree'] = TRUE;
  $form['filter_groups']['operator'] = array(
    '#type' => 'select',
    '#options' => array (
      'AND' => t('And'),
      'OR' => t('Or'),
    ),
    '#default_value' => $groups['operator'],
    '#attributes' => array(
      'class' => 'warning-on-change',
    ),
    '#title' => t('Operator to use on all groups'),
    '#description' => t('Either "group 0 AND group 1 AND group 2" or "group 0 OR group 1 OR group 2", etc'),
    '#access' => $grouping,
  );

  $form['remove_groups']['#tree'] = TRUE;

  foreach ($groups['groups'] as $id => $group) {
    $form['filter_groups']['groups'][$id] = array(
      '#title' => t('Group operator'),
      '#type' => 'select',
      '#options' => array(
        'AND' => t('And'),
        'OR' => t('Or'),
      ),
      '#default_value' => $group,
      '#attributes' => array(
        'class' => array('warning-on-change'),
      ),
    );

    $form['remove_groups'][$id] = array(); // to prevent a notice
    if ($id != 0) {
      $form['remove_groups'][$id] = array(
        '#type' => 'submit',
        '#value' => t('Remove group @group', array('@group' => $id)),
        '#group' => $id,
      );
    }
    $group_options[$id] = $id == 0 ? t('Default group') : t('Group @group', array('@group' => $id));
    $form['#group_renders'][$id] = array();
  }

  $form['#group_options'] = $group_options;
  $form['#groups'] = $groups;
  // We don't use get_handlers() because we want items without handlers to
  // appear and show up as 'broken' so that the user can see them.
  $form['filters'] = array('#tree' => TRUE);
  foreach ($handlers as $id => $field) {
    // If the group does not exist, move the filters to the default group.
    if (empty($field['group']) || empty($groups['groups'][$field['group']])) {
      $field['group'] = 0;
    }

    $handler = $display->handler->get_handler($type, $id);
    if ($grouping && $handler && !$handler->can_group()) {
      $field['group'] = 'ungroupable';
    }

    // If not grouping and the handler is set ungroupable, move it back to
    // the default group to prevent weird errors from having it be in its
    // own group:
    if (!$grouping && $field['group'] == 'ungroupable') {
      $field['group'] = 0;
    }

    // Place this item into the proper group for rendering.
    $form['#group_renders'][$field['group']][] = $id;

    $form['filters'][$id]['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$count,
    );
    $form['filters'][$id]['group'] = array(
      '#type' => 'select',
      '#options' => $group_options,
      '#default_value' => $field['group'],
      '#attributes' => array(
        'class' => array('views-region-select', 'views-region-' . $id),
      ),
      '#access' => $field['group'] !== 'ungroupable',
    );

    if ($handler) {
      $name = $handler->ui_name() . ' ' . $handler->admin_summary();
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
      }

      $form['filters'][$id]['name'] = array(
        '#markup' => $name,
      );
    }
    else {
      $form['filters'][$id]['name'] = array('#markup' => t('Broken field @id', array('@id' => $id)));
    }
    $form['filters'][$id]['removed'] = array(
      '#type' => 'checkbox',
      '#id' => 'views-removed-' . $id,
      '#attributes' => array('class' => array('views-remove-checkbox')),
      '#default_value' => 0,
    );
  }

  // Add javascript settings that will be added via $.extend for tabledragging
  // Equivalent: drupal_add_tabledrag('arrange', 'order', 'sibling', 'weight');
  $form['#js']['tableDrag']['arrange']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  $form['#js']['tableDrag']['ungroupable_arrange']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  foreach ($form['#group_renders'] as $group_id => $title) {
    // Add javascript settings that will be added via $.extend for tabledragging
    // Equivalent: drupal_add_tabledrag('arrange', 'match', 'sibling', 'views-group-select', 'views-group-' . $group_id);
    $form['#js']['tableDrag']['arrange']['views-group-select'][] = array(
      'target' => 'views-group-' . $group_id,
      'source' => 'views-group-' . $group_id,
      'relationship' => 'sibling',
      'action' => 'match',
      'hidden' => FALSE,
      'limit' => 0,
    );
  }

  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_rearrange_filter_form');
  $form['buttons']['add_group'] = array(
    '#type' => 'submit',
    '#value' => t('Add new group'),
    '#id' => 'views-add-group',
    '#group' => 'add',
  );

  return $form;
}

/**
 * Turn the rearrange form into a proper table
 */
function theme_views_ui_rearrange_filter_form(&$vars) {
  $form = $vars['form'];
  $rows = $ungroupable_rows = array();
  // Enable grouping only if > 1 group.
  $grouping = count(array_keys($form['#group_options'])) > 1;

  foreach ($form['#group_renders'] as $group_id => $contents) {
    // Header row for the group.
    if ($grouping && $group_id !== 'ungroupable') {
      $row = array();
      $row[] = array('class' => array('group', 'group-title'), 'data' => $form['#group_options'][$group_id]);
      $row[] = array('class' => array('group', 'container-inline'), 'data' => drupal_render($form['filter_groups']['groups'][$group_id]), 'colspan' => 3);
      $rows[] = array('class' => array('views-group'), 'data' => $row, 'id' => 'views-group-' . $group_id);
      // Row which will only appear if the group has nothing in it:
      $row = array();
      $class = 'group-' . (count($contents) ? 'populated' : 'empty');
      $row[] = array('colspan' => 4, 'data' => '<span>' . t('This group is empty') . '</span> ' .
      drupal_render($form['remove_groups'][$group_id]));
      $rows[] = array('class' => array("group-message", "group-$group_id-message", $class), 'data' => $row, 'id' => 'views-group-' . $group_id);
    }

    foreach ($contents as $id) {
      if (isset($form['filters'][$id]['name'])) {
        $row = array();
        $row[] = drupal_render($form['filters'][$id]['name']);
        $form['filters'][$id]['weight']['#attributes']['class'] = array('weight');
        $row[] = drupal_render($form['filters'][$id]['weight']);
        $form['filters'][$id]['group']['#attributes']['class'] = array('views-group-select views-group-' . $group_id);
        $row[] = drupal_render($form['filters'][$id]['group']);
        $row[] = drupal_render($form['filters'][$id]['removed']) . l('<span>' . t('Remove') . '</span>', 'javascript:void()', array('attributes' => array('id' => 'views-remove-link-' . $id, 'class' => array('views-hidden', 'views-button-remove', 'views-groups-remove-link'), 'alt' => t('Remove this item'), 'title' => t('Remove this item')), 'html' => true));

        $row = array('data' => $row, 'class' => array('draggable'), 'id' => 'views-row-' . $id);
        if ($group_id !== 'ungroupable') {
          $rows[] = $row;
        }
        else {
          $ungroupable_rows[] = $row;
        }
      }
    }
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No fields available.'), 'colspan' => '2'));
  }

  $output = drupal_render($form['override']);
  if ($grouping) {
    $output .= drupal_render($form['filter_groups']['operator']);
  }
  else {
    $form['filter_groups']['groups'][0]['#title'] = t('Operator');
    $output .= drupal_render($form['filter_groups']['groups'][0]);
  }

  if (!empty($ungroupable_rows)) {
    drupal_add_tabledrag('ungroupable_arrange', 'order', 'sibling', 'weight');
    $header = array(t('Ungroupable filters'), t('Weight'), array('class' => array('views-hide-label'), 'data' => t('Group')), array('class' => array('views-hide-label'), 'data' => t('Remove')));
    $output .= theme('table', array('header' => $header, 'rows' => $ungroupable_rows, 'attributes' => array('id' => 'ungroupable_arrange', 'class' => array('arrange'))));
  }

  drupal_add_tabledrag('arrange', 'order', 'sibling', 'weight');
  $header = array('', t('Weight'), t('Group'), t('Remove'));
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'arrange', 'class' => array('arrange'))));
  $output .= drupal_render_children($form);
  return $output;

}

/**
 * Submit handler for rearranging form
 */
function views_ui_rearrange_filter_form_submit($form, &$form_state) {
  $types = views_object_types();
  $display = &$form_state['view']->display[$form_state['display_id']];
  $remember_groups = array();

  if (!empty($form_state['view']->form_cache)) {
    $old_fields = $form_state['view']->form_cache['handlers'];
  }
  else {
    $old_fields = $display->handler->get_option($types[$form_state['type']]['plural']);
  }
  $count = 0;

  $groups = $form_state['values']['filter_groups'];
  // Whatever button was clicked, re-calculate field information.
  $new_fields = $order = array();

  // Make an array with the weights
  foreach ($form_state['values']['filters'] as $field => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && empty($info['removed'])) {
      if (isset($info['weight'])) {
        $order[$field] = $info['weight'];
      }

      if (isset($info['group'])) {
        $old_fields[$field]['group'] = $info['group'];
        $remember_groups[$info['group']][] = $field;
      }
    }
  }

  // Sort the array
  asort($order);

  // Create a new list of fields in the new order.
  foreach (array_keys($order) as $field) {
    $new_fields[$field] = $old_fields[$field];
  }

  // Save if the actual update button was clicked.
  if (!empty($form_state['clicked_button']['#group'])) {
    if ($form_state['clicked_button']['#group'] == 'add') {
      // Add a new group
      $groups['groups'][] = 'AND';
    }
    else {
      // Renumber groups above the removed one down.
      foreach (array_keys($groups['groups']) as $group_id) {
        if ($group_id >= $form_state['clicked_button']['#group']) {
          $old_group = $group_id + 1;
          if (isset($groups['groups'][$old_group])) {
            $groups['groups'][$group_id] = $groups['groups'][$old_group];
            if (isset($remember_groups[$old_group])) {
              foreach ($remember_groups[$old_group] as $id) {
                $new_fields[$id]['group'] = $group_id;
              }
            }
          }
          else {
            // If this is the last one, just unset it.
            unset($groups['groups'][$group_id]);
          }
        }
      }
    }
    // Update our cache with values so that cancel still works the way
    // people expect.
    $form_state['view']->form_cache = array(
      'key' => 'rearrange-filter',
      'groups' => $groups,
      'handlers' => $new_fields,
    );

    // Return to this form except on actual Update.
    views_ui_add_form_to_stack('rearrange-filter', $form_state['view'], $form_state['display_id'], array($form_state['type']));
  }
  else {
    // Actually write changed handler values.
    $display->handler->set_option($types[$form_state['type']]['plural'], $new_fields);
    $display->handler->set_option('filter_groups', $groups);
    if (isset($form_state['view']->form_cache)) {
      unset($form_state['view']->form_cache);
    }
  }

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to add_item items in the views UI.
 */
function views_ui_add_item_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  ctools_add_js('dependent');

  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];

  $types = views_object_types();
  $ltitle = $types[$type]['ltitle'];

  if (!empty($types[$type]['type'])) {
    $type = $types[$type]['type'];
  }

  $form['#title'] = check_plain($display->display_title) . ': ';
  $form['#title'] .= t('Add @type', array('@type' => $ltitle));
  $form['#section'] = $display_id . 'add-item';

  // Figure out all the base tables allowed based upon what the relationships provide.
  $base_tables = $view->get_base_tables();
  $options = views_fetch_fields(array_keys($base_tables), $type, $display->handler->use_group_by());

  if (!empty($options)) {
    $groups = array('all' => t('- All -'));
    $form['group'] = array(
      '#type' => 'select',
      '#title' => t('Groups'),
      '#options' => array(),
      '#attributes' => array('class' => array('ctools-master-dependent')),
    );

    $form['name'] = array(
      '#prefix' => '<div class="views-radio-box form-checkboxes">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#default_value' => 'all',
    );

    // Group options first to simplify the DOM objects that Views
    // dependent JS will act upon.
    $grouped_options = array();
    foreach ($options as $key => $option) {
      $group = preg_replace('/[^a-z0-9]/', '-', strtolower($option['group']));
      $groups[$group] = $option['group'];
      $grouped_options[$group][$key] = $option;
    }

    foreach ($grouped_options as $group => $group_options) {
      $form['name'][$group . '_start']['#markup'] = '<div class="ctools-dependent-all ctools-dependent-' . $group . '">';
      foreach ($group_options as $key => $option) {
        $form['name'][$key] = array(
          '#type' => 'checkbox',
          '#title' => t('!group: !field', array('!group' => $option['group'], '!field' => $option['title'])),
          '#description' => $option['help'],
          '#return_value' => $key,
        );
      }
      $form['name'][$group . '_end']['#markup'] = '</div>';
    }

    $form['group']['#options'] = $groups;
  }
  else {
    $form['markup'] = array(
      '#markup' => '<div class="form-item">' . t('There are no @types available to add.', array('@types' =>  $types[$type]['ltitle'])) . '</div>',
    );
  }
  ctools_include('dependent');
  views_ui_standard_form_buttons($form, $form_state, 'views_ui_add_item_form', t('Add'));

  return $form;
}

/**
 * Submit handler for adding new item(s) to a view.
 */
function views_ui_add_item_form_submit($form, &$form_state) {
  $type = $form_state['type'];
  $types = views_object_types();

  if (!empty($form_state['values']['name']) && is_array($form_state['values']['name'])) {
    // Loop through each of the items that were checked and add them to the view.
    foreach (array_keys(array_filter($form_state['values']['name'])) as $field) {
      list($table, $field) = explode('.', $field, 2);
      $id = $form_state['view']->add_item($form_state['display_id'], $type, $table, $field);

      // check to see if we have group by settings
      if ($form_state['view']->display_handler->use_group_by()) {
        views_ui_add_form_to_stack('config-item-group', $form_state['view'], $form_state['display_id'], array($type, $id));
      }

      // check to see if this type has settings, if so add the settings form first
      $handler = views_get_handler($table, $field, $type);
      if ($handler && $handler->has_extra_options()) {
        views_ui_add_form_to_stack('config-item-extra', $form_state['view'], $form_state['display_id'], array($type, $id));
      }
      // Then add the form to the stack
      views_ui_add_form_to_stack('config-item', $form_state['view'], $form_state['display_id'], array($type, $id));
    }
  }

  if (isset($form_state['view']->form_cache)) {
    unset($form_state['view']->form_cache);
  }

  // Store in cache
  views_ui_cache_set($form_state['view']);
}


/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array('options' => array('#tree' => TRUE));
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = $view->display_handler->get_handler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#value' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $types = views_object_types();

      if ($view->display_handler->defaultable_sections($types[$type]['plural'])) {
        $form_state['section'] = $types[$type]['plural'];
        $view->display_handler->add_override_button($form['options'], $form_state, $form_state['section']);
      }

      // A whole bunch of code to figure out what relationships are valid for
      // this item.
      $relationships = $view->display_handler->get_option('relationships');
      $relationship_options = array();

      foreach ($relationships as $relationship) {
        // relationships can't link back to self. But also, due to ordering,
        // relationships can only link to prior relationships.
        if ($type == 'relationship' && $id == $relationship['id']) {
          break;
        }
        $relationship_handler = views_get_handler($relationship['table'], $relationship['field'], 'relationship');
        // ignore invalid/broken relationships.
        if (empty($relationship_handler)) {
          continue;
        }

        // If this relationship is valid for this type, add it to the list.
        $data = views_fetch_data($relationship['table']);
        $base = $data[$relationship['field']]['relationship']['base'];
        $base_fields = views_fetch_fields($base, $form_state['type'], $view->display_handler->use_group_by());
        if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
          $relationship_handler->init($view, $relationship);
          $relationship_options[$relationship['id']] = $relationship_handler->label();
        }
      }

      if (!empty($relationship_options)) {
        // Make sure the existing relationship is even valid. If not, force
        // it to none.
        $base_fields = views_fetch_fields($view->base_table, $form_state['type'], $view->display_handler->use_group_by());
        if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
          $relationship_options = array_merge(array('none' => t('Do not use a relationship')), $relationship_options);
        }
        $rel = empty($item['relationship']) ? 'none' : $item['relationship'];
        if (empty($relationship_options[$rel])) {
          // Pick the first relationship.
          $rel = key($relationship_options);
          // We want this relationship option to get saved even if the user
          // skips submitting the form.
          $view->set_item_option($display_id, $type, $id, 'relationship', $rel);
          $temp_view = $view->clone_view();
          views_ui_cache_set($temp_view);
        }

        $form['options']['relationship'] = array(
          '#type' => 'select',
          '#title' => t('Relationship'),
          '#options' => $relationship_options,
          '#default_value' => $rel,
        );
      }
      else {
        $form['options']['relationship'] = array(
          '#type' => 'value',
          '#value' => 'none',
        );
      }

      $form['#title'] = check_plain($view->display[$display_id]->display_title) . ': ';
      $form['#title'] .= t('Configure @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $form['form_description'] = array(
        '#type' => 'markup',
        '#weight' => -1000,
        '#prefix' => '<div class="form-item description">',
        '#suffix' => '</div>',
        '#value' => $handler->definition['help'],
      );

      $form['#section'] = $display_id . '-' . $type . '-' . $id;

      // Get form from the handler.
      $handler->options_form($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    $name = NULL;
    if (isset($form_state['update_name'])) {
      $name = $form_state['update_name'];
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_item_form', $name, t('Remove'), 'remove');
  }
  return $form;
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_form_validate($form, &$form_state) {
  $form_state['handler']->options_validate($form['options'], $form_state);

  if (form_get_errors()) {
    $form_state['rerender'] = TRUE;
  }
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_submit($form['options'], $form_state);
  $item = $form_state['handler']->options;
  $types = views_object_types();

  $type = $form_state['type'];
  if (!empty($types[$type]['type'])) {
    $type = $types[$type]['type'];
  }

  // Create a new handler and unpack the options from the form onto it. We
  // can use that for storage.
  $handler = views_get_handler($item['table'], $item['field'], $type);
  $handler->init($form_state['view'], $item);


  // Add the incoming options to existing options because items using
  // the extra form may not have everything in the form here.
  $options = $form_state['values']['options'] + $form_state['handler']->options;

  // This unpacks only options that are in the definition, ensuring random
  // extra stuff on the form is not sent through.
  $handler->unpack_options($handler->options, $options, NULL, FALSE);

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $handler->options);
  if ($form_state['handler'] && $form_state['handler']->needs_style_plugin()) {
    views_ui_add_form_to_stack('change-style', $form_state['view'], $form_state['display_id'], array($form_state['type'], $form_state['id']), TRUE);
  }

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_group_form($type, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array('options' => array('#tree' => TRUE));
  if (!$view->set_display($display_id)) {
    views_ajax_render(t('Invalid display id @display', array('@display' => $display_id)));
  }

  $view->init_query();

  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = $view->display_handler->get_handler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#value' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $handler->groupby_form($form, $form_state);
      $form_state['handler'] = &$handler;
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_item_group_form');
  }
  return $form;
}

/**
 * Submit handler for configing group settings on a view.
 */
function views_ui_config_item_group_form_submit($form, &$form_state) {
  $item =& $form_state['handler']->options;
  $type = $form_state['type'];
  $id = $form_state['id'];

  $handler = views_get_handler($item['table'], $item['field'], $type);
  $handler->init($form_state['view'], $item);

  $handler->groupby_form_submit($form, $form_state);

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Submit handler for removing an item from a view
 */
function views_ui_config_item_form_remove($form, &$form_state) {
  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], NULL);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_config_item_form_expose($form, &$form_state) {
  $item = &$form_state['handler']->options;
  // flip
  $item['exposed'] = empty($item['exposed']);

  // If necessary, set new defaults:
  if ($item['exposed']) {
    $form_state['handler']->expose_options();
  }

  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  $form_state['force_expose_options'] = TRUE;
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_extra_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array('options' => array('#tree' => TRUE));
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = views_get_handler($item['table'], $item['field'], $type);
    if (empty($handler)) {
      $form['markup'] = array('#value' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = views_object_types();

      $form['#title'] = check_plain($view->display[$display_id]->display_title) . ': ';
      $form['#title'] .= t('Configure extra settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $form['#section'] = $display_id . '-' . $type . '-' . $id;

      // Get form from the handler.
      $handler->extra_options_form($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_item_extra_form');
  }
  return $form;
}

/**
 * Validation handler for configing new item(s) to a view.
 */
function views_ui_config_item_extra_form_validate($form, &$form_state) {
  $form_state['handler']->extra_options_validate($form['options'], $form_state);
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_extra_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->extra_options_submit($form['options'], $form_state);
  $item = $form_state['handler']->options;

  // Store the data we're given.
  foreach ($form_state['values']['options'] as $key => $value) {
    $item[$key] = $value;
  }

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to change_style items in the views UI.
 */
function views_ui_change_style_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array('options' => array('#tree' => TRUE));
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = views_get_handler($item['table'], $item['field'], $type);
    if (empty($handler)) {
      $form['markup'] = array('#value' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = views_object_types();
      $form['#title'] = t('Change summary style for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $form['#section'] = $display_id . '-' . $type . '-' . $id . '-style-plugin';

      $form['style_plugin'] =  array(
        '#type' => 'radios',
        '#options' => views_fetch_plugin_names('style', 'summary'),
        '#default_value' => $item['style_plugin'],
      );

      $form_state['handler'] = &$handler;
    }
    views_ui_standard_form_buttons($form, $form_state, 'views_ui_change_style_form');
  }
  return $form;
}

function views_ui_change_style_form_validate($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_validate($form['options'], $form_state);

  $plugin = views_get_plugin('style', $form_state['values']['style_plugin']);
  if (!$plugin) {
    form_error($form['style_plugin'], t('Internal error: broken plugin.'));
  }
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_change_style_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_submit($form['options'], $form_state);
  $item = $form_state['handler']->options;

  $plugin = views_get_plugin('style', $form_state['values']['style_plugin']);
  if (!$plugin) {
    drupal_set_message(t('Internal error: broken plugin.'), 'error');
    return;
  }

  $plugin->init($form_state['view'], $form_state['view']->display[$form_state['display_id']]);

  // If changing style plugin, reset options to defaults.
  if (empty($item['style_plugin']) || $item['style_plugin'] != $form_state['values']['style_plugin']) {
    $item['style_options'] = $plugin->options;
  }

  // Store the data we're given.
  $item['style_plugin'] = $form_state['values']['style_plugin'];

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  if (!empty($plugin->definition['uses options'])) {
    views_ui_add_form_to_stack('config-style', $form_state['view'], $form_state['display_id'], array($form_state['type'], $form_state['id']), TRUE);
  }

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to config_style items in the views UI.
 */
function views_ui_config_style_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array('options' => array('#tree' => TRUE));
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = views_get_handler($item['table'], $item['field'], $type);
    if (empty($handler)) {
      $form['markup'] = array('#value' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = views_object_types();

      $form['#title'] = check_plain($view->display[$display_id]->display_title) . ': ';
      $form['#title'] .= t('Configure summary style for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $form['#section'] = $display_id . '-' . $type . '-style-options';

      $plugin = views_get_plugin('style', $handler->options['style_plugin']);
      if ($plugin) {
        $form['style_options'] = array(
          '#tree' => TRUE,
        );
        $plugin->init($view, $view->display[$display_id], $handler->options['style_options']);

        $plugin->options_form($form['style_options'], $form_state);
      }

      $form_state['handler'] = &$handler;
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_style_form');
  }
  return $form;
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_style_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_submit($form['style_options'], $form_state);
  $item = $form_state['handler']->options;

  // Store the data we're given.
  $item['style_options'] = $form_state['values']['style_options'];

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Get a list of roles in the system.
 */
function views_ui_get_roles() {
  static $roles = NULL;
  if (!isset($roles)) {
    $roles = array();
    $result = db_query("SELECT r.rid, r.name FROM {role} r ORDER BY r.name");
    foreach ($result as $obj) {
      $roles[$obj->rid] = $obj->name;
    }
  }

  return $roles;
}

/**
 * Page callback for the Views enable page.
 */
function views_ui_enable_page($view) {
  $views_status = variable_get('views_defaults', array());
  $views_status[$view->name] = FALSE; // FALSE is enabled
  variable_set('views_defaults', $views_status);
  views_invalidate_cache();
  menu_rebuild();
  drupal_goto('admin/structure/views');
}

/**
 * Page callback for the Views enable page
 */
function views_ui_disable_page($view) {
  $views_status = variable_get('views_defaults', array());
  $views_status[$view->name] = TRUE; // True is disabled
  variable_set('views_defaults', $views_status);
  views_invalidate_cache();
  menu_rebuild();
  drupal_goto('admin/structure/views');
}

/**
 * Page callback for the tools - other page
 */
function views_ui_admin_tools() {
  $form['clear_cache'] = array(
    '#type' => 'submit',
    '#value' => t("Clear Views' cache"),
    '#submit' => array('views_ui_tools_clear_cache'),
  );

  $form['views_sql_signature'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add Views signature to all SQL queries'),
    '#description' => t("All Views-generated queries will include a special 'VIEWS' = 'VIEWS' string in the WHERE clause. This makes identifying Views queries in database server logs simpler, but should only be used when troubleshooting."),
    '#default_value' => variable_get('views_sql_signature', FALSE),
  );

  $form['views_skip_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable views data caching'),
    '#description' => t("Views caches data about tables, modules and views available, to increase performance. By checking this box, Views will skip this cache and always rebuild this data when needed. This can have a serious performance impact on your site."),
    '#default_value' => variable_get('views_skip_cache', FALSE),
  );

  $form['views_hide_help_message'] = array(
    '#type' => 'checkbox',
    '#title' => t('Ignore missing advanced help module'),
    '#description' => t("Views uses the advanced help module to provide help text; if this module is not present Views will complain, unless this setting is checked."),
    '#default_value' => variable_get('views_hide_help_message', FALSE),
  );

  $form['views_ui_query_on_top'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show query above live preview'),
    '#description' => t("The live preview feature will show you the output of the view you're creating, as well as the view. Check here to show the query and other information above the view; leave this unchecked to show that information below the view."),
    '#default_value' => variable_get('views_ui_query_on_top', FALSE),
  );

  $form['views_ui_disable_live_preview'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable automatic live preview'),
    '#description' => t("Don't automatically update the preview. This can speed up the editing of views a bit.'"),
    '#default_value' => variable_get('views_ui_disable_live_preview', 0),
  );

  $form['views_show_additional_queries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show other queries run during render during live preview'),
    '#description' => t("Drupal has the potential to run many queries while a view is being rendered. Checking this box will display every query run during view render as part of the live preview."),
    '#default_value' => variable_get('views_show_additional_queries', FALSE),
  );

  $form['views_no_hover_links'] = array(
    '#type' => 'checkbox',
    '#title' => t('Do not show hover links over views'),
    '#description' => t("To make it easier to administrate your views, Views provides 'hover' links to take you to the edit and export screen of a view whenever the view is used. This can be distracting on some themes, though; if it is problematic, you can turn it off here."),
    '#default_value' => variable_get('views_no_hover_links', FALSE),
  );

  $form['views_devel_output'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable views performance statistics via the Devel module'),
    '#description' => t("Check this to enable some Views query and performance statistics <em>if Devel is installed</em>."),
    '#default_value' => variable_get('views_devel_output', FALSE),
  );

  $form['views_no_javascript'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable javascript with Views'),
    '#description' => t("If you are having problems with the javascript, you can disable it here; the Views UI should degrade and still be usable without javascript, it just not as good."),
    '#default_value' => variable_get('views_no_javascript', FALSE),
  );

  $form['views_localization_plugin'] =  array(
    '#type' => 'radios',
    '#title' => t('Translation method'),
    '#options' => views_fetch_plugin_names('localization', NULL, array(), TRUE),
    '#default_value' => variable_get('views_localization_plugin', 'core'),
    '#description' => t('Select a translation method to use for Views data like header, footer, and empty text.'),
  );

  $regions = system_region_list(variable_get('theme_default', 'garland'));
  $regions['watchdog'] = t('Watchdog');

  $form['views_devel_region'] = array(
    '#type' => 'select',
    '#title' => t('Page region to output performance statistics'),
    '#default_value' => variable_get('views_devel_region', 'footer'),
    '#options' => $regions,
  );

  $form['views_exposed_filter_any_label'] = array(
    '#type' => 'select',
    '#title' => t('Label for "Any" value on optional single-select exposed filters'),
    '#options' => array('old_any' => '<Any>', 'new_any' => t('- Any -')),
    '#default_value' => variable_get('views_exposed_filter_any_label', 'old_any'),
  );

  return system_settings_form($form);
}

/**
 * Submit hook to clear the views cache.
 */
function views_ui_tools_clear_cache() {
  views_invalidate_cache();
  drupal_set_message(t('The cache has been cleared.'));
}

/**
 * Submit hook to clear Drupal's theme registry (thereby triggering
 * a templates rescan).
 */
function views_ui_config_item_form_rescan($form, &$form_state) {
  drupal_theme_rebuild();

  // The 'Theme: Information' page is about to be shown again. That page
  // analyzes the output of theme_get_registry(). However, this latter
  // function uses an internal cache (which was initialized before we
  // called drupal_theme_rebuild()) so it won't reflect the
  // current state of our theme registry. The only way to clear that cache
  // is to re-initialize the theme system:
  unset($GLOBALS['theme']);
  drupal_theme_initialize();

  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_change_theme($form, &$form_state) {
  // This is just a temporary variable.
  $form_state['view']->theme = $form_state['values']['theme'];

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Page callback for views tag autocomplete
 */
function views_ui_autocomplete_tag($string = '') {
  $matches = array();
  // get matches from default views:
  views_include('view');
  $views = views_discover_default_views();
  foreach ($views as $view) {
    if (!empty($view->tag) && strpos($view->tag, $string) === 0) {
      $matches[$view->tag] = $view->tag;
    }
  }

  $count = 10 - count($matches);
  if ($string) {
    $result = db_query_range("SELECT DISTINCT tag FROM {views_view} WHERE LOWER(tag) LIKE LOWER(:tag)",
      0, $count,
      array(':tag' => $string . '%'));
    foreach ($result as $view) {
      $matches[$view->tag] = check_plain($view->tag);
    }
  }

  drupal_json_output($matches);
}

// ------------------------------------------------------------------
// Get information from the Views data

function _views_weight_sort($a, $b) {
  if ($a['weight'] != $b['weight']) {
    return $a['weight'] < $b['weight'] ? -1 : 1;
  }
  if ($a['title'] != $b['title']) {
    return $a['title'] < $b['title'] ? -1 : 1;
  }

  return 0;
}

/**
 * Fetch a list of all base tables available
 *
 * @return
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_base_tables() {
  static $base_tables = array();
  if (empty($base_tables)) {
    $weights = array();
    $tables = array();
    $data = views_fetch_data();
    foreach ($data as $table => $info) {
      if (!empty($info['table']['base'])) {
        $tables[$table] = array(
          'title' => $info['table']['base']['title'],
          'description' => $info['table']['base']['help'],
          'weight' => !empty($info['table']['base']['weight']) ? $info['table']['base']['weight'] : 0,
        );
      }
    }
    uasort($tables, '_views_weight_sort');
    $base_tables = $tables;
  }

  return $base_tables;
}

function _views_sort_types($a, $b) {
  if ($a['group'] != $b['group']) {
    return $a['group'] < $b['group'] ? -1 : 1;
  }

  if ($a['title'] != $b['title']) {
    return $a['title'] < $b['title'] ? -1 : 1;
  }

  return 0;
}

/**
 * Fetch a list of all fields available for a given base type.
 *
 * @return
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_fields($base, $type, $grouping = FALSE) {
  static $fields = array();
  if (empty($fields)) {
    $data = views_fetch_data();
    $start = microtime(TRUE);
    // This constructs this ginormous multi dimensional array to
    // collect the important data about fields. In the end,
    // the structure looks a bit like this (using nid as an example)
    // $strings['nid']['filter']['title'] = 'string'.
    //
    // This is constructed this way because the above referenced strings
    // can appear in different places in the actual data structure so that
    // the data doesn't have to be repeated a lot. This essentially lets
    // each field have a cheap kind of inheritance.

    foreach ($data as $table => $table_data) {
      $bases = array();
      $strings = array();
      $skip_bases = array();
      foreach ($table_data as $field => $info) {
        // Collect table data from this table
        if ($field == 'table') {
          // calculate what tables this table can join to.
          if (!empty($info['join'])) {
            $bases = array_keys($info['join']);
          }
          // And it obviously joins to itself.
          $bases[] = $table;
          continue;
        }
        foreach (array('field', 'sort', 'filter', 'argument', 'relationship', 'area') as $key) {
          if (!empty($info[$key])) {
            if ($grouping && !empty($info[$key]['no group by'])) {
              continue;
            }
            if (!empty($info[$key]['skip base'])) {
              foreach ((array) $info[$key]['skip base'] as $base_name) {
                $skip_bases[$field][$key][$base_name] = TRUE;
              }
            }
            elseif (!empty($info['skip base'])) {
              foreach ((array) $info['skip base'] as $base_name) {
                $skip_bases[$field][$key][$base_name] = TRUE;
              }
            }
            foreach (array('title', 'group', 'help', 'base') as $string) {
              // First, try the lowest possible level
              if (!empty($info[$key][$string])) {
                $strings[$field][$key][$string] = $info[$key][$string];
              }
              // Then try the field level
              elseif (!empty($info[$string])) {
                $strings[$field][$key][$string] = $info[$string];
              }
              // Finally, try the table level
              elseif (!empty($table_data['table'][$string])) {
                $strings[$field][$key][$string] = $table_data['table'][$string];
              }
              else {
                if ($string != 'base') {
                  $strings[$field][$key][$string] = t("Error: missing @component", array('@component' => $string));
                }
              }
            }
          }
        }
      }
      foreach ($bases as $base_name) {
        foreach ($strings as $field => $field_strings) {
          foreach ($field_strings as $type_name => $type_strings) {
            if (empty($skip_bases[$field][$type_name][$base_name])) {
              $fields[$base_name][$type_name]["$table.$field"] = $type_strings;
            }
          }
        }
      }
    }
//    vsm('Views UI data build time: ' . (views_microtime() - $start) * 1000 . ' ms');
  }

  // If we have an array of base tables available, go through them
  // all and add them together. Duplicate keys will be lost and that's
  // Just Fine.
  if (is_array($base)) {
    $strings = array();
    foreach ($base as $base_table) {
      if (isset($fields[$base_table][$type])) {
        $strings += $fields[$base_table][$type];
      }
    }
    uasort($strings, '_views_sort_types');
    return $strings;
  }

  if (isset($fields[$base][$type])) {
    uasort($fields[$base][$type], '_views_sort_types');
    return $fields[$base][$type];
  }
  return array();
}

/**
 * Fetch a list of all base tables available
 *
 * @param $type
 *   Either 'display', 'style' or 'row'
 * @param $key
 *   For style plugins, this is an optional type to restrict to. May be 'normal',
 *   'summary', 'feed' or others based on the neds of the display.
 * @param $base
 *   An array of possible base tables.
 *
 * @return
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_plugin_names($type, $key = NULL, $base = array()) {
  $data = views_fetch_plugin_data();

  $plugins[$type] = array();

  foreach ($data[$type] as $id => $plugin) {
    // Skip plugins that don't conform to our key.
    if ($key && (empty($plugin['type']) || $plugin['type'] != $key)) {
      continue;
    }
    if (empty($plugin['no ui']) && (empty($base) || empty($plugin['base']) || array_intersect($base, $plugin['base']))) {
      $plugins[$type][$id] = $plugin['title'];
    }
  }

  if (!empty($plugins[$type])) {
    asort($plugins[$type]);
    return $plugins[$type];
  }
  // fall-through
  return array();
}


/**
 * Theme the form for the table style plugin
 */
function theme_views_ui_style_plugin_table($variables) {
  $form = $variables['form'];

  $output = drupal_render($form['description_markup']);

  $header = array(
    t('Field'),
    t('Column'),
    t('Align'),
    t('Separator'),
    array(
      'data' => t('Sortable'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default order'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default sort'),
      'align' => 'center',
    ),
  );
  $rows = array();
  foreach (element_children($form['columns']) as $id) {
    $row = array();
    $row[] = drupal_render($form['info'][$id]['name']);
    $row[] = drupal_render($form['columns'][$id]);
    $row[] = drupal_render($form['info'][$id]['align']);
    $row[] = drupal_render($form['info'][$id]['separator']);
    if (!empty($form['info'][$id]['sortable'])) {
      $row[] = array(
        'data' => drupal_render($form['info'][$id]['sortable']),
        'align' => 'center',
      );
      $row[] = array(
        'data' => drupal_render($form['info'][$id]['default_sort_order']),
        'align' => 'center',
      );
      $row[] = array(
        'data' => drupal_render($form['default'][$id]),
        'align' => 'center',
      );
    }
    else {
      $row[] = '';
      $row[] = '';
      $row[] = '';
    }
    $rows[] = $row;
  }

  // Add the special 'None' row.
  $rows[] = array(t('None'), '', '', '', '', '', array('align' => 'center', 'data' => drupal_render($form['default'][-1])));

  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);
  return $output;
}
