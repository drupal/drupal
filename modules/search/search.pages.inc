<?php

/**
 * @file
 * User page callbacks for the search module.
 */

/**
 * Menu callback; presents the search form and/or search results.
 *
 * @param $module
 *   Search module to use for the search.
 * @param $keys
 *   Keywords to use for the search.
 */
function search_view($module = NULL, $keys = '') {
  $info = FALSE;
  $keys = trim($keys);
  // Also try to pull search keywords out of the $_REQUEST variable to
  // support old GET format of searches for existing links.
  if (!$keys && !empty($_REQUEST['keys'])) {
    $keys = trim($_REQUEST['keys']);
  }

  if (!empty($module)) {
    $active_module_info = search_get_info();
    if (isset($active_module_info[$module])) {
      $info = $active_module_info[$module];
    }
  }

  if (empty($info)) {
    // No path or invalid path: find the default module. Note that if there
    // are no enabled search modules, this function should never be called,
    // since hook_menu() would not have defined any search paths.
    $info = search_get_default_module_info();
    // Redirect from bare /search or an invalid path to the default search path.
    $path = 'search/' . $info['path'];
    if ($keys) {
      $path .= '/' . $keys;
    }
    drupal_goto($path);
  }

  // Default results output is an empty string.
  $results = array('#markup' => '');
  // Process the search form. Note that if there is $_POST data,
  // search_form_submit() will cause a redirect to search/[module path]/[keys],
  // which will get us back to this page callback. In other words, the search
  // form submits with POST but redirects to GET. This way we can keep
  // the search query URL clean as a whistle.
  if (empty($_POST['form_id']) || $_POST['form_id'] != 'search_form') {
    $conditions =  NULL;
    if (isset($info['conditions_callback']) && function_exists($info['conditions_callback'])) {
      // Build an optional array of more search conditions.
      $conditions = call_user_func($info['conditions_callback'], $keys);
    }
    // Only search if there are keywords or non-empty conditions.
    if ($keys || !empty($conditions)) {
      // Log the search keys.
      watchdog('search', 'Searched %type for %keys.', array('%keys' => $keys, '%type' => $info['title']), WATCHDOG_NOTICE, l(t('results'), 'search/' . $info['path'] . '/' . $keys));

      // Collect the search results.
      $results = search_data($keys, $info['module'], $conditions);
    }
  }
  // The form may be altered based on whether the search was run.
  $build['search_form'] = drupal_get_form('search_form', NULL, $keys, $info['module']);
  $build['search_results'] = $results;

  return $build;
}

/**
 * Process variables for search-results.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $results: Search results array.
 * - $module: Module the search results came from (module implementing
 *   hook_search_info()).
 *
 * @see search-results.tpl.php
 */
function template_preprocess_search_results(&$variables) {
  $variables['search_results'] = '';
  if (!empty($variables['module'])) {
    $variables['module'] = check_plain($variables['module']);
  }
  foreach ($variables['results'] as $result) {
    $variables['search_results'] .= theme('search_result', array('result' => $result, 'module' => $variables['module']));
  }
  $variables['pager'] = theme('pager', array('tags' => NULL));
  $variables['theme_hook_suggestions'][] = 'search_results__' . $variables['module'];
}

/**
 * Process variables for search-result.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $result
 * - $module
 *
 * @see search-result.tpl.php
 */
function template_preprocess_search_result(&$variables) {
  global $language;

  $result = $variables['result'];
  $variables['url'] = check_url($result['link']);
  $variables['title'] = check_plain($result['title']);
  if (isset($result['language']) && $result['language'] != $language->language && $result['language'] != LANGUAGE_NONE) {
    $variables['title_attributes_array']['xml:lang'] = $result['language'];
    $variables['content_attributes_array']['xml:lang'] = $result['language'];
  }

  $info = array();
  if (!empty($result['module'])) {
    $info['module'] = check_plain($result['module']);
  }
  if (!empty($result['user'])) {
    $info['user'] = $result['user'];
  }
  if (!empty($result['date'])) {
    $info['date'] = format_date($result['date'], 'short');
  }
  if (isset($result['extra']) && is_array($result['extra'])) {
    $info = array_merge($info, $result['extra']);
  }
  // Check for existence. User search does not include snippets.
  $variables['snippet'] = isset($result['snippet']) ? $result['snippet'] : '';
  // Provide separated and grouped meta information..
  $variables['info_split'] = $info;
  $variables['info'] = implode(' - ', $info);
  $variables['theme_hook_suggestions'][] = 'search_result__' . $variables['module'];
}

/**
 * As the search form collates keys from other modules hooked in via
 * hook_form_alter, the validation takes place in _submit.
 * search_form_validate() is used solely to set the 'processed_keys' form
 * value for the basic search form.
 */
function search_form_validate($form, &$form_state) {
  form_set_value($form['basic']['processed_keys'], trim($form_state['values']['keys']), $form_state);
}

/**
 * Process a search form submission.
 */
function search_form_submit($form, &$form_state) {
  $keys = $form_state['values']['processed_keys'];
  if ($keys == '') {
    form_set_error('keys', t('Please enter some keywords.'));
    // Fall through to the form redirect.
  }

  $form_state['redirect'] = $form_state['action'] . '/' . $keys;
}
