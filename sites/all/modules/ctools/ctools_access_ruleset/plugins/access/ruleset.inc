<?php
// $Id: ruleset.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based on user rulesetission strings.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => '',
  'description' => '',
  'callback' => 'ctools_ruleset_ctools_access_check',
  'settings form' => 'ctools_ruleset_ctools_access_settings',
  'summary' => 'ctools_ruleset_ctools_access_summary',

  // This access plugin actually just contains child plugins that are
  // exportable, UI configured rulesets.
  'get child' => 'ctools_ruleset_ctools_access_get_child',
  'get children' => 'ctools_ruleset_ctools_access_get_children',
);

/**
 * Merge the main access plugin with a loaded ruleset to form a child plugin.
 */
function ctools_ruleset_ctools_access_merge_plugin($plugin, $parent, $item) {
  $plugin['name'] = $parent . ':' . $item->name;
  $plugin['title'] = check_plain($item->admin_title);
  $plugin['description'] = check_plain($item->admin_description);

  // TODO: Generalize this in CTools.
  if (!empty($item->requiredcontexts)) {
    $plugin['required context'] = array();
    foreach ($item->requiredcontexts as $context) {
      $info = ctools_get_context($context['name']);
      // TODO: allow an optional setting
      $plugin['required context'][] = new ctools_context_required($context['identifier'], $info['context name']);
    }
  }

  // Store the loaded ruleset in the plugin.
  $plugin['ruleset'] = $item;
  return $plugin;
}

/**
 * Get a single child access plugin.
 */
function ctools_ruleset_ctools_access_get_child($plugin, $parent, $child) {
  ctools_include('export');
  $item = ctools_export_crud_load('ctools_access_ruleset', $child);
  if ($item) {
    return ctools_ruleset_ctools_access_merge_plugin($plugin, $parent, $item);
  }
}

/**
 * Get all child access plugins.
 */
function ctools_ruleset_ctools_access_get_children($plugin, $parent) {
  $plugins = array();
  ctools_include('export');
  $items = ctools_export_crud_load_all('ctools_access_ruleset');
  foreach ($items as $name => $item) {
    $child = ctools_ruleset_ctools_access_merge_plugin($plugin, $parent, $item);
    $plugins[$child['name']] = $child;
  }

  return $plugins;
}

/**
 * Settings form for the 'by ruleset' access plugin
 */
function ctools_ruleset_ctools_access_settings(&$form, &$form_state, $conf) {
  $form['markup'] = array(
    '#value' => '<div class="description">' . check_plain($form_state['plugin']['ruleset']->admin_description) . '</div>',
  );
}

/**
 * Check for access.
 */
function ctools_ruleset_ctools_access_check($conf, $context, $plugin) {
  // Load up any contexts we might be using.
  $contexts = ctools_context_match_required_contexts($plugin['ruleset']->requiredcontexts, $context);
  $contexts = ctools_context_load_contexts($plugin['ruleset'], FALSE, $contexts);

  return ctools_access($plugin['ruleset']->access, $contexts);
}

/**
 * Provide a summary description based upon the checked roles.
 */
function ctools_ruleset_ctools_access_summary($conf, $context, $plugin) {
  return check_plain($plugin['ruleset']->admin_description);
}

