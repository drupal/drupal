<?php
// $Id: search.pages.inc,v 1.18 2010/04/13 15:23:03 dries Exp $

/**
 * @file
 * User page callbacks for the search module.
 */

/**
 * Menu callback; presents the search form and/or search results.
 */
function search_view($type = 'node') {
  // Search form submits with POST but redirects to GET. This way we can keep
  // the search query URL clean as a whistle:
  // search/type/keyword+keyword
  if (!isset($_POST['form_id'])) {
    if ($type == '') {
      // Note: search/node can not be a default tab because it would take on the
      // path of its parent (search). It would prevent remembering keywords when
      // switching tabs. This is why we drupal_goto to it from the parent instead.
      drupal_goto('search/node');
    }

    $keys = search_get_keys();
    // Only perform search if there is non-whitespace search term:
    $results = '';
    if (trim($keys)) {
      // Log the search keys.
      $info = search_get_info();
      watchdog('search', 'Searched %type for %keys.', array('%keys' => $keys, '%type' => $info[$type]['title']), WATCHDOG_NOTICE, l(t('results'), 'search/' . $type . '/' . $keys));

      // Collect the search results.
      $results = search_data($keys, $type);

      // Construct the search form.
      $build['search_form'] = drupal_get_form('search_form', NULL, $keys, $type);
      $build['search_results'] = array(
        '#theme' => 'search_results_listing',
        '#content' => $results,
      );

      return $build;
    }
  }

  return drupal_get_form('search_form', NULL, empty($keys) ? '' : $keys, $type);
}

/**
 * Returns HTML for a listing of search results.
 *
 * @param $variables
 *   An associative array containing:
 *   - title: The subject of the listing.
 *   - content: The content of the listing.
 *
 * @ingroup themeable
 */
function theme_search_results_listing($variables) {
  $output = '<h2 class="title">' . $variables['title'] . '</h2><div>' . $variables['content'] . '</div>';
  return $output;
}

/**
 * Process variables for search-results.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $results
 * - $type
 *
 * @see search-results.tpl.php
 */
function template_preprocess_search_results(&$variables) {
  $variables['search_results'] = '';
  foreach ($variables['results'] as $result) {
    $variables['search_results'] .= theme('search_result', array('result' => $result, 'type' => $variables['type']));
  }
  $variables['pager'] = theme('pager', array('tags' => NULL));
  $variables['theme_hook_suggestions'][] = 'search_results__' . $variables['type'];
}

/**
 * Process variables for search-result.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $result
 * - $type
 *
 * @see search-result.tpl.php
 */
function template_preprocess_search_result(&$variables) {
  $result = $variables['result'];
  $variables['url'] = check_url($result['link']);
  $variables['title'] = check_plain($result['title']);

  $info = array();
  if (!empty($result['type'])) {
    $info['type'] = check_plain($result['type']);
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
  $variables['theme_hook_suggestions'][] = 'search_result__' . $variables['type'];
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
    // Fall through to the drupal_goto() call.
  }

  $type = $form_state['values']['module'] ? $form_state['values']['module'] : 'node';
  $form_state['redirect'] = $form_state['action'] . '/' . $keys;
  return;
}
