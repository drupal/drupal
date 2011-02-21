<?php
// $Id: path_visibility.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control/visibility based on path.
 */

$plugin = array(
  'title' => t('String: URL path'),
  'description' => t('Control access by the current path.'),
  'callback' => 'ctools_path_visibility_ctools_access_check',
  'settings form' => 'ctools_path_visibility_ctools_access_settings',
  'summary' => 'ctools_path_visibility_ctools_access_summary',
  'required context' =>  new ctools_context_optional(t('Path'), 'string'),
  'default' => array('visibility_setting' => 1, 'paths' => ''),
);

/**
 * Settings form
 */
function ctools_path_visibility_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['note'] = array(
    '#value' => '<div class="description">' . t('Note: if no context is chosen, the current page path will be used.') . '</div>',
  );

  $form['settings']['visibility_setting'] = array(
    '#type' => 'radios',
    '#options' => array(
      1 => t('Allow access on the following pages'),
      0 => t('Allow access on all pages except the following pages'),
    ),
    '#default_value' => $conf['visibility_setting'],
  );

  $form['settings']['paths'] = array(
    '#type' => 'textarea',
    '#title' => t('Paths'),
    '#default_value' => $conf['paths'],
    '#description' => t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>')),
  );
  return $form;
}

/**
 * Check for access.
 */
function ctools_path_visibility_ctools_access_check($conf, $context) {
  if (isset($context->data)) {
    $base_path = $context->data;
  }
  else {
    $base_path = $_GET['q'];
  }

  $path = drupal_get_path_alias($base_path);
  $page_match = drupal_match_path($path, $conf['paths']);

  // If there's a path alias, we may still be at the un-aliased path
  // so check that as well.
  if (!isset($context->data) && $path != $base_path) {
    $page_match = $page_match || drupal_match_path($base_path, $conf['paths']);
  }

  // When $conf['visibility_setting'] has a value of 0, the block is displayed
  // on all pages except those listed in $block->pages. When set to 1, it
  // is displayed only on those pages listed in $block->pages.
  $page_match = !($conf['visibility_setting'] xor $page_match);

  return $page_match;
}

/**
 * Provide a summary description.
 */
function ctools_path_visibility_ctools_access_summary($conf, $context) {
  $paths = array();
  foreach (explode("\n", $conf['paths']) as $path) {
    $paths[] = check_plain($path);
  }

  $identifier = $context->type == 'any' ? t('Current path') : $context->identifier;
  if ($conf['visibility_setting']) {
    return format_plural(count($paths), '@identifier is "@paths"', '@identifier type is one of "@paths"', array('@paths' => implode(', ', $paths), '@identifier' => $identifier));
  }
  else {
    return format_plural(count($paths), '@identifier is not "@paths"', '@identifier type is not one of "@paths"', array('@paths' => implode(', ', $paths), '@identifier' => $identifier));
  }
}
