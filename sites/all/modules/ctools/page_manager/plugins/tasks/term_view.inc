<?php
// $Id: term_view.inc,v 1.10 2011/01/06 00:16:28 merlinofchaos Exp $

/**
 * @file
 * Handle the 'term view' override task.
 *
 * This plugin overrides term/%term and reroutes it to the page manager, where
 * a list of tasks can be used to service this request based upon criteria
 * supplied by access plugins.
 */

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_term_view_page_manager_tasks() {
  if (module_exists('taxonomy')) {
    return array(
      // This is a 'page' task and will fall under the page admin UI
      'task type' => 'page',

      'title' => t('Taxonomy term template'),
      'admin title' => t('Taxonomy term template'),
      'admin description' => t('When enabled, this overrides the default Drupal behavior for displaying taxonomy terms at <em>taxonomy/term/%term</em>. If you add variants, you may use selection criteria such as vocabulary or user access to provide different displays of the taxonomy term and associated nodes. If no variant is selected, the default Drupal taxonomy term display will be used. This page only affects items actually displayed ad taxonomy/term/%term. Some taxonomy terms, such as forums, have their displays moved elsewhere. Also please note that if you are using pathauto, aliases may make a taxonomy terms appear somewhere else, but as far as Drupal is concerned, they are still at taxonomy/term/%term.'),
      'admin path' => 'taxonomy/term/%term',
      'admin summary' => 'page_manager_term_view_admin_summary',

      // Menu hooks so that we can alter the term/%term menu entry to point to us.
      'hook menu' => 'page_manager_term_view_menu',
      'hook menu alter' => 'page_manager_term_view_menu_alter',

      // Provide a setting to the primary settings UI for Panels
      'admin settings' => 'page_manager_term_view_admin_settings',
      // Even though we don't have subtasks, this allows us to save our settings.
      'save subtask callback' => 'page_manager_term_view_save',

      // Callback to add items to the page manager task administration form:
      'task admin' => 'page_manager_term_view_task_admin',

      // This is task uses 'context' handlers and must implement these to give the
      // handler data it needs.
      'handler type' => 'context',
      'get arguments' => 'page_manager_term_view_get_arguments',
      'get context placeholders' => 'page_manager_term_view_get_contexts',

      // Allow this to be enabled or disabled:
      'disabled' => variable_get('page_manager_term_view_disabled', TRUE),
      'enable callback' => 'page_manager_term_view_enable',

      // Allow additional operations
      'operations' => array(
        'settings' => array(
          'title' => t('Settings'),
          'description' => t('Edit name, path and other basic settings for the page.'),
          'form' => 'page_manager_term_view_settings',
        ),
      ),
    );
  }
}

/**
 * Callback defined by page_manager_term_view_page_manager_tasks().
 *
 * Alter the term view input so that term view comes to us rather than the
 * normal term view process.
 */
function page_manager_term_view_menu_alter(&$items, $task) {
  if (variable_get('page_manager_term_view_disabled', TRUE)) {
    return;
  }

  // @todo --fix.
  variable_set('page_manager_term_view_disabled', TRUE);
  drupal_set_message(t('Due to changes in taxonomy, the taxonomy integration with Page Manager is currently not implemented. See <a href="http://drupal.org/node/1016510">.'), 'error');
  return;

  // Override the term view handler for our purpose, but only if someone else
  // has not already done so.
  if (isset($items['taxonomy/term/%taxonomy_term']) && $items['taxonomy/term/%taxonomy_term']['page callback'] == 'taxonomy_term_page' || variable_get('page_manager_override_anyway', FALSE)) {
    $items['taxonomy/term/%taxonomy_term']['page callback'] = 'page_manager_term_view_page';
    $items['taxonomy/term/%taxonomy_term']['file path'] = $task['path'];
    $items['taxonomy/term/%taxonomy_term']['file'] = $task['file'];
  }
  else {
    // automatically disable this task if it cannot be enabled.
    variable_set('page_manager_term_view_disabled', TRUE);
    if (!empty($GLOBALS['page_manager_enabling_term_view'])) {
      drupal_set_message(t('Page manager module is unable to enable taxonomy/term/%term because some other module already has overridden with %callback.', array('%callback' => $items['taxonomy/term/%taxonomy_term']['page callback'])), 'error');
    }
  }
}

/**
 * Entry point for our overridden term view.
 *
 * This function asks its assigned handlers who, if anyone, would like
 * to run with it. If no one does, it passes through to Drupal core's
 * term view, which is term_page_view().
 */
function page_manager_term_view_page($term) {
  // While we ordinarily should never actually get feeds through here,
  // just in case
  if ($op != 'feed') {
    // Load my task plugin
    $task = page_manager_get_task('term_view');

    // Load the term into a context.
    ctools_include('context');
    ctools_include('context-task-handler');
    $contexts = ctools_context_handler_get_task_contexts($task, '', array($terms, $depth));

    if (empty($contexts)) {
      return drupal_not_found();
    }

    // Add a fake tab for 'View' so that edit tabs can be added.
    if (user_access('administer page manager')) {
      ctools_include('menu');
      ctools_menu_add_tab(array(
        'title' => t('View'),
        'href' => $_GET['q'],
        'type' => MENU_DEFAULT_LOCAL_TASK,
        'weight' => -10,
      ));
    }

    $output = ctools_context_handler_render($task, '', $contexts, array($terms, $depth, $op));
    if ($output !== FALSE) {
      return $output;
    }
  }

  // Otherwise, fall back.
  module_load_include('inc', 'taxonomy', 'taxonomy.pages');
  return taxonomy_term_page($terms, $depth, $op);
}

/**
 * Callback to get arguments provided by this task handler.
 *
 * Since this is the term view and there is no UI on the arguments, we
 * create dummy arguments that contain the needed data.
 */
function page_manager_term_view_get_arguments($task, $subtask_id) {
  return array(
    array(
      'keyword' => 'term',
      'identifier' => variable_get('page_manager_term_view_type', 'multiple') == 'multiple' ? t('Term(s) being viewed') : t('Term being viewed'),
      'id' => 1,
      'name' => variable_get('page_manager_term_view_type', 'multiple') == 'multiple' ? 'terms' : 'term',
      'settings' => array('input_form' => 'tid', 'breadcrumb' => variable_get('page_manager_taxonomy_breadcrumb', TRUE)),
      'default' => '404',
    ),
    array(
      'keyword' => 'depth',
      'identifier' => t('Depth'),
      'id' => 1,
      'name' => 'string',
      'settings' => array(),
    ),
  );
}

/**
 * Callback to get context placeholders provided by this handler.
 */
function page_manager_term_view_get_contexts($task, $subtask_id) {
  return ctools_context_get_placeholders_from_argument(page_manager_term_view_get_arguments($task, $subtask_id));
}

/**
 * Settings page for this item.
 */
function page_manager_term_view_settings(&$form, &$form_state) {
  // This passes thru because the setting can also appear on the main Panels
  // settings form. If $settings is an array it will just pick up the default.
  $settings = isset($form_state->update_values) ? $form_state->update_values : array();
  page_manager_term_view_admin_settings($form, $settings);
}

/**
 * Copy form values into the page cache.
 */
function page_manager_term_view_settings_submit(&$form, &$form_state) {
  $form_state['page']->update_values = $form_state['values'];
}

/**
 * Save when the page cache is saved.
 */
function page_manager_term_view_save($subtask, $cache) {
  if (isset($cache->update_values)) {
    variable_set('page_manager_term_view_type', $cache->update_values['page_manager_term_view_type']);
    variable_set('page_manager_taxonomy_breadcrumb', $cache->update_values['page_manager_taxonomy_breadcrumb']);
  }
}

/**
 * Provide a setting to the Panels administrative form.
 */
function page_manager_term_view_admin_settings(&$form, $settings = array()) {
  if (empty($settings)) {
    $settings = array(
      'page_manager_term_view_type' => variable_get('page_manager_term_view_type', 'multiple'),
      'page_manager_taxonomy_breadcrumb' => variable_get('page_manager_taxonomy_breadcrumb', TRUE),
    );
  }

  $form['page_manager_term_view_type'] = array(
    '#type' => 'radios',
    '#title' => t('Allow multiple terms on taxonomy/term/%term'),
    '#options' => array('single' => t('Single term'), 'multiple' => t('Multiple terms')),
    '#description' => t('By default, Drupal allows multiple terms as an argument by separating them with commas or plus signs. If you set this to single, that feature will be disabled.'),
    '#default_value' => $settings['page_manager_term_view_type'],
  );
  $form['page_manager_taxonomy_breadcrumb'] = array(
    '#title' => t('Inject hierarchy of first term into breadcrumb trail'),
    '#type' => 'checkbox',
    '#default_value' => $settings['page_manager_taxonomy_breadcrumb'],
    '#description' => t('If checked, taxonomy term parents will appear in the breadcrumb trail.'),
  );
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_term_view_enable($cache, $status) {
  variable_set('page_manager_term_view_disabled', $status);

  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_term_view'] = TRUE;
  }
}

/**
 * Provide a nice administrative summary of the page so an admin can see at a
 * glance what this page does and how it is configured.
 */
function page_manager_term_view_admin_summary($task, $subtask) {
  $task_name = page_manager_make_task_name($task['name'], $subtask['name']);

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Path')),
    array('class' => array('page-summary-data'), 'data' => 'taxonomy/term/%term'),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Access')),
    array('class' => array('page-summary-data'), 'data' => t('This page is publicly accessible.')),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  $menu = t('No menu entry');

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Menu')),
    array('class' => array('page-summary-data'), 'data' => $menu),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  if (variable_get('page_manager_term_view_type', 'multiple') == 'multiple') {
    $message = t('Multiple terms may be used, separated by , or +.');
  }
  else {
    $message = t('Only a single term may be used.');
  }

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('%term')),
    array('class' => array('page-summary-data'), 'data' => $message),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  if (variable_get('page_manager_taxonomy_breadcrumb', TRUE)) {
    $message = t('Breadcrumb trail will contain taxonomy term hierarchy');
  }
  else {
    $message = t('Breadcrumb trail will not contain taxonomy term hiearchy.');
  }

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Breadcrumb')),
    array('class' => array('page-summary-data'), 'data' => $message),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  $output = theme('table', array(), $rows, array('id' => 'page-manager-page-summary'));
  return $output;
}