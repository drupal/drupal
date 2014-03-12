<?php

/**
 * @file
 * Administrative page callbacks for the path module.
 */

use Drupal\Core\Language\Language;
use Drupal\Component\Utility\String;

/**
 * Returns a listing of all defined URL aliases.
 *
 * When filter key passed, perform a standard search on the given key,
 * and return the list of matching URL aliases.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\path\Controller\PathController::adminOverview().
 */
function path_admin_overview($keys = NULL) {
  // Add the filter form above the overview table.
  $build['path_admin_filter_form'] = drupal_get_form('path_admin_filter_form', $keys);
  // Enable language column if language.module is enabled or if we have any
  // alias with a language.
  $alias_exists = (bool) db_query_range('SELECT 1 FROM {url_alias} WHERE langcode <> :langcode', 0, 1, array(':langcode' => Language::LANGCODE_NOT_SPECIFIED))->fetchField();
  $multilanguage = (\Drupal::moduleHandler()->moduleExists('language') || $alias_exists);

  $header = array();
  $header[] = array('data' => t('Alias'), 'field' => 'alias', 'sort' => 'asc');
  $header[] = array('data' => t('System'), 'field' => 'source');
  if ($multilanguage) {
    $header[] = array('data' => t('Language'), 'field' => 'langcode');
  }
  $header[] = t('Operations');

  $query = db_select('url_alias')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  if ($keys) {
    // Replace wildcards with PDO wildcards.
    $query->condition('alias', '%' . preg_replace('!\*+!', '%', $keys) . '%', 'LIKE');
  }
  $result = $query
    ->fields('url_alias')
    ->orderByHeader($header)
    ->limit(50)
    ->execute();

  $rows = array();
  $destination = drupal_get_destination();
  foreach ($result as $data) {
    $row = array();
    $row['data']['alias'] = l(truncate_utf8($data->alias, 50, FALSE, TRUE), $data->source, array(
      'attributes' => array('title' => $data->alias),
    ));
    $row['data']['source'] = l(truncate_utf8($data->source, 50, FALSE, TRUE), $data->source, array(
      'alias' => TRUE,
      'attributes' => array('title' => $data->source),
    ));
    if ($multilanguage) {
      $row['data']['language_name'] = \Drupal::languageManager()->getLanguageName($data->langcode);
    }

    $operations = array();
    $operations['edit'] = array(
      'title' => t('edit'),
      'href' => "admin/config/search/path/edit/$data->pid",
      'query' => $destination,
    );
    $operations['delete'] = array(
      'title' => t('delete'),
      'href' => "admin/config/search/path/delete/$data->pid",
      'query' => $destination,
    );
    $row['data']['operations'] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $operations,
      ),
    );

    // If the system path maps to a different URL alias, highlight this table
    // row to let the user know of old aliases.
    if ($data->alias != \Drupal::service('path.alias_manager')->getPathAlias($data->source, $data->langcode)) {
      $row['class'] = array('warning');
    }

    $rows[] = $row;
  }

  $build['path_table'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No URL aliases available. <a href="@link">Add URL alias</a>.', array('@link' => url('admin/config/search/path/add'))),
  );
  $build['path_pager'] = array('#theme' => 'pager');

  return $build;
}

/**
 * Page callback: Returns a form creating or editing a path alias.
 *
 * @param $path
 *   An array containing the path ID, source, alias, and language code.
 *
 * @return
 *   A form for adding or editing a URL alias.
 *
 * @see path_menu()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal\path\Controller\PathController::adminAdd() or
 *   \Drupal\path\Controller\PathController::adminEdit()
 */
function path_admin_edit($path = array()) {
  if ($path) {
    $output = drupal_get_form('path_admin_form', $path);
    $output['#title'] = String::checkPlain($path['alias']);
  }
  else {
    $output = drupal_get_form('path_admin_form');
  }

  return $output;
}

/**
 * Form constructor for the path administration form.
 *
 * @param $path
 *   An array containing the path ID, source, alias, and language code.
 *
 * @ingroup forms
 * @see path_admin_form_validate()
 * @see path_admin_form_submit()
 * @see path_admin_form_delete_submit()
 */
function path_admin_form($form, &$form_state, $path = array('source' => '', 'alias' => '', 'langcode' => Language::LANGCODE_NOT_SPECIFIED, 'pid' => NULL)) {
  $form['source'] = array(
    '#type' => 'textfield',
    '#title' => t('Existing system path'),
    '#default_value' => $path['source'],
    '#maxlength' => 255,
    '#size' => 45,
    '#description' => t('Specify the existing path you wish to alias. For example: node/28, forum/1, taxonomy/term/1.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    '#required' => TRUE,
  );
  $form['alias'] = array(
    '#type' => 'textfield',
    '#title' => t('Path alias'),
    '#default_value' => $path['alias'],
    '#maxlength' => 255,
    '#size' => 45,
    '#description' => t('Specify an alternative path by which this data can be accessed. For example, type "about" when writing an about page. Use a relative path and don\'t add a trailing slash or the URL alias won\'t work.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    '#required' => TRUE,
  );

  // A hidden value unless language.module is enabled.
  if (\Drupal::moduleHandler()->moduleExists('language')) {
    $languages = language_list();
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language->name;
    }

    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#options' => $language_options,
      '#empty_value' => Language::LANGCODE_NOT_SPECIFIED,
      '#empty_option' => t('- None -'),
      '#default_value' => $path['langcode'],
      '#weight' => -10,
      '#description' => t('A path alias set for a specific language will always be used when displaying this page in that language, and takes precedence over path aliases set as <em>- None -</em>.'),
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $path['langcode']
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  if ($path['pid']) {
    $form['pid'] = array(
      '#type' => 'hidden',
      '#value' => $path['pid'],
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('path_admin_form_delete_submit'),
    );
  }

  return $form;
}

/**
 * Form submission handler for the 'Delete' button on path_admin_form().
 *
 * @see path_admin_form_validate()
 * @see path_admin_form_submit()
 */
function path_admin_form_delete_submit($form, &$form_state) {
  $destination = array();
  $query = \Drupal::request()->query;
  if ($query->has('destination')) {
    $destination = drupal_get_destination();
    $query->remove('destination');
  }
  $form_state['redirect'] = array('admin/config/search/path/delete/' . $form_state['values']['pid'], array('query' => $destination));
}

/**
 * Form validation handler for path_admin_form().
 *
 * @see path_admin_form_submit()
 * @see path_admin_form_delete_submit()
 */
function path_admin_form_validate($form, &$form_state) {
  $source = &$form_state['values']['source'];
  $source = \Drupal::service('path.alias_manager')->getSystemPath($source);
  $alias = $form_state['values']['alias'];
  $pid = isset($form_state['values']['pid']) ? $form_state['values']['pid'] : 0;
  // Language is only set if language.module is enabled, otherwise save for all
  // languages.
  $langcode = isset($form_state['values']['langcode']) ? $form_state['values']['langcode'] : Language::LANGCODE_NOT_SPECIFIED;

  $has_alias = db_query("SELECT COUNT(alias) FROM {url_alias} WHERE pid <> :pid AND alias = :alias AND langcode = :langcode", array(
      ':pid' => $pid,
      ':alias' => $alias,
      ':langcode' => $langcode,
    ))
    ->fetchField();

  if ($has_alias) {
    form_set_error('alias', $form_state, t('The alias %alias is already in use in this language.', array('%alias' => $alias)));
  }
  if (!drupal_valid_path($source)) {
    form_set_error('source', $form_state, t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $source)));
  }
}

/**
 * Form submission handler for path_admin_form().
 *
 * @see path_admin_form_validate()
 * @see path_admin_form_delete_submit()
 */
function path_admin_form_submit($form, &$form_state) {
  // Remove unnecessary values.
  form_state_values_clean($form_state);

  $pid = isset($form_state['values']['pid']) ? $form_state['values']['pid'] : 0;
  $source = &$form_state['values']['source'];
  $source = \Drupal::service('path.alias_manager')->getSystemPath($source);
  $alias = $form_state['values']['alias'];
  // Language is only set if language.module is enabled, otherwise save for all
  // languages.
  $langcode = isset($form_state['values']['langcode']) ? $form_state['values']['langcode'] : Language::LANGCODE_NOT_SPECIFIED;

  \Drupal::service('path.crud')->save($source, $alias, $langcode, $pid);

  drupal_set_message(t('The alias has been saved.'));
  $form_state['redirect'] = 'admin/config/search/path';
}

/**
 * Form constructor for the path admin overview filter form.
 *
 * @ingroup forms
 * @see path_admin_filter_form_submit_filter()
 * @see path_admin_filter_form_submit_reset()
 */
function path_admin_filter_form($form, &$form_state, $keys = '') {
  $form['#attributes'] = array('class' => array('search-form'));
  $form['basic'] = array(
    '#type' => 'details',
    '#title' => t('Filter aliases'),
    '#open' => TRUE,
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['basic']['filter'] = array(
    '#type' => 'search',
    '#title' => 'Path alias',
    '#title_display' => 'invisible',
    '#default_value' => $keys,
    '#maxlength' => 128,
    '#size' => 25,
  );
  $form['basic']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#submit' => array('path_admin_filter_form_submit_filter'),
    );
  if ($keys) {
    $form['basic']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
      '#submit' => array('path_admin_filter_form_submit_reset'),
    );
  }
  return $form;
}

/**
 * Form submission handler for the path_admin_filter_form() Filter button.
 *
 * @see path_admin_filter_form_submit_reset()
 */
function path_admin_filter_form_submit_filter($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/search/path/list/' . trim($form_state['values']['filter']);
}

/**
 * Form submission handler for the path_admin_filter_form() Reset button.
 *
 * @see path_admin_filter_form_submit_filter()
 */
function path_admin_filter_form_submit_reset($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/search/path/list';
}
