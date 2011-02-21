<?php
// $Id: page-wizard.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * Fetch metadata on a specific page_wizard plugin.
 *
 * @param $page_wizard
 *   Name of a panel page_wizard.
 *
 * @return
 *   An array with information about the requested panel page_wizard.
 */
function page_manager_get_page_wizard($page_wizard) {
  ctools_include('plugins');
  return ctools_get_plugins('page_manager', 'page_wizards', $page_wizard);
}

/**
 * Fetch metadata for all page_wizard plugins.
 *
 * @return
 *   An array of arrays with information about all available panel page_wizards.
 */
function page_manager_get_page_wizards() {
  ctools_include('plugins');
  return ctools_get_plugins('page_manager', 'page_wizards');
}

/**
 * Get the cached changes to a given wizard.
 *
 * @return
 *   A $cache object or a clean cache object if none could be loaded.
 */
function page_manager_get_wizard_cache($plugin) {
  if (is_string($plugin)) {
    $plugin = page_manager_get_page_wizard($plugin);
  }

  if (empty($plugin)) {
    return;
  }

  ctools_include('object-cache');

  // Since contexts might be cache, include this so they load.
  ctools_include('context');
  $cache = ctools_object_cache_get('page_manager_page_wizard', $plugin['name']);
  if (!$cache) {
    $cache = page_manager_make_wizard_cache($plugin);
  }

  return $cache;
}

function page_manager_make_wizard_cache($plugin) {
  $cache = new stdClass;
  $cache->plugin = $plugin;
  if ($function = ctools_plugin_get_function($plugin, 'default cache')) {
    $function($cache);
  }

  return $cache;
}

/**
 * Store changes to a task handler in the object cache.
 */
function page_manager_set_wizard_cache($cache) {
  ctools_include('object-cache');
  ctools_object_cache_set('page_manager_page_wizard', $cache->plugin['name'], $cache);
}

/**
 * Remove an item from the object cache.
 */
function page_manager_clear_wizard_cache($name) {
  ctools_include('object-cache');
  ctools_object_cache_clear('page_manager_page_wizard', $name);
}

/**
 * Menu callback for the page wizard.
 */
function page_manager_page_wizard($name, $step = NULL) {
  $plugin = page_manager_get_page_wizard($name);
  if (!$plugin) {
    return MENU_NOT_FOUND;
  }

  // Check for simple access string on plugin.
  if (!empty($plugin['access']) && !user_access($plugin['access'])) {
    return MENU_ACCESS_DENIED;
  }

  // Check for possibly more complex access callback on plugin.
  if ($function = ctools_plugin_get_function($plugin, 'access callback') && !$function($plugin)) {
    return MENU_ACCESS_DENIED;
  }

  // Create a basic wizard.in form info array and merge it with the
  // plugin's.
  $form_info = array(
    'id' => 'page_manager_page_wizard',
    'show trail' => TRUE,
    'show back' => TRUE,
    'show return' => FALSE,
    'show cancel' => FALSE,
    'next callback' => 'page_manager_page_wizard_next',
    'finish callback' => 'page_manager_page_wizard_finish',

    'path' => "admin/structure/pages/wizard/$name/%step",
  );

  $form_info = array_merge_recursive($form_info, $plugin['form info']);

  // If step is unset, go with the basic step.
  if (!isset($step)) {
    $step = current(array_keys($form_info['order']));
    $cache = page_manager_make_wizard_cache($plugin);
  }
  else {
    $cache = page_manager_get_wizard_cache($plugin);
  }

  ctools_include('wizard');
  $form_state = array(
    'plugin' => $plugin,
    'wizard cache' => $cache,
    'type' => 'edit',
    'rerender' => TRUE,
    'step' => $step,
  );

  if (isset($plugin['page title'])) {
    drupal_set_title($plugin['page title']);
  }

  if ($function = ctools_plugin_get_function($form_state['plugin'], 'start')) {
    $function($form_info, $step, $form_state);
  }

  $output = ctools_wizard_multistep_form($form_info, $step, $form_state);
  return $output;
}

/**
 * Callback generated when the add page process is finished.
 */
function page_manager_page_wizard_finish(&$form_state) {
  if ($function = ctools_plugin_get_function($form_state['plugin'], 'finish')) {
    $function($form_state);
  }

  page_manager_clear_wizard_cache($form_state['wizard cache']->plugin['name']);
}

/**
 * Callback generated when the 'next' button is clicked.
 *
 * All we do here is store the cache.
 */
function page_manager_page_wizard_next(&$form_state) {
  if ($function = ctools_plugin_get_function($form_state['plugin'], 'next')) {
    $function($form_state);
  }

  page_manager_set_wizard_cache($form_state['wizard cache']);
}

/**
 * Provide a simple administrative list of all wizards.
 *
 * This is called as a page callback, but can also be used by any module
 * that wants to get a list of wizards for its type.
 */
function page_manager_page_wizard_list($type = NULL) {
  $plugins = page_manager_get_page_wizards();
  if (empty($plugins)) {
    return '<p>' . t('There are no wizards available at this time.') . '</p>';
  }

  uasort($plugins, 'ctools_plugin_sort');

  $output = '<dl class="page-manager-wizards">';
  foreach ($plugins as $id => $plugin) {
    if (!$type || (isset($plugin['type']) && $plugin['type'] == $type)) {
      $output .= '<dt>' . l($plugin['title'], 'admin/structure/pages/wizard/' . $id) . '</dt>';
      $output .= '<dd class="description">' . $plugin['description'] . '</dd>';
    }
  }
  $output .= '</dl>';

  return $output;
}
