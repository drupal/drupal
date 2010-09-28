<?php
// $Id: search.admin.inc,v 1.18 2010/09/28 02:30:31 dries Exp $

/**
 * @file
 * Admin page callbacks for the search module.
 */

/**
 * Menu callback: confirm wiping of the index.
 */
function search_reindex_confirm() {
  return confirm_form(array(), t('Are you sure you want to re-index the site?'),
                  'admin/config/search/settings', t(' The search index is not cleared but systematically updated to reflect the new settings. Searching will continue to work but new content won\'t be indexed until all existing content has been re-indexed. This action cannot be undone.'), t('Re-index site'), t('Cancel'));
}

/**
 * Handler for wipe confirmation
 */
function search_reindex_confirm_submit(&$form, &$form_state) {
  if ($form['confirm']) {
    search_reindex();
    drupal_set_message(t('The index will be rebuilt.'));
    $form_state['redirect'] = 'admin/config/search/settings';
    return;
  }
}

/**
 * Helper function to get real module names.
 */
function _search_get_module_names() {

  $search_info = search_get_info(TRUE);
  $modules = db_select('system', 's')
    ->fields('s', array('name', 'info'))
    ->condition('s.status', 1)
    ->condition('s.type', 'module')
    ->condition('s.name', array_keys($search_info), 'IN')
    ->orderBy('s.name')
    ->execute();
  $names = array();
  foreach ($modules as $item) {
    $info = unserialize($item->info);
    $names[$item->name] = $info['name'];
  }
  return $names;
}

/**
 * Menu callback: displays the search module settings page.
 *
 * @ingroup forms
 *
 * @see search_admin_settings_validate()
 * @see search_admin_settings_submit()
 * @see search_admin_reindex_submit()
 */
function search_admin_settings($form) {
  // Collect some stats
  $remaining = 0;
  $total = 0;
  foreach (variable_get('search_active_modules', array('node', 'user')) as $module) {
    if ($status = module_invoke($module, 'search_status')) {
      $remaining += $status['remaining'];
      $total += $status['total'];
    }
  }

  $count = format_plural($remaining, 'There is 1 item left to index.', 'There are @count items left to index.');
  $percentage = ((int)min(100, 100 * ($total - $remaining) / max(1, $total))) . '%';
  $status = '<p><strong>' . t('%percentage of the site has been indexed.', array('%percentage' => $percentage)) . ' ' . $count . '</strong></p>';
  $form['status'] = array('#type' => 'fieldset', '#title' => t('Indexing status'));
  $form['status']['status'] = array('#markup' => $status);
  $form['status']['wipe'] = array('#type' => 'submit', '#value' => t('Re-index site'), '#submit' => array('search_admin_reindex_submit'));

  $items = drupal_map_assoc(array(10, 20, 50, 100, 200, 500));

  // Indexing throttle:
  $form['indexing_throttle'] = array(
    '#type' => 'fieldset',
    '#title' => t('Indexing throttle')
  );
  $form['indexing_throttle']['search_cron_limit'] = array(
    '#type' => 'select',
    '#title' => t('Number of items to index per cron run'),
    '#default_value' => variable_get('search_cron_limit', 100),
    '#options' => $items,
    '#description' => t('The maximum number of items indexed in each pass of a <a href="@cron">cron maintenance task</a>. If necessary, reduce the number of items to prevent timeouts and memory errors while indexing.', array('@cron' => url('admin/reports/status')))
  );
  // Indexing settings:
  $form['indexing_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Indexing settings')
  );
  $form['indexing_settings']['info'] = array(
    '#markup' => t('<p><em>Changing the settings below will cause the site index to be rebuilt. The search index is not cleared but systematically updated to reflect the new settings. Searching will continue to work but new content won\'t be indexed until all existing content has been re-indexed.</em></p><p><em>The default settings should be appropriate for the majority of sites.</em></p>')
  );
  $form['indexing_settings']['minimum_word_size'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum word length to index'),
    '#default_value' => variable_get('minimum_word_size', 3),
    '#size' => 5,
    '#maxlength' => 3,
    '#description' => t('The number of characters a word has to be to be indexed. A lower setting means better search result ranking, but also a larger database. Each search query must contain at least one keyword that is this size (or longer).')
  );
  $form['indexing_settings']['overlap_cjk'] = array(
    '#type' => 'checkbox',
    '#title' => t('Simple CJK handling'),
    '#default_value' => variable_get('overlap_cjk', TRUE),
    '#description' => t('Whether to apply a simple Chinese/Japanese/Korean tokenizer based on overlapping sequences. Turn this off if you want to use an external preprocessor for this instead. Does not affect other languages.')
  );

  $form['active'] = array(
    '#type' => 'fieldset',
    '#title' => t('Active search modules ')
  );
  $form['active']['search_active_modules'] = array(
    '#type' => 'checkboxes',
    '#default_value' => variable_get('search_active_modules', array('node', 'user')),
    '#options' => _search_get_module_names(),
    '#description' => t('Choose which search modules are active from the available modules.')
  );
  $form['active']['search_default_module'] = array(
    '#title' => t('Default search module'),
    '#type' => 'radios',
    '#default_value' => variable_get('search_default_module', 'node'),
    '#options' => _search_get_module_names(),
    '#description' => t('Choose which search module is the default.')
  );
  $form['#validate'][] = 'search_admin_settings_validate';
  $form['#submit'][] = 'search_admin_settings_submit';

  // Per module settings
  foreach (variable_get('search_active_modules', array('node', 'user')) as $module) {
    $added_form = module_invoke($module, 'search_admin');
    if (is_array($added_form)) {
      $form = array_merge($form, $added_form);
    }
  }

  return system_settings_form($form);
}

/**
 * Form validation handler for search_admin_settings().
 */
function search_admin_settings_validate($form, &$form_state) {
  // Check whether we selected a valid default.
  if ($form_state['clicked_button']['#value'] != t('Reset to defaults')) {
    $new_modules = array_filter($form_state['values']['search_active_modules']);
    $default = $form_state['values']['search_default_module'];
    if (!in_array($default, $new_modules, TRUE)) {
      form_set_error('search_default_module', t('Your default search module is not selected as an active module.'));
    }
  }
}

/**
 * Form submission handler for search_admin_settings().
 */
function search_admin_settings_submit($form, &$form_state) {
  // If these settings change, the index needs to be rebuilt.
  if ((variable_get('minimum_word_size', 3) != $form_state['values']['minimum_word_size']) ||
      (variable_get('overlap_cjk', TRUE) != $form_state['values']['overlap_cjk'])) {
    drupal_set_message(t('The index will be rebuilt.'));
    search_reindex();
  }
  $current_modules = variable_get('search_active_modules', array('node', 'user'));
  // Check whether we are resetting the values.
  if ($form_state['clicked_button']['#value'] == t('Reset to defaults')) {
    $new_modules = array('node', 'user');
  }
  else {
    $new_modules = array_filter($form_state['values']['search_active_modules']);
  }
  if (array_diff($current_modules, $new_modules)) {
    drupal_set_message(t('The active search modules have been changed.'));
    variable_set('menu_rebuild_needed', TRUE);
  }
}

/**
 * Form submission handler for reindex button on search_admin_settings_form().
 */
function search_admin_reindex_submit($form, &$form_state) {
  // send the user to the confirmation page
  $form_state['redirect'] = 'admin/config/search/settings/reindex';
}
