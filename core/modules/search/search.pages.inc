<?php

/**
 * @file
 * User page callbacks for the Search module.
 */

use Drupal\Core\Language\Language;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function search_theme_suggestions_search_result(array $variables) {
  return array('search_result__' . $variables['plugin_id']);
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
}

