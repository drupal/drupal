<?php
// $Id: page_manager.admin.inc,v 1.45 2010/11/03 00:01:14 merlinofchaos Exp $

/**
 * @file
 * Administrative functions for the page manager.
 *
 * This provides the UI to list, create, edit and delete pages, though much
 * of this is delegated through to individual tasks.
 */

/**
 * Output a list of pages that are managed.
 */
function page_manager_list_page($js = NULL) {
  // Prevent this page from showing up when random other links fail.
  if ($js && $js != 'ajax' && $js != 'nojs') {
    return drupal_not_found();
  }

  // TRUE if 'ajax', FALSE if otherwise.
  $js = $js == 'ajax';

  if (module_exists('advanced_help') && !$js) {
    drupal_set_message(theme('advanced_help_topic', 'page_manager', 'getting-started', t('See the getting started guide for more information.')));
  }

  $tasks = page_manager_get_tasks_by_type('page');
  $pages = array('operations' => array(), 'tasks' => array());

  page_manager_get_pages($tasks, $pages);

  // Add lock icon to all locked tasks.
  global $user;

  ctools_include('object-cache');
  $locks = ctools_object_cache_test_objects('page_manager_page', $pages['tasks']);
  foreach ($locks as $task_name => $lock) {
    if ($lock->uid == $user->uid) {
      $pages['rows'][$task_name]['class'][] = ' page-manager-locked';
      $pages['rows'][$task_name]['title'] = t('This page is currently locked for editing by you. Nobody else may edit this page until these changes are saved or canceled.');
    }
    else {
      $pages['rows'][$task_name]['class'][] = ' page-manager-locked-other';
      $pages['rows'][$task_name]['title'] = t('This page is currently locked for editing by another user. You may not edit this page without breaking the lock.');
    }
  }

  $input = $_POST;

  // Respond to a reset command by clearing session and doing a drupal goto
  // back to the base URL.
  if (isset($input['op']) && $input['op'] == t('Reset')) {
    unset($_SESSION['page_manager']['#admin']);
    if (!$js) {
      return drupal_goto($_GET['q']);
    }
    // clear everything but form id, form build id and form token:
    $keys = array_keys($input);
    foreach ($keys as $id) {
      if ($id != 'form_id' && $id != 'form_build_id' && $id != 'form_token') {
        unset($input[$id]);
      }
    }
    $replace_form = TRUE;
  }
  if (count($input) <= 1) {
    if (isset($_SESSION['page_manager']['#admin']) && is_array($_SESSION['page_manager']['#admin'])) {
      $input  = $_SESSION['page_manager']['#admin'];
    }
  }
  else {
    $_SESSION['page_manager']['#admin'] = $input;
    unset($_SESSION['page_manager']['#admin']['q']);
  }

  $form_state = array(
    'pages' => &$pages,
    'input' => $input,
    'rerender' => TRUE,
    'no_redirect' => TRUE,
  );

  // This form will sort and filter the pages.
  $form = drupal_build_form('page_manager_list_pages_form', $form_state);

  $header = array(
    array('data' => t('Type'), 'class' => array('page-manager-page-type')),
    array('data' => t('Name'), 'class' => array('page-manager-page-name')),
    array('data' => t('Title'), 'class' => array('page-manager-page-title')),
    array('data' => t('Path'), 'class' => array('page-manager-page-path')),
    array('data' => t('Storage'), 'class' => array('page-manager-page-storage')),
  );

  $header[] = array('data' => t('Operations'), 'class' => array('page-manager-page-operations'));
  $table = theme('table', array('header' => $header, 'rows' => $pages['rows'], 'attributes' => array('id' => 'page-manager-list-pages')));

  $operations = '<div id="page-manager-links" class="links">' . theme('links', array('links' => $pages['operations'])) . '</div>';

  drupal_add_css(drupal_get_path('module', 'page_manager') . '/css/page-manager.css');


  if (!$js) {
    return array('#markup' => drupal_render($form) . $table . $operations);
  }

  ctools_include('ajax');
  $commands = array();
  $commands[] = ajax_command_replace('#page-manager-list-pages', $table);
  if (!empty($replace_form)) {
    $commands[] = ajax_command_replace('#page-manager-list-pages-form', $form);
  }
  print ajax_render($commands);
  ajax_footer();
}

/**
 * Sort tasks into buckets based upon whether or not they have subtasks.
 */
function page_manager_get_pages($tasks, &$pages, $task_id = NULL) {
  foreach ($tasks as $id => $task) {
    if (empty($task_id) && !empty($task['page operations'])) {
      $pages['operations'] = array_merge($pages['operations'], $task['page operations']);
    }

    // If a type has subtasks, add its subtasks in its own table.
    if (!empty($task['subtasks'])) {
      page_manager_get_pages(page_manager_get_task_subtasks($task), $pages, $task['name']);
      continue;
    }

    if (isset($task_id)) {
      $task_name = page_manager_make_task_name($task_id, $task['name']);
    }
    else {
      $task_name = $task['name'];
    }

    $class = array('page-task-' . $id);
    if (isset($task['row class'])) {
      $class[] = $task['row class'];
    }

    if (!empty($task['disabled'])) {
      $class[] = 'page-manager-disabled';
    }

    $path = array();
    $visible_path = '';
    if (!empty($task['admin path'])) {
      foreach (explode('/', $task['admin path']) as $bit) {
        if ($bit[0] != '!') {
          $path[] = $bit;
        }
      }

      $path = implode('/', $path);
      if (empty($task['disabled']) && strpos($path, '%') === FALSE) {
        $visible_path = l('/' . $task['admin path'], $path);
      }
      else {
        $visible_path = '/' . $task['admin path'];
      }
    }

    $row = array('data' => array(), 'class' => $class, 'title' => strip_tags($task['admin description']));

    $type = isset($task['admin type']) ? $task['admin type'] : t('System');
    $pages['types'][$type] = $type;
    $row['data']['type'] = array('data' => $type, 'class' => array('page-manager-page-type'));

    $row['data']['name'] = array('data' => $task_name, 'class' => array('page-manager-page-name'));
    $row['data']['title'] = array('data' => $task['admin title'], 'class' => array('page-manager-page-title'));
    $row['data']['path'] = array('data' => $visible_path, 'class' => array('page-manager-page-path'));

    $storage = isset($task['storage']) ? $task['storage'] : t('In code');
    $pages['storages'][$storage] = $storage;
    $row['data']['storage'] = array('data' => $storage, 'class' => array('page-manager-page-storage'));


/*
    if (empty($task['disabled'])) {
      $item = menu_get_item($path);
      if (empty($item)) {
        dsm($path);
      }
      else {
        dsm($item);
      }
    }
*/
    $operations = array(
      array(
        'title' => t('Edit'),
        'href' => page_manager_edit_url($task_name),
      ),
    );

    if (!empty($task['enable callback'])) {
      if (!empty($task['disabled'])) {
        $operations[] = array(
          'title' => t('Enable'),
          'href' => 'admin/structure/pages/nojs/enable/' . $task_name,
          'query' => array('token' => drupal_get_token($task_name)),
        );
      }
      else {
        $operations[] = array(
          'title' => t('Disable'),
          'href' => 'admin/structure/pages/nojs/disable/' . $task_name,
          'query' => array('token' => drupal_get_token($task_name)),
        );
      }
    }

    $row['data']['operations'] = array('data' => theme('links', array('links' => $operations)), 'class' => array('page-manager-page-operations'));

    $pages['disabled'][$task_name] = !empty($task['disabled']);
    $pages['tasks'][] = $task_name;
    $pages['rows'][$task_name] = $row;
  }
}

/**
 * Provide a form for sorting and filtering the list of pages.
 */
function page_manager_list_pages_form($form, &$form_state) {
  // This forces the form to *always* treat as submitted which is
  // necessary to make it work.
  if (empty($_POST)) {
    $form["#programmed"] = TRUE;
  }
  $form['#action'] = url('admin/structure/pages/nojs/', array('absolute' => TRUE));
  if (!variable_get('clean_url', FALSE)) {
    $form['q'] = array(
      '#type' => 'hidden',
      '#value' => $_GET['q'],
    );
  }

  $all = array('all' => t('<All>'));

  $form['type'] = array(
    '#type' => 'select',
    '#title' => t('Type'),
    '#options' => $all + $form_state['pages']['types'],
    '#default_value' => 'all',
  );

  $form['storage'] = array(
    '#type' => 'select',
    '#title' => t('Storage'),
    '#options' => $all + $form_state['pages']['storages'],
    '#default_value' => 'all',
  );

  $form['disabled'] = array(
    '#type' => 'select',
    '#title' => t('Enabled'),
    '#options' => $all + array('0' => t('Enabled'), '1' => t('Disabled')),
    '#default_value' => 'all',
  );

  $form['search'] = array(
    '#type' => 'textfield',
    '#title' => t('Search'),
  );

  $form['order'] = array(
    '#type' => 'select',
    '#title' => t('Sort by'),
    '#options' => array(
      'disabled' => t('Enabled, title'),
      'title' => t('Title'),
      'name' => t('Name'),
      'path' => t('Path'),
      'type' => t('Type'),
      'storage' => t('Storage'),
    ),
    '#default_value' => 'disabled',
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

  $form['submit'] = array(
    '#name' => '', // so it won't in the $_GET args
    '#type' => 'submit',
    '#id' => 'edit-pages-apply',
    '#value' => t('Apply'),
    '#attributes' => array('class' => array('use-ajax-submit ctools-auto-submit-click')),
  );

  $form['reset'] = array(
    '#type' => 'submit',
    '#id' => 'edit-pages-reset',
    '#value' => t('Reset'),
    '#attributes' => array('class' => array('use-ajax-submit')),
  );

  $form['#attached']['js'] = array('misc/ajax.js', 'misc/progress.js', 'misc/jquery.form.js', ctools_attach_js('auto-submit'), ctools_attach_js('page-list', 'page_manager'));
  $form['#prefix'] = '<div class="clearfix">';
  $form['#suffix'] = '</div>';
  $form['#attributes'] = array('class' => array('ctools-auto-submit-full-form'));

  return $form;
}

/**
 * Accept submission from the page manager sort/filter form and apply it
 * to the list of pages.
 */
function page_manager_list_pages_form_submit(&$form, &$form_state) {
  // Filter and re-sort the pages.

  // This is a copy.
  $rows = $form_state['pages']['rows'];

  $sorts = array();
  foreach ($rows as $name => $data) {
    // Filter
    if ($form_state['values']['type'] != 'all' && $form_state['values']['type'] != $data['data']['type']['data']) {
      continue;
    }

    if ($form_state['values']['storage'] != 'all' && $form_state['values']['storage'] != $data['data']['storage']['data']) {
      continue;
    }

    if ($form_state['values']['disabled'] != 'all' && $form_state['values']['disabled'] != $form_state['pages']['disabled'][$name]) {
      continue;
    }

    if ($form_state['values']['search'] &&
        strpos($data['data']['name']['data'], $form_state['values']['search']) === FALSE &&
        strpos($data['data']['path']['data'], $form_state['values']['search']) === FALSE &&
        strpos($data['data']['title']['data'], $form_state['values']['search']) === FALSE) {
          continue;
    }
    // Set up sorting
    switch ($form_state['values']['order']) {
      case 'disabled':
        $sorts[$name] = !$form_state['pages']['disabled'][$name] . $data['data']['title']['data'];
        break;
      case 'title':
        $sorts[$name] = $data['data']['title']['data'];
        break;
      case 'name':
        $sorts[$name] = $data['data']['name']['data'];
        break;
      case 'path':
        $sorts[$name] = $data['data']['path']['data'];
        break;
      case 'type':
        $sorts[$name] = $data['data']['type']['data'];
        break;
      case 'storage':
        $sorts[$name] = $data['data']['storage']['data'];
        break;
    }
  }

  // Now actually sort
  if ($form_state['values']['sort'] == 'desc') {
    arsort($sorts);
  }
  else {
    asort($sorts);
  }

  // Nuke the original.
  $form_state['pages']['rows'] = array();
  // And restore.
  foreach ($sorts as $name => $title) {
    $form_state['pages']['rows'][$name] = $rows[$name];
  }

}

/**
 * Render the edit page for a a page, custom or system.
 */
function page_manager_edit_page($page) {
  drupal_set_title($page->subtask['admin title']);
  // Provide and process the save page form before anything else.
  $form_state = array('page' => &$page);
  $form = drupal_render(drupal_build_form('page_manager_save_page_form', $form_state));

  $operations = page_manager_get_operations($page);
  $args = array('summary');
  $rendered_operations = page_manager_render_operations($page, $operations, $args, array('class' => array('operations-main')), 'nav');
  $content = page_manager_get_operation_content(FALSE, $page, $args, $operations);
  $output = theme('page_manager_edit_page', array('page' => $page, 'save' => $form, 'operations' => $rendered_operations, 'content' => $content));
  return array('#markup' => $output);
}

/**
 * Entry point to edit a single operation for a page.
 *
 * @param $js
 *   Whether or not the page was called via javascript.
 * @param $page
 *   The cached page that is being edited.
 * @param ...
 *   A number of items used to drill down into the actual operation called.
 */
function page_manager_edit_page_operation() {
  $args = func_get_args();
  $js = array_shift($args);
  $page = array_shift($args);

  $operations = page_manager_get_operations($page);
  $content = page_manager_get_operation_content($js, $page, $args, $operations);

  // If the operation requested we go somewhere else afterward, oblige it.
  if (isset($content['new trail'])) {
    $args = $content['new trail'];
    // Get operations again, for the operation may have changed their availability.
    $operations = page_manager_get_operations($page);
    $content = page_manager_get_operation_content($js, $page, $args, $operations);
  }

  // Rendering the content may have been a form submission that changed the
  // operations, such as renaming or adding a handler. Thus we get a new set
  // of operations.
  $operations = page_manager_get_operations($page);
  $rendered_operations = page_manager_render_operations($page, $operations, $args, array('class' => array('operations-main')), 'nav');

  // Since this form should never be submitted to this page, process it late so
  // that we can be sure it notices changes.
  $form_state = array('page' => &$page);
  $form = drupal_render(drupal_build_form('page_manager_save_page_form', $form_state));

  $output = theme('page_manager_edit_page', array('page' => $page, 'save' => $form, 'operations' => $rendered_operations, 'content' => $content));

  if ($js) {
    $commands = array();
    $commands[] = ajax_command_replace('#page-manager-edit', $output);

    print ajax_render($commands);
    ajax_footer();
    return;
  }

  drupal_set_title($page->subtask['admin title']);
  return $output;
}

/**
 * Take the operations array from a task and expand it.
 *
 * This allows some of the operations to be dynamic, based upon settings
 * on the task or the task's handlers. Each operation should have a type. In
 * addition to all the types allowed in page_manager_render_operations, these
 * types will be dynamically replaced with something else:
 * - 'handlers': An automatically created group that contains all the task's
 *   handlers and appropriate links.
 * - 'function': A callback (which will be placed in the 'function' parameter
 *   that should return an array of operations. This can be used to provide
 *   additional, dynamic links if needed.
 */
function page_manager_get_operations($page, $operations = NULL) {
  if (!isset($operations)) {
    // All tasks have at least these 2 ops:
    $operations = array(
      'summary' => array(
        'title' => t('Summary'),
        'description' => t('Get a summary of the information about this page.'),
        'path' => 'admin/structure/pages/edit',
        'ajax' => FALSE,
        'no operations' => TRUE,
        'form info' => array(
          'no buttons' => TRUE,
        ),
        'form' => 'page_manager_page_summary',
      ),
      'actions' => array(
        'type' => 'group',
        'title' => '',
        'class' => array('operations-actions'),
        'location' => 'primary',
        'children' => array(),
      ),
    );

    if (isset($page->subtask['operations'])) {
      $operations += $page->subtask['operations'];
      // add actions separately.
      if (!empty($page->subtask['operations']['actions'])) {
        $operations['actions']['children'] += $page->subtask['operations']['actions']['children'];
      }
    }
    $operations['handlers'] = array('type' => 'handlers');
  }

  $result = array();
  foreach ($operations as $id => $operation) {
    if (empty($operation['type'])) {
      $operation['type'] = 'operation';
    }
    switch ($operation['type']) {
      case 'handlers':
        $result[$id] = page_manager_get_handler_operations($page);
        break;
      case 'function':
        if (function_exists($operation['function'])) {
          $retval = $function($page, $operation);
          if (is_array($retval)) {
            $result[$id] = $retval;
          }
        }
        break;
      default:
        $result[$id] = $operation;
    }
  }

  if (!empty($page->subtask['enable callback']) && !empty($page->subtask['disabled']) && empty($result['actions']['children']['enable'])) {
    $result['actions']['children']['enable'] = array(
      'title' => t('Enable'),
      'description' => t('Activate this page so that it will be in use in your system.'),
      'form' => 'page_manager_enable_form',
      'ajax' => FALSE,
      'silent' => TRUE,
      'no update and save' => TRUE,
      'form info' => array(
        'finish text' => t('Enable'),
      ),
    );
  }

  if (!empty($page->subtask['enable callback']) && empty($page->subtask['disabled']) && empty($result['actions']['children']['disable'])) {
    $result['actions']['children']['disable'] = array(
      'title' => t('Disable'),
      'description' => t('De-activate this page. The data will remain but the page will not be in use on your system.'),
      'form' => 'page_manager_disable_form',
      'ajax' => FALSE,
      'silent' => TRUE,
      'no update and save' => TRUE,
      'form info' => array(
        'finish text' => t('Disable'),
      ),
    );
  }

  $result['actions']['children']['add'] = array(
    'title' => t('Add variant'),
    'description' => t('Add a new variant to this page.'),
    'form' => 'page_manager_handler_add',
    'ajax' => FALSE,
    'silent' => TRUE, // prevents a message about updating and prevents this item from showing as changed.
    'no update and save' => TRUE, // get rid of update and save button which is bad here.
    'form info' => array(
      'finish text' => t('Create variant'),
    ),
  );

  // Restrict variant import to users who can already execute arbitrary PHP
  if (user_access('use PHP for settings')) {
    $result['actions']['children']['import'] = array(
      'title' => t('Import variant'),
      'description' => t('Add a new variant to this page from code exported from another page.'),
      'form' => 'page_manager_handler_import',
    );
  }

  if (count($page->handlers) > 1) {
    $result['actions']['children']['rearrange'] = array(
      'title' => t('Reorder variants'),
      'ajax' => FALSE,
      'description' => t('Change the priority of the variants to ensure that the right one gets selected.'),
      'form' => 'page_manager_handler_rearrange',
    );
  }

  // This is a special operation used to configure a new task handler before
  // it is added.
  if (isset($page->new_handler)) {
  $plugin = page_manager_get_task_handler($page->new_handler->handler);
    $result['actions']['children']['configure'] = array(
      'title' => t('Configure'),
      'description' => t('Configure a newly created variant prior to actually adding it to the page.'),
      'ajax' => FALSE,
      'no update and save' => TRUE, // get rid of update and save button which is bad here.
      'form info' => array(
        // We use our own cancel and finish callback to handle the fun stuff.
        'finish callback' => 'page_manager_handler_add_finish',
        'cancel callback' => 'page_manager_handler_add_cancel',
        'show trail' => TRUE,
        'show back' => TRUE,
        'finish text' => t('Create variant'),
      ),
      'form' => array(
        'forms' => $plugin['forms'],
      ),
    );

    foreach ($page->forms as $id) {
      if (isset($plugin['add features'][$id])) {
        $result['actions']['children']['configure']['form']['order'][$id] = $plugin['add features'][$id];
      }
      else if (isset($plugin['required forms'][$id])) {
        $result['actions']['children']['configure']['form']['order'][$id] = $plugin['required forms'][$id];
      }
    }
  }

  if ($page->locked) {
    $result['actions']['children']['break-lock'] = array(
      'title' => t('Break lock'),
      'description' => t('Break the lock on this page so that you can edit it.'),
      'form' => 'page_manager_break_lock',
      'ajax' => FALSE,
      'no update and save' => TRUE, // get rid of update and save button which is bad here.
      'form info' => array(
        'finish text' => t('Break lock'),
       ),
      'even locked' => TRUE, // show button even if locked
      'silent' => TRUE,
    );
  }

  drupal_alter('page_manager_operations', $result, $page);
  return $result;
}

/**
 * Collect all the operations related to task handlers (variants) and
 * build a menu.
 */
function page_manager_get_handler_operations(&$page) {
  ctools_include('export');
  $group = array(
    'type' => 'group',
    'class' => array('operations-handlers'),
    'title' => t('Variants'),
  );

  $operations = array();

  // If there is only one variant, let's not have it collapsible.
  $collapsible = count($page->handler_info) != 1;
  foreach ($page->handler_info as $id => $info) {
    if ($info['changed'] & PAGE_MANAGER_CHANGED_DELETED) {
      continue;
    }
    $handler = $page->handlers[$id];
    $plugin = page_manager_get_task_handler($handler->handler);

    $operations[$id] = array(
      'type' => 'group',
      'class' => array('operations-handlers-' . $id),
      'title' => page_manager_get_handler_title($plugin, $handler, $page->task, $page->subtask_id),
      'collapsible' => $collapsible,
      'children' => array(),
    );

    $operations[$id]['children']['actions'] = array(
      'type' => 'group',
      'class' => array('operations-handlers-actions-' . $id),
      'title' => t('Variant operations'),
      'children' => array(),
      'location' => $id,
    );

    // There needs to be a 'summary' item here for variants.
    $operations[$id]['children']['summary'] = array(
      'title' => t('Summary'),
      'description' => t('Get a summary of the information about this variant.'),
      'form info' => array(
        'no buttons' => TRUE,
      ),
      'form' => 'page_manager_handler_summary',
    );

    if ($plugin && isset($plugin['operations'])) {
      $operations[$id]['children'] += $plugin['operations'];
    }

    $actions = &$operations[$id]['children']['actions']['children'];

    $actions['clone'] = array(
      'title' => t('Clone'),
      'description' => t('Make an exact copy of this variant.'),
      'form' => 'page_manager_handler_clone',
    );
    $actions['export'] = array(
      'title' => t('Export'),
      'description' => t('Export this variant into code to import into another page.'),
      'form' => 'page_manager_handler_export',
    );
    if ($handler->export_type == (EXPORT_IN_CODE | EXPORT_IN_DATABASE)) {
      $actions['delete'] = array(
        'title' => t('Revert'),
        'description' => t('Remove all changes to this variant and revert to the version in code.'),
        'form' => 'page_manager_handler_delete',
        'no update and save' => TRUE,
        'form info' => array(
          'finish text' => t('Revert'),
        ),
      );
    }
    else if ($handler->export_type != EXPORT_IN_CODE) {
      $actions['delete'] = array(
        'title' => t('Delete'),
        'description' => t('Remove this variant from the page completely.'),
        'form' => 'page_manager_handler_delete',
        'form info' => array(
          'finish text' => t('Delete'),
          'save text' => t('Delete and save'),
        ),
      );
    }
    if (!empty($handler->disabled)) {
      $actions['enable'] = array(
        'title' => t('Enable'),
        'description' => t('Activate this variant so that it will be in use in your system.'),
        'form' => 'page_manager_handler_enable',
        'silent' => TRUE,
        'form info' => array(
          'finish text' => t('Enable'),
          'save text' => t('Enable and save'),
        ),
      );
    }
    else {
      $actions['disable'] = array(
        'title' => t('Disable'),
        'description' => t('De-activate this variant. The data will remain but the variant will not be in use on your system.'),
        'form' => 'page_manager_handler_disable',
        'silent' => TRUE,
        'form info' => array(
          'finish text' => t('Disable'),
          'save text' => t('Disable and save'),
        ),
      );
    }

    drupal_alter('page_manager_variant_operations', $operations[$id], $handler);
  }
  if (empty($operations)) {
    $operations['empty'] = array(
      'type' => 'text',
      'title' => t('No variants'),
    );
  }

  $group['children'] = $operations;
  return $group;
}

/**
 * Get an operation from a trail.
 *
 * @return array($operation, $active, $args)
 */
function page_manager_get_operation($operations, $trail) {
  $args = $trail;
  $stop = FALSE;
  $active = array();
  $titles = array();
  // Drill down into operations array:
  while (!$stop) {
    $check = reset($args);
    $stop = TRUE;
    if (is_array($operations)) {
      if (isset($operations[$check])) {
        $active[] = $check;
        $operation = array_shift($args);
        // check to see if this operation has children. If so, we continue.
        if (!isset($operations[$check]['children'])) {
          $operations = $operations[$check];
        }
        else {
          $titles[] = $operations[$check]['title'];
          $operations = $operations[$check]['children'];
          // continue only if the operation hs children.
          $stop = FALSE;
        }
      }
    }
  }

  return array($operations, $active, $args, $titles);
}

/**
 * Fetch the content for an operation.
 *
 * First, this drills down through the arguments to find the operation, and
 * turns whatever it finds into the active trail which is then used to
 * hilite where we are when rendering the operation list.
 *
 * The arguments are discovered from the URL, and are an exact match for where
 * the operation is in the hierarchy. For example, handlers/foo/settings will
 * be the operation to edit the settings for the handler named foo. This comes
 * in as an array ('handlers', 'foo', 'settings') and is used to find where the
 * data for that operation is in the array.
 */
function page_manager_get_operation_content($js, &$page, $trail, $operations) {
  list($operation, $active, $args, $titles) = page_manager_get_operation($operations, $trail);
  // Once we've found the operation, send it off to render.
  if ($operation) {
    $content = _page_manager_get_operation_content($js, $page, $active, $operation, $titles, $args);
  }

  if (empty($content)) {
    $content = _page_manager_get_operation_content($js, $page, array('summary'), $operations['summary']);
  }

  return $content;
}

/**
 * Fetch the content for an operation, after it's been discovered from arguments.
 *
 * This system runs through the CTools form wizard. Each operation specifies a form
 * or set of forms that it may use. Operations may also specify wrappers and can
 * set their own next/finish handlers so that they can make additional things happen
 * at the end.
 */
function _page_manager_get_operation_content($js, &$page, $active, $operation, $titles = array(), $args = array()) {
  if (isset($operation['form'])) {
    $form_info = array(
      'id' => 'page_manager_page',
      'finish text' => t('Update'),
      'show trail' => FALSE,
      'show back' => FALSE,
      'show return' => FALSE,
      'show cancel' => FALSE,
      'next callback' => 'page_manager_edit_page_next',
      'finish callback' => 'page_manager_edit_page_finish',
      // Items specific to the 'edit' routines that will get moved over:
      'path' => page_manager_edit_url($page->task_name, $active) . "/%step",
      // wrapper function to add an extra finish button.
      'wrapper' => 'page_manager_operation_wrapper',
    );

    // If $operation['form'] is simply a string, then it is the function
    // name of the form.
    if (!is_array($operation['form'])) {
      $form_info['order'] = array(
        'form' => $operation['title'],
      );
      $form_info['forms'] = array(
        'form' => array('form id' => $operation['form']),
      );
      if (isset($operation['wrapper'])) {
        $form_info['forms']['form']['wrapper'] = $operation['wrapper'];
      }
    }
    // Otherwise it's the order and forms arrays directly.
    else {
      $form_info['order'] = $operation['form']['order'];
      $form_info['forms'] = $operation['form']['forms'];
    }

    // Allow the operation to override any form info settings:
    if (isset($operation['form info'])) {
      foreach ($operation['form info'] as $key => $setting) {
        $form_info[$key] = $setting;
      }
    }

    if (!empty($page->subtask['operations include'])) {
      // Quickly load any files necessary to display the forms.
      $page->subtask['operations include']['function'] = 'nop';
      ctools_plugin_get_function($page->subtask, 'operations include');
    }

    $step = array_shift($args);
    // If step is unset, go with the basic step.
    if (!isset($step)) {
      $step = current(array_keys($form_info['order']));
    }

    // If it is locked, hide the buttonzzz!
    if ($page->locked && empty($operation['even locked'])) {
      $form_info['no buttons'] = TRUE;
    }

    ctools_include('wizard');
    $form_state = array(
      'page' => $page,
      'type' => 'edit',
      'ajax' => $js && (!isset($operation['ajax']) || !empty($operation['ajax'])),
      'rerender' => TRUE,
      'trail' => $active,
      'task_name' => $page->task_name,
      'task_id' => $page->task_id,
      'task' => $page->task,
      'subtask_id' => $page->subtask_id,
      'subtask' => $page->subtask,
      'operation' => $operation,
    );

    if ($active && $active[0] == 'handlers' && isset($form_state['page']->handlers[$form_state['trail'][1]])) {
      $form_state['handler_id'] = $form_state['trail'][1];
      $form_state['handler'] = &$form_state['page']->handlers[$form_state['handler_id']];
    }

    if ($active && $active[0] == 'actions' && $active[1] == 'configure' && isset($form_state['page']->new_handler)) {
      $form_state['handler_id'] = $form_state['page']->new_handler->name;
      $form_state['handler'] = &$form_state['page']->new_handler;
    }

    $output = drupal_render(ctools_wizard_multistep_form($form_info, $step, $form_state));
    $title = empty($form_state['title']) ? $operation['title'] : $form_state['title'];
    $titles[] = $title;
    $title = implode(' &raquo ', array_filter($titles));

    // If there are messages for the form, render them.
    if ($messages = theme('status_messages')) {
      $output = $messages . $output;
    }

    $description = isset($operation['admin description']) ? $operation['admin description'] : (isset($operation['description']) ? $operation['description'] : '');
    $return = array(
      'title' => $title,
      'content' => $output,
      'description' => $description,
    );

    // Append any extra content, used for the preview which is submitted then
    // rendered.
    if (isset($form_state['content'])) {
      $return['content'] .= $form_state['content'];
    }

    // If the form wanted us to go somewhere else next, pass that along.
    if (isset($form_state['new trail'])) {
      $return['new trail'] = $form_state['new trail'];
    }
  }
  else {
    $return = array(
      'title' => t('Error'),
      'content' => t('This operation trail does not exist.'),
    );
  }

  $return['active'] = $active;
  return $return;
}

function page_manager_operation_wrapper($form, &$form_state) {
  if (empty($form_state['operation']['no update and save']) && !empty($form['buttons']['return']['#wizard type']) && $form['buttons']['return']['#wizard type']) {
    $form['buttons']['save'] = array(
      '#type' => 'submit',
      '#value' => !empty($form_state['form_info']['save text']) ? $form_state['form_info']['save text'] : t('Update and save'),
      '#wizard type' => 'finish',
      '#attributes' => $form['buttons']['return']['#attributes'],
      '#save' => TRUE,
    );
  }

  return $form;
}

/**
 * Callback generated when the an operation edit finished.
 */
function page_manager_edit_page_finish(&$form_state) {
  if (empty($form_state['operation']['silent'])) {
    if (empty($form_state['clicked_button']['#save'])) {
      drupal_set_message(t('The page has been updated. Changes will not be permanent until you save.'));
    }
    else {
      drupal_set_message(t('The page has been updated and saved.'));
    }
    $path = array();
    foreach ($form_state['trail'] as $operation) {
      $path[] = $operation;
      $form_state['page']->changes[implode('/', $path)] = TRUE;
    }
  }

  // If a handler was modified, set it to changed so we know to overwrite it.
  if (isset($form_state['handler_id'])) {
    $form_state['page']->handler_info[$form_state['handler_id']]['changed'] |= PAGE_MANAGER_CHANGED_CACHED;
  }

  // While we make buttons go away on locked pages, it is still possible to
  // have a lock a appear while you were editing, and have your changes
  // disappear. This at least warns the user that this has happened.
  if (!empty($page->locked)) {
    drupal_set_message(t('Unable to update changes due to lock.'));
  }

  // If the 'Update and Save' button was selected, write our cache out.
  if (!empty($form_state['clicked_button']['#save'])) {
    page_manager_save_page_cache($form_state['page']);
    page_manager_clear_page_cache($form_state['page']->task_name);
    $form_state['page'] = page_manager_get_page_cache($form_state['page']->task_name);
  }
  else {
    if (empty($form_state['do not cache'])) {
      page_manager_set_page_cache($form_state['page']);
    }
  }

  // We basically always want to force a rerender when the forms
  // are finished, so make sure there is a new trail.
  if (empty($form_state['new trail'])) {
    // force a rerender to get rid of old form items that may have changed
    // during save.
    $form_state['new trail'] = $form_state['trail'];
  }

  if (isset($form_state['new trail']) && empty($form_state['ajax'])) {
    $form_state['redirect'] = page_manager_edit_url($form_state['page']->task_name, $form_state['new trail']);
  }

  $form_state['complete'] = TRUE;
}

/**
 * Callback generated when the 'next' button is clicked.
 *
 * All we do here is store the cache.
 */
function page_manager_edit_page_next(&$form_state) {
  page_manager_set_page_cache($form_state['page']);
}

/**
 * Callback generated when the 'cancel' button is clicked.
 *
 * All we do here is clear the cache.
 */
function page_manager_edit_page_cancel(&$form_state) {
  $page = $form_state['page'];
}

/**
 * Render an operations array.
 *
 * This renders an array of operations into a series of nested UL statements,
 * with ajax automatically on unless specified otherwise. Operations will
 * automatically have the URLs generated nested.
 *
 * Each operation should have a 'type', which tells the renderer how to deal
 * with it:
 * - 'operation': An AJAX link to render. This is the default and is
 *   assumed if a type is not specified. Other fields for the operation:
 * - - 'title': The text to display. Can be an image. Must be pre-sanitized.
 * - - 'description': Text to place in the hover box over the link using the
 *     title attribute.
 * - - 'arguments': Anything optional to put at the end of the URL.
 * - - 'path': If set, overrides the default path.
 * - - 'no operations': If set, the path will not have operations appended.
 * - - 'no task': If set, the path will not have the task id.
 * - - 'no link': If set, this item will just be text, not a link.
 * - - 'ajax': If set to TRUE, ajax will be used. The default is TRUE.
 * - - 'class': An optional class to specify for the link.
 * - - 'form': The form to display for this operation, if using a single form.
 * - - 'forms': An array of forms that must be paired with 'order' of this
 *      operation uses multiple forms. See wizard tool for details.
 * - - 'order': The form order to use for multiple forms. See wizard tool for
 *     details.
 * - - 'form info': Form info overrides for the wizard. See the wizard tool
 *      for available settings
 * - 'group':
 * - - 'title': The title of the link. May be HTML.
 * - - 'title class': A class to apply to the title.
 * - - 'children': An array of more operations that this group represents.
 *     All operations within this group will have this group's ID as part
 *     of the AJAX url to make it easier to find.
 * - - 'class': A class to apply to the UL of the children.
 * - - 'collapsible': If TRUE the collapsible tool will be used.
 */
function page_manager_render_operations(&$page, $operations, $active_trail, $attributes, $location, $parents = array()) {
  drupal_add_js('misc/ajax.js');

  if (!isset($output[$location])) {
    $output[$location] = '';
  }

  $keys = array_keys($operations);
  $first = array_shift($keys);
  $last = array_pop($keys);

  // Make sure the 'first' and 'last' operations are part of THIS nav tree:
  while ($keys && isset($operations[$first]['location']) && $operations[$first]['location'] != $location) {
    $first = array_shift($keys);
  }
  while ($keys && isset($operations[$last]['location']) && $operations[$last]['location'] != $location) {
    $last = array_pop($keys);
  }

  $active = reset($active_trail);
  foreach ($operations as $id => $operation) {
    $current_path = '';
    if ($parents) {
      $current_path .= implode('/', $parents) . '/';
    }
    $current_path .= $id;

    if (empty($operation['type'])) {
      $operation['type'] = 'operation';
    }

    // We only render an li for things in the same nav tree.
    if (empty($operation['location']) || $operation['location'] == $location) {
      if (!is_array($attributes['class'])) {
        dsm($attributes['class']);
      }
      $class = empty($attributes['class']) || !is_array($attributes['class']) ? array() : $attributes['class'];
      if ($id == $first) {
        $class[] = 'operation-first';
      }
      else if ($id == $last) {
        $class[] = 'operation-last';
      }

      if (empty($operation['silent']) && !empty($page->changes[$current_path])) {
        $class[] = $operation['type'] == 'group' ? 'changed-group' : 'changed';
      }
      else {
        $class[] = 'not-changed';
      }

      if ($active == $id) {
        $class[] = $operation['type'] == 'group' ? 'active-group' : 'active';
      }
      else {
        $class[] = 'not-active';
      }

      $output[$location] .= '<li class="' . implode(' ', $class) . '">';
    }

    switch ($operation['type']) {
      case 'text':
        $output[$location] .= $operation['title'];
        break;
      case 'operation':
        $path = isset($operation['path']) ? $operation['path'] : 'admin/structure/pages/nojs/operation';
        if (!isset($operation['no task'])) {
          $path .= '/' . $page->task_name;
        }

        if (!isset($operation['no operations'])) {
          $path .= '/' . $current_path;
          if (isset($operation['arguments'])) {
            $path .= '/' . $arguments;
          }
        }

        $class = array('page-manager-operation');
        if (!isset($operation['ajax']) || !empty($operation['ajax'])) {
          $class[] = 'use-ajax';
        }
        if (!empty($operation['class'])) {
          $class[] = $operation['class'];
        }

        $description = isset($operation['description']) ? $operation['description'] : '';
        if (empty($operation['silent']) && !empty($page->changes[$current_path])) {
          $description .= ' ' . t('This setting contains unsaved changes.');
        }

        $output[$location] .= l($operation['title'], $path, array('attributes' => array('id' => 'page-manager-operation-' . $id, 'class' => $class, 'title' => $description), 'html' => TRUE));
        break;
      case 'group':
        if ($active == $id) {
          $trail = $active_trail;
          array_shift($trail);
        }
        else {
          $trail = array();
        }
        $group_location = isset($operation['location']) ? $operation['location'] : $location;
        $temp = page_manager_render_operations($page, $operation['children'], $trail, $operation, $group_location, array_merge($parents, array($id)));
        if ($temp) {
          foreach ($temp as $id => $text) {
            if (empty($output[$id])) {
              $output[$id] = '';
            }
            $output[$id] .= $text;
          }
        }
        break;
    }

    if (empty($operation['location']) || $operation['location'] == $location) {
      $output[$location] .= '</li>';
    }
  }

  if ($output[$location]) {
    $output[$location] = '<ul class="page-manager-operations ' . $attributes['class'] . '">' . $output[$location] . '</ul>';

    if (!empty($attributes['title'])) {
      $class = '';
      if (isset($attributes['title class'])) {
        $class = $attributes['title class'];
      }
      $title = '<div class="page-manager-group-title' . $class . '">' . $attributes['title'] . '</div>';

      if (!empty($attributes['collapsible'])) {
        $output[$location] = theme('ctools_collapsible', array('handle' => $title, 'content' => $output[$location], 'collapsed' => empty($active_trail)));
      }
      else {
        $output[$location] = $title . $output[$location];
      }
    }
    return $output;
  }
}

/**
 * Provide a simple form for saving the page manager info out of the cache.
 */
function page_manager_save_page_form($form, &$form_state) {
  if (!empty($form_state['page']->changed)) {
    $form['markup'] = array(
      '#markup' => '<div class="changed-notification">' . t('You have unsaved changes to this page. You must select Save to write them to the database, or Cancel to discard these changes. Please note that if you have changed any form, you must submit that form before saving.') . '</div>',
    );

    // Always make sure we submit back to the proper page.
    $form['#action'] = url('admin/structure/pages/edit/' . $form_state['page']->task_name);
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => array('page_manager_save_page_form_submit'),
    );

    $form['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => array('page_manager_save_page_form_cancel'),
    );
    return $form;
  }
}

/**
 * Save the page.
 */
function page_manager_save_page_form_submit(&$form, &$form_state) {
  page_manager_save_page_cache($form_state['page']);
}

/**
 * Discard changes to the page.
 */
function page_manager_save_page_form_cancel($form, &$form_state) {
  drupal_set_message(t('All pending changes have been discarded, and the page is now unlocked.'));
  page_manager_clear_page_cache($form_state['page']->task_name);

  if (!empty($form_state['page']->new)) {
    $form_state['redirect'] = 'admin/structure/pages';
  }
}

// --------------------------------------------------------------------------
// Handler (variant) related forms.

/**
 * Add a new task handler.
 */
function page_manager_handler_add($form, &$form_state) {
  // Get a list of possible task handlers for this task.
  return page_manager_handler_add_form($form, $form_state);
}

/**
 * Handler related forms.
 */
function page_manager_handler_add_submit(&$form, &$form_state) {
  $cache = $form_state['page'];
  $plugin = page_manager_get_task_handler($form_state['values']['handler']);

  // Create a new handler.
  $handler = page_manager_new_task_handler($plugin);
  if (!empty($form_state['values']['title'])) {
    $handler->conf['title'] = $form_state['values']['title'];
  }
  else {
    $handler->conf['title'] = $plugin['title'];
  }
  $cache->new_handler = $handler;

  // Figure out which forms to present them with
  $cache->forms = array();

  $features = $form_state['values']['features'];
  if (isset($features[$form_state['values']['handler']])) {
    $cache->forms = array_merge($cache->forms, array_keys(array_filter($features[$form_state['values']['handler']])));
  }

  if (isset($plugin['required forms'])) {
    $cache->forms = array_merge($cache->forms, array_keys($plugin['required forms']));
  }

  $form_state['no_rerender'] = TRUE;
  if (!empty($cache->forms)) {
    // Tell the form to go to the config page.
    drupal_set_message(t('Before this variant can be added, it must be configured. When you are finished, click "Create variant" at the end of this wizard to add this to your page.'));
    $form_state['new trail'] = array('actions', 'configure');
  }
  else {
    // It has no forms at all. Add the variant and go to its first operation.
    page_manager_handler_add_finish($form_state);
  }
}

/**
 * Finish the add process and make the new handler official.
 */
function page_manager_handler_add_finish(&$form_state) {
  $page = &$form_state['page'];
  $handler = $page->new_handler;
  page_manager_handler_add_to_page($page, $handler);

  // Remove the temporary page.
  unset($page->new_handler);
  unset($page->forms);

  // Set the new destination
  $plugin = page_manager_get_task_handler($handler->handler);
  if (!empty($plugin['add finish'])) {
    $location = $plugin['add finish'];
  }
  else {
    $keys = array_keys($plugin['operations']);
    $location = reset($keys);
  }

  $form_state['new trail'] = array('handlers', $handler->name, $location);

  // Pass through.
  page_manager_edit_page_finish($form_state);
}

/**
 * Throw away a new handler and return to the add form
 */
function page_manager_handler_add_cancel(&$form_state) {
  $form_state['new trail'] = array('handlers', 'add');

  // Remove the temporary page.
  unset($page->new_handler);
  unset($page->forms);
}

/**
 * Provide a consistent UI for adding handlers.
 */
function page_manager_handler_add_form($form, $form_state, $features = array()) {
  $task = $form_state['task'];
  $task_handler_plugins = page_manager_get_task_handler_plugins($task);
  if (empty($task_handler_plugins)) {
    drupal_set_message(t('There are currently no variants available and a page may not be added. Perhaps you need to install the Panels module to get a variant?'), 'error');
    $form['buttons']['return']['#disabled'] = TRUE;
    return;
  }

  foreach ($task_handler_plugins as $id => $plugin) {
    $options[$id] = $plugin['title'];
    if (isset($plugin['add features'])) {
      $features[$id] = $plugin['add features'];
    }
  }

  if (!isset($form_state['type']) || $form_state['type'] != 'add') {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('Administrative title of this variant. If you leave blank it will be automatically assigned.'),
    );
  }

  $form['handler'] = array(
    '#title' => t('Variant type'),
    '#type' => 'select',
    '#options' => $options,
  );

  // This set of checkboxes is not dangerous at all.
  $form['features'] = array(
    '#type' => 'checkboxes',
    '#validated' => TRUE,
    '#title' => t('Optional features'),
    '#options' => array(),
    '#description' => t('Check any optional features you need to be presented with forms for configuring them. If you do not check them here you will still be able to utilize these features once the new page is created. If you are not sure, leave these unchecked.'),
    '#tree' => TRUE,
  );

  ctools_include('dependent');
  foreach ($features as $plugin => $feature_list) {
    foreach ($feature_list as $feature_id => $feature) {
      $form['features'][$plugin][$feature_id] = array(
        '#type' => 'checkbox',
        '#title' => $feature,
      );
      if (!empty($form_state['page']->forms) && in_array($feature_id, $form_state['page']->forms)) {
        $form['features'][$plugin][$feature_id]['#default_value'] = TRUE;
      }

      if ($plugin != 'default') {
        $form['features'][$plugin][$feature_id] += array(
          '#process' => array('ctools_dependent_process'),
          '#dependency' => array('edit-handler' => array($plugin)),
        );
      }
    }
  }

  return $form;
}

/**
 * Rearrange the order of variants.
 */
function page_manager_handler_import($form, &$form_state) {
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Variant name'),
    '#description' => t('Enter the name of the new variant.'),
  );

  if (user_access('use PHP for settings')) {
    $form['object'] = array(
      '#type' => 'textarea',
      '#title' => t('Paste variant code here'),
      '#rows' => 15,
    );
  }
  // Users ordinarily can't get here without the PHP block visibility perm.
  // In case they somehow do, though, disable the form widget for extra safety.
  else {
    $form['shoveoff'] = array(
      '#markup' => '<div>' . t('You do not have sufficient permissions to perform this action.') . '</div>',
    );
  }

  return $form;
}

/**
 * Make sure that an import actually provides a handler.
 */
function page_manager_handler_import_validate($form, &$form_state) {
  if (!user_access('use PHP for settings')) {
    form_error($form['shoveoff'], t('You account permissions do not permit you to import.'));
    return;
  }
  ob_start();
  eval($form_state['values']['object']);
  ob_end_clean();

  if (empty($handler)) {
    $errors = ob_get_contents();
    if (empty($errors)) {
      $errors = t('No variant found.');
    }

    form_error($form['object'], t('Unable to get a variant from the import. Errors reported: @errors', array('@errors' => $errors)));
  }

  $form_state['handler'] = $handler;
}

/**
 * Clone an existing task handler into a new handler.
 */
function page_manager_handler_import_submit(&$form, &$form_state) {
  $handler = $form_state['handler'];

  page_manager_handler_add_to_page($form_state['page'], $handler, $form_state['values']['title']);

  $plugin = page_manager_get_task_handler($handler->handler);
  // It has no forms at all. Add the variant and go to its first operation.
  $keys = array_keys($plugin['operations']);
  $form_state['new trail'] = array('handlers', $handler->name, reset($keys));
}

/**
 * Rearrange the order of variants.
 */
function page_manager_handler_rearrange($form, &$form_state) {
  $page = $form_state['page'];

  $form['handlers'] = array('#tree' => TRUE);

  foreach ($page->handler_info as $id => $info) {
    if ($info['changed'] & PAGE_MANAGER_CHANGED_DELETED) {
      continue;
    }
    $handler = $page->handlers[$id];
    $plugin = page_manager_get_task_handler($handler->handler);

    $form['handlers'][$id]['title'] = array(
      '#markup' => page_manager_get_handler_title($plugin, $handler, $page->task, $page->subtask_id),
    );

    $form['handlers'][$id]['weight'] = array(
      '#type' => 'weight',
      '#default_value' => $info['weight'],
      '#delta' => 30,
    );
  }

  return $form;
}

function page_manager_handler_rearrange_submit(&$form, &$form_state) {
  $handler_info = &$form_state['page']->handler_info;

  foreach ($form_state['values']['handlers'] as $id => $info) {
    if ($handler_info[$id]['weight'] = $info['weight']) {
      $handler_info[$id]['weight'] = $info['weight'];
      $handler_info[$id]['changed'] |= PAGE_MANAGER_CHANGED_MOVED;
    }
  }

  // Sort the new cache.
  uasort($handler_info, '_page_manager_handler_sort');

}

/**
 * Used as a callback to uasort to sort the task cache by weight.
 *
 * The 'name' field is used as a backup when weights are the same, which
 * can happen when multiple modules put items out there at the same
 * weight.
 */
function _page_manager_handler_sort($a, $b) {
  if ($a['weight'] < $b['weight']) {
    return -1;
  }
  elseif ($a['weight'] > $b['weight']) {
    return 1;
  }
  elseif ($a['name'] < $b['name']) {
    return -1;
  }
  elseif ($a['name'] > $b['name']) {
    return 1;
  }
}

/**
 * Rearrange the order of variants.
 */
function page_manager_handler_delete($form, &$form_state) {
  if ($form_state['handler']->type == t('Overridden')) {
    $text = t('Reverting the variant will delete the variant that is in the database, reverting it to the original default variant. This deletion will not be made permanent until you click Save.');
  }
  else {
    $text = t('Are you sure you want to delete this variant? This deletion will not be made permanent until you click Save.');
  }
  $form['markup'] = array(
    '#markup' => '<p>' . $text . '</p>',
  );

  return $form;
}

/**
 * Submit handler to delete a view.
 */
function page_manager_handler_delete_submit(&$form, &$form_state) {
  $form_state['page']->handler_info[$form_state['handler_id']]['changed'] |= PAGE_MANAGER_CHANGED_DELETED;
  $form_state['new trail'] = array('summary');
}

/**
 * Entry point to export a page.
 */
function page_manager_handler_export($form, &$form_state) {
  $export = page_manager_export_task_handler($form_state['handler']);

  $lines = substr_count($export, "\n");
  $form['code'] = array(
    '#type' => 'textarea',
    '#default_value' => $export,
    '#rows' => $lines,
  );

  unset($form['buttons']);
  return $form;
}

/**
 * Rearrange the order of variants.
 */
function page_manager_handler_clone($form, &$form_state) {
  // This provides its own button because it does something totally different.
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Variant name'),
    '#description' => t('Enter the name of the new variant.'),
  );

  return $form;
}

/**
 * Clone an existing task handler into a new handler.
 */
function page_manager_handler_clone_submit(&$form, &$form_state) {
  $export = page_manager_export_task_handler($form_state['handler']);
  ob_start();
  eval($export);
  ob_end_clean();

  page_manager_handler_add_to_page($form_state['page'], $handler, $form_state['values']['title']);

  $plugin = page_manager_get_task_handler($handler->handler);
  // It has no forms at all. Add the variant and go to its first operation.
  $keys = array_keys($plugin['operations']);
  $form_state['new trail'] = array('handlers', $handler->name, reset($keys));
}

/**
 * Form to enable a handler.
 */
function page_manager_handler_enable($form, &$form_state) {
  $form['markup'] = array(
    '#markup' => t('This variant is currently disabled. Enabling it will make it available in your system. This will not take effect until you save this page.'),
  );

  return $form;
}

/**
 * Enable the page after it has been confirmed.
 */
function page_manager_handler_enable_submit(&$form, &$form_state) {
  $form_state['handler']->disabled = FALSE;
  $form_state['page']->handler_info[$form_state['handler_id']]['disabled'] = FALSE;
  $form_state['page']->handler_info[$form_state['handler_id']]['changed'] |= PAGE_MANAGER_CHANGED_STATUS;
  $form_state['new trail'] = array('handlers', $form_state['handler_id'], 'actions', 'disable');
}

/**
 * Form to disable a page.
 */
function page_manager_handler_disable($form, &$form_state) {
  $form['markup'] = array(
    '#markup' => t('This variant is currently enabled. Disabling it will make it unavailable in your system, and it will not be used. This will not take effect until you save this page.'),
  );

  return $form;
}

/**
 * Form to disable a page.
 */
function page_manager_handler_summary($form, &$form_state) {
  $handler = $form_state['handler'];
  $page = $form_state['page'];
  $plugin = page_manager_get_task_handler($handler->handler);

  $form['markup'] = array(
    '#markup' => page_manager_get_handler_summary($plugin, $handler, $page, FALSE),
  );

  return $form;
}

/**
 * Disable the page after it has been confirmed.
 */
function page_manager_handler_disable_submit(&$form, &$form_state) {
  $form_state['handler']->disabled = TRUE;
  $form_state['page']->handler_info[$form_state['handler_id']]['disabled'] = TRUE;
  $form_state['page']->handler_info[$form_state['handler_id']]['changed'] |= PAGE_MANAGER_CHANGED_STATUS;
  $form_state['new trail'] = array('handlers', $form_state['handler_id'], 'actions', 'enable');
}

/**
 * Break the lock on a page so that it can be edited.
 */
function page_manager_break_lock($form, &$form_state) {
  $form['markup'] = array(
    '#markup' => t('Breaking the lock on this page will <strong>discard</strong> any pending changes made by the locking user. Are you REALLY sure you want to do this?')
  );

  return $form;
}

/**
 * Submit to break the lock on a page.
 */
function page_manager_break_lock_submit(&$form, &$form_state) {
  $page = &$form_state['page'];
  $form_state['page']->locked = FALSE;
  ctools_object_cache_clear_all('page_manager_page', $page->task_name);
  $form_state['do not cache'] = TRUE;
  drupal_set_message(t('The lock has been cleared and all changes discarded. You may now make changes to this page.'));

  $form_state['new trail'] = array('summary');
}

/**
 * Form to enable a page.
 */
function page_manager_enable_form($form, &$form_state) {
  $form['markup'] = array(
    '#markup' => t('Enabling this page will immediately make it available in your system (there is no need to wait for a save.)'),
  );

  return $form;
}

/**
 * Enable the page after it has been confirmed.
 */
function page_manager_enable_form_submit(&$form, &$form_state) {
  $page = &$form_state['page'];
  if ($function = ctools_plugin_get_function($page->subtask, 'enable callback')) {
    $result = $function($page, FALSE);
    menu_rebuild();
  }
  $form_state['new trail'] = array('actions', 'disable');

  // We don't want to cause this to cache if it wasn't already. If it was
  // cached, however, we want to update the enabled state.
  if (empty($form_state['page']->changed)) {
    $form_state['do not cache'] = TRUE;
  }
}

/**
 * Form to disable a page.
 */
function page_manager_disable_form($form, &$form_state) {
  $form['markup'] = array(
    '#markup' => t('Disabling this page will immediately make it unavailable in your system (there is no need to wait for a save.)'),
  );

  return $form;
}

/**
 * Disable the page after it has been confirmed.
 */
function page_manager_disable_form_submit(&$form, &$form_state) {
  $page = &$form_state['page'];
  if ($function = ctools_plugin_get_function($page->subtask, 'enable callback')) {
    $result = $function($page, TRUE);
      menu_rebuild();
    $form_state['new trail'] = array('actions', 'enable');

    // We don't want to cause this to cache if it wasn't already. If it was
    // cached, however, we want to update the enabled state.
    if (empty($form_state['page']->changed)) {
      $form_state['do not cache'] = TRUE;
    }
  }
}

/**
 * Print the summary information for a page.
 */
function page_manager_page_summary($form, &$form_state) {
  $page = $form_state['page'];

  $output = '';

/*
  if (isset($form_state['subtask']['admin title'])) {
    $form_state['title'] = $form_state['subtask']['admin title'];
  }
*/

  if (isset($form_state['subtask']['admin description'])) {
    $output .= '<div class="description">' . $form_state['subtask']['admin description'] . '</div>';
  }

  $output .= page_manager_get_page_summary($page->task, $page->subtask);

  if (!empty($page->handlers)) {
    foreach ($page->handler_info as $id => $info) {
      if ($info['changed'] & PAGE_MANAGER_CHANGED_DELETED) {
        continue;
      }

      $handler = $page->handlers[$id];
      $plugin = page_manager_get_task_handler($handler->handler);

      $output .= '<div class="handler-summary">';
      $output .= page_manager_get_handler_summary($plugin, $handler, $page);
      $output .= '</div>';

    }
  }
  else {
    $output .= '<p>' . t('This page has no variants and thus no output of its own.') . '</p>';
  }

  $links = array(
    array(
      'title' => ' &raquo; ' . t('Add a new variant'),
      'href' => page_manager_edit_url($page->task_name, array('actions', 'add')),
      'html' => TRUE,
    ),
  );

  $output .= '<div class="links">' . theme('links', array('links' => $links)) . '</div>';
  $form['markup'] = array(
    '#markup' => $output,
  );

  return $form;
}

/**
 * Menu callback to enable or disable a page
 */
function page_manager_enable_page($disable, $js, $page) {
  if (!isset($_GET['token']) || !drupal_valid_token($_GET['token'], $page->task_name)) {
    return MENU_ACCESS_DENIED;
  }
  if ($page->locked) {
    if ($disable) {
      drupal_set_message(t('Unable to disable due to lock.'));
    }
    else {
      drupal_set_message(t('Unable to enable due to lock.'));
    }
  }
  else {
    if ($function = ctools_plugin_get_function($page->subtask, 'enable callback')) {
      $result = $function($page, $disable);
      menu_rebuild();

      // We want to re-cache this if it's changed so that status is properly
      // updated on the changed form.
      if (!empty($page->changed)) {
        page_manager_set_page_cache($page);
      }
    }
  }

  // For now $js is not actually in use on this.
  drupal_goto('admin/structure/pages');
}
