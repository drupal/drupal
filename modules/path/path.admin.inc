<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the path module.
 */

/**
 * Return a listing of all defined URL aliases.
 * When filter key passed, perform a standard search on the given key,
 * and return the list of matching URL aliases.
 */
function path_admin_overview($keys = NULL) {
  // Add the filter form above the overview table.
  $build['path_admin_filter_form'] = drupal_get_form('path_admin_filter_form', $keys);
  // Enable language column if locale is enabled or if we have any alias with language
  $alias_exists = (bool) db_query_range('SELECT 1 FROM {url_alias} WHERE language <> :language', array(':language' => ''), 0, 1)->fetchField();
  $multilanguage = (module_exists('locale') || $alias_exists);

  $header = array(
    array('data' => t('Alias'), 'field' => 'dst', 'sort' => 'asc'),
    array('data' => t('System'), 'field' => 'src'),
    array('data' => t('Operations'), 'colspan' => '2')
  );
  if ($multilanguage) {
    array_splice($header, 2, 0, array(array('data' => t('Language'), 'field' => 'language')));
  }

  $query = db_select('url_alias')->extend('PagerDefault')->extend('TableSort');
  if ($keys) {
    // Replace wildcards with PDO wildcards.
    $query->condition('dst', '%' . preg_replace('!\*+!', '%', $keys) . '%', 'LIKE');
  }
  $result = $query
    ->fields('url_alias')
    ->orderByHeader($header)
    ->limit(50)
    ->execute();

  $rows = array();
  $destination = drupal_get_destination();
  foreach ($result as $data) {
    $row = array(
      'data' => array(
        l($data->dst, $data->src),
        l($data->src, $data->src, array('alias' => TRUE)),
        l(t('edit'), "admin/config/search/path/edit/$data->pid", array('query' => $destination)),
        l(t('delete'), "admin/config/search/path/delete/$data->pid", array('query' => $destination)),
      ),
    );
    // If the system path maps to a different URL alias, highlight this table
    // row to let the user know of old aliases.
    if ($data->dst != drupal_get_path_alias($data->src, $data->language)) {
      $row['class'] = array('warning');
    }
    if ($multilanguage) {
      array_splice($row['data'], 2, 0, module_invoke('locale', 'language_name', $data->language));
    }
    $rows[] = $row;
  }

  if (empty($rows)) {
    $empty_message = $keys ? t('No URL aliases found.') : t('No URL aliases available. <a href="@link">Add alias</a>.', array('@link' => url('admin/config/search/path/add'))) ;
    $rows[] = array(array('data' => $empty_message, 'colspan' => ($multilanguage ? 5 : 4)));
  }

  $build['path_table'] = array(
    '#theme' => 'table', 
    '#header' => $header, 
    '#rows' => $rows
  );
  $build['path_pager'] = array('#theme' => 'pager');

  return $build;
}

/**
 * Menu callback; handles pages for creating and editing URL aliases.
 */
function path_admin_edit($pid = 0) {
  if ($pid) {
    $alias = path_load($pid);
    drupal_set_title($alias['dst']);
    $output = drupal_get_form('path_admin_form', $alias);
  }
  else {
    $output = drupal_get_form('path_admin_form');
  }

  return $output;
}

/**
 * Return a form for editing or creating an individual URL alias.
 *
 * @ingroup forms
 * @see path_admin_form_validate()
 * @see path_admin_form_submit()
 */
function path_admin_form(&$form_state, $edit = array('src' => '', 'dst' => '', 'language' => '', 'pid' => NULL)) {

  $form['#alias'] = $edit;

  $form['src'] = array(
    '#type' => 'textfield',
    '#title' => t('Existing system path'),
    '#default_value' => $edit['src'],
    '#maxlength' => 255,
    '#size' => 45,
    '#description' => t('Specify the existing path you wish to alias. For example: node/28, forum/1, taxonomy/term/1+2.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
    '#required' => TRUE,
  );
  $form['dst'] = array(
    '#type' => 'textfield',
    '#title' => t('Path alias'),
    '#default_value' => $edit['dst'],
    '#maxlength' => 255,
    '#size' => 45,
    '#description' => t('Specify an alternative path by which this data can be accessed. For example, type "about" when writing an about page. Use a relative path and don\'t add a trailing slash or the URL alias won\'t work.'),
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
    '#required' => TRUE,
  );
  // This will be a hidden value unless locale module is enabled
  $form['language'] = array(
    '#type' => 'value',
    '#value' => $edit['language']
  );
  if ($edit['pid']) {
    $form['pid'] = array('#type' => 'hidden', '#value' => $edit['pid']);
    $form['submit'] = array('#type' => 'submit', '#value' => t('Update alias'));
  }
  else {
    $form['submit'] = array('#type' => 'submit', '#value' => t('Create new alias'));
  }

  return $form;
}


/**
 * Verify that a new URL alias is valid
 */
function path_admin_form_validate($form, &$form_state) {
  $src = $form_state['values']['src'];
  $dst = $form_state['values']['dst'];
  $pid = isset($form_state['values']['pid']) ? $form_state['values']['pid'] : 0;
  // Language is only set if locale module is enabled, otherwise save for all languages.
  $language = isset($form_state['values']['language']) ? $form_state['values']['language'] : '';

  $has_alias = db_query("SELECT COUNT(dst) FROM {url_alias} WHERE pid <> :pid AND dst = :dst AND language = :language", array(
    ':pid' => $pid,
    ':dst' => $dst,
    ':language' => $language,
  ))
  ->fetchField();

  if ($has_alias) {
    form_set_error('dst', t('The alias %alias is already in use in this language.', array('%alias' => $dst)));
  }
  $item = menu_get_item($src);
  if (!$item || !$item['access']) {
    form_set_error('src', t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $src)));
  }
}

/**
 * Save a new URL alias to the database.
 */
function path_admin_form_submit($form, &$form_state) {
  // Language is only set if locale module is enabled
  path_set_alias($form_state['values']['src'], $form_state['values']['dst'], isset($form_state['values']['pid']) ? $form_state['values']['pid'] : 0, isset($form_state['values']['language']) ? $form_state['values']['language'] : '');

  drupal_set_message(t('The alias has been saved.'));
  $form_state['redirect'] = 'admin/config/search/path';
  return;
}

/**
 * Menu callback; confirms deleting an URL alias
 */
function path_admin_delete_confirm($form_state, $pid) {
  $path = path_load($pid);
  if (user_access('administer url aliases')) {
    $form['pid'] = array('#type' => 'value', '#value' => $pid);
    $output = confirm_form($form,
      t('Are you sure you want to delete path alias %title?', array('%title' => $path['dst'])),
      isset($_GET['destination']) ? $_GET['destination'] : 'admin/config/search/path');
  }
  return $output;
}

/**
 * Execute URL alias deletion
 */
function path_admin_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    path_admin_delete($form_state['values']['pid']);
    $form_state['redirect'] = 'admin/config/search/path';
    return;
  }
}


/**
 * Return a form to filter URL aliases.
 *
 * @ingroup forms
 * @see path_admin_filter_form_submit()
 */
function path_admin_filter_form(&$form_state, $keys = '') {
  $form['#attributes'] = array('class' => array('search-form'));
  $form['basic'] = array('#type' => 'fieldset',
    '#title' => t('Filter aliases')
  );
  $form['basic']['inline'] = array('#prefix' => '<div class="container-inline">', '#suffix' => '</div>');
  $form['basic']['inline']['filter'] = array(
    '#type' => 'textfield',
    '#title' => '',
    '#default_value' => $keys,
    '#maxlength' => 128,
    '#size' => 25,
  );
  $form['basic']['inline']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#submit' => array('path_admin_filter_form_submit_filter'),
    );
  if ($keys) {
    $form['basic']['inline']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
      '#submit' => array('path_admin_filter_form_submit_reset'),
    );
  }
  return $form;
}

/**
 * Process filter form submission when the Filter button is pressed.
 */
function path_admin_filter_form_submit_filter($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/search/path/list/' . trim($form_state['values']['filter']);
}

/**
 * Process filter form submission when the Reset button is pressed.
 */
function path_admin_filter_form_submit_reset($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/search/path/list';
}


/**
 * Helper function for grabbing filter keys.
 */
function path_admin_filter_get_keys() {
  // Extract keys as remainder of path
  $path = explode('/', $_GET['q'], 5);
  return count($path) == 5 ? $path[4] : '';
}
