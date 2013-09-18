<?php

/**
 * @file
 * User page callbacks for the Search module.
 */

use Drupal\Core\Language\Language;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Page callback: Presents the search form and/or search results.
 *
 * @param $plugin_id
 *   Search plugin_id to use for the search.
 * @param $keys
 *   Keywords to use for the search.
 *
 * @deprecated Use \Drupal\search\Controller\SearchController::searchView()
 */
function search_view($plugin_id = NULL, $keys = '') {
  $info = FALSE;
  $keys = trim($keys);
  // Also try to pull search keywords out of the $_REQUEST variable to
  // support old GET format of searches for existing links.
  if (!$keys && !empty($_REQUEST['keys'])) {
    $keys = trim($_REQUEST['keys']);
  }

  $manager = \Drupal::service('plugin.manager.search');
  if (!empty($plugin_id)) {
    $active_plugin_info = $manager->getActiveDefinitions();
    if (isset($active_plugin_info[$plugin_id])) {
      $info = $active_plugin_info[$plugin_id];
    }
  }

  if (empty($plugin_id) || empty($info)) {
    // No path or invalid path: find the default plugin. Note that if there
    // are no enabled search plugins, this function should never be called,
    // since hook_menu() would not have defined any search paths.
    $info = search_get_default_plugin_info();
    // Redirect from bare /search or an invalid path to the default search path.
    $path = 'search/' . $info['path'];
    if ($keys) {
      $path .= '/' . $keys;
    }
    return new RedirectResponse(url($path, array('absolute' => TRUE)));
  }
  $plugin = $manager->createInstance($plugin_id);
  $request = \Drupal::request();
  $plugin->setSearch($keys, $request->query->all(), $request->attributes->all());
  // Default results output is an empty string.
  $results = array('#markup' => '');
  // Process the search form. Note that if there is $_POST data,
  // search_form_submit() will cause a redirect to search/[path]/[keys],
  // which will get us back to this page callback. In other words, the search
  // form submits with POST but redirects to GET. This way we can keep
  // the search query URL clean as a whistle.
  if (empty($_POST['form_id']) || $_POST['form_id'] != 'search_form') {
    // Only search if there are keywords or non-empty conditions.
    if ($plugin->isSearchExecutable()) {
      // Log the search keys.
      watchdog('search', 'Searched %type for %keys.', array('%keys' => $keys, '%type' => $info['title']), WATCHDOG_NOTICE, l(t('results'), 'search/' . $info['path'] . '/' . $keys));

      // Collect the search results.
      $results = $plugin->buildResults();
    }
  }
  // The form may be altered based on whether the search was run.
  $build['search_form'] = drupal_get_form('search_form', $plugin);
  $build['search_results'] = $results;

  return $build;
}

/**
 * Prepares variables for search results templates.
 *
 * Default template: search-results.html.twig.
 *
 * @param array $variables
 *   An array with the following elements:
 *   - results: Search results array.
 *   - plugin_id: Plugin the search results came from.
 */
function template_preprocess_search_results(&$variables) {
  $variables['search_results'] = '';
  if (!empty($variables['plugin_id'])) {
    $variables['plugin_id'] = check_plain($variables['plugin_id']);
  }
  foreach ($variables['results'] as $result) {
    $variables['search_results'][] = array(
      '#theme' => 'search_result',
      '#result' => $result,
      '#plugin_id' => $variables['plugin_id'],
    );
  }
  $variables['pager'] = array('#theme' => 'pager');
  // @todo Revisit where this help text is added, see also
  //   http://drupal.org/node/1918856.
  $variables['help'] = search_help('search#noresults', drupal_help_arg());
  $variables['theme_hook_suggestions'][] = 'search_results__' . $variables['plugin_id'];
}

/**
 * Prepares variables for individual search result templates.
 *
 * Default template: search-result.html.twig
 *
 * @param array $variables
 *   An array with the following elements:
 *   - result: Individual search result.
 *   - plugin_id: Plugin the search results came from.
 *   - title_prefix: Additional output populated by modules, intended to be
 *     displayed in front of the main title tag that appears in the template.
 *   - title_suffix: Additional output populated by modules, intended to be
 *     displayed after the main title tag that appears in the template.
 *   - title_attributes: HTML attributes for the title.
 *   - content_attributes: HTML attributes for the content.
 */
function template_preprocess_search_result(&$variables) {
  $language_interface = language(Language::TYPE_INTERFACE);

  $result = $variables['result'];
  $variables['url'] = check_url($result['link']);
  $variables['title'] = check_plain($result['title']);
  if (isset($result['language']) && $result['language'] != $language_interface->id && $result['language'] != Language::LANGCODE_NOT_SPECIFIED) {
    $variables['title_attributes']['lang'] = $result['language'];
    $variables['content_attributes']['lang'] = $result['language'];
  }

  $info = array();
  if (!empty($result['plugin_id'])) {
    $info['plugin_id'] = check_plain($result['plugin_id']);
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
  $variables['theme_hook_suggestions'][] = 'search_result__' . $variables['plugin_id'];
}

/**
 * Form validation handler for search_form().
 *
 * As the search form collates keys from other modules hooked in via
 * hook_form_alter, the validation takes place in search_form_submit().
 * search_form_validate() is used solely to set the 'processed_keys' form
 * value for the basic search form.
 *
 * @see search_form_submit()
 */
function search_form_validate($form, &$form_state) {
  form_set_value($form['basic']['processed_keys'], trim($form_state['values']['keys']), $form_state);
}

/**
 * Form submission handler for search_form().
 *
 * @see search_form_validate()
 */
function search_form_submit($form, &$form_state) {
  $keys = $form_state['values']['processed_keys'];
  if ($keys == '') {
    form_set_error('keys', t('Please enter some keywords.'));
    // Fall through to the form redirect.
  }

  $form_state['redirect'] = $form_state['action'] . '/' . $keys;
}
