<?php

/**
 * @file
 * User page callbacks for the profile module.
 */

/**
 * Menu callback; display a list of user information.
 */
function profile_browse() {
  // Ensure that the path is converted to 3 levels always.
  list(, $name, $value) = array_pad(explode('/', $_GET['q'], 3), 3, '');

  $field = db_query("SELECT DISTINCT(fid), type, title, page, visibility FROM {profile_field} WHERE name = :name", array(':name' => $name))->fetchObject();

  if ($name && $field->fid) {
    // Only allow browsing of fields that have a page title set.
    if (empty($field->page)) {
      drupal_not_found();
      return;
    }
    // Do not allow browsing of private and hidden fields by non-admins.
    if (!user_access('administer users') && ($field->visibility == PROFILE_PRIVATE || $field->visibility == PROFILE_HIDDEN)) {
      drupal_access_denied();
      return;
    }

    // Compile a list of fields to show.
    $fields = db_query('SELECT name, title, type, weight, page FROM {profile_field} WHERE fid <> :fid AND visibility = :visibility ORDER BY weight', array(
      ':fid' => $field->fid,
      ':visibility' => PROFILE_PUBLIC_LISTINGS,
    ))->fetchAll();

    $query = db_select('users', 'u')->extend('PagerDefault');
    $query->join('profile_value', 'v', 'u.uid = v.uid');
    $query
      ->fields('u', array('uid', 'access'))
      ->condition('v.fid', $field->fid)
      ->condition('u.status', 0, '<>')
      ->orderBy('u.access', 'DESC');

    // Determine what query to use:
    $arguments = array($field->fid);
    switch ($field->type) {
      case 'checkbox':
        $query->condition('v.value', 1);
        break;
      case 'textfield':
      case 'selection':
        $query->condition('v.value', $value);
        break;
      case 'list':
        $query->condition('v.value', '%' . db_like($value) . '%', 'LIKE');
        break;
      default:
        drupal_not_found();
        return;
    }

    $uids = $query
      ->limit(20)
      ->execute()
      ->fetchCol();

    // Load the users.
    $users = user_load_multiple($uids);

    $content = '';
    foreach ($users as $account) {
      $profile = _profile_update_user_fields($fields, $account);
      $content .= theme('profile_listing', array('account' => $account, 'fields' => $profile));
    }
    $output = theme('profile_wrapper', array('content' => $content));
    $output .= theme('pager');

    if ($field->type == 'selection' || $field->type == 'list' || $field->type == 'textfield') {
      $title = strtr(check_plain($field->page), array('%value' => drupal_placeholder($value)));
    }
    else {
      $title = check_plain($field->page);
    }

    drupal_set_title($title, PASS_THROUGH);
    return $output;
  }
  elseif ($name && !$field->fid) {
    drupal_not_found();
  }
  else {
    // Compile a list of fields to show.
    $fields = db_query('SELECT name, title, type, weight, page, visibility FROM {profile_field} WHERE visibility = :visibility ORDER BY category, weight', array(':visibility' => PROFILE_PUBLIC_LISTINGS))->fetchAll();

    // Extract the affected users:
    $query = db_select('users', 'u')->extend('PagerDefault');
    $uids = $query
      ->fields('u', array('uid', 'access'))
      ->condition('u.uid', 0, '>')
      ->condition('u.status', 0, '>')
      ->orderBy('u.access', 'DESC')
      ->limit(20)
      ->execute()
      ->fetchCol();
    $users = user_load_multiple($uids);
    $content = '';
    foreach ($users as $account) {
      $profile = _profile_update_user_fields($fields, $account);
      $content .= theme('profile_listing', array('account' => $account, 'fields' => $profile));
    }
    $output = theme('profile_wrapper', array('content' => $content));
    $output .= theme('pager');

    drupal_set_title(t('User list'));
    return $output;
  }
}

/**
 * Callback to allow autocomplete of profile text fields.
 */
function profile_autocomplete($field, $string) {
  $matches = array();
  $autocomplete_field = (bool) db_query_range("SELECT 1 FROM {profile_field} WHERE fid = :fid AND autocomplete = 1", 0, 1, array(':fid' => $field))->fetchField();
  if ($autocomplete_field) {
    $values = db_select('profile_value')
      ->fields('profile_value', array('value'))
      ->condition('fid', $field)
      ->condition('value', db_like($string) . '%', 'LIKE')
      ->groupBy('value')
      ->orderBy('value')
      ->range(0, 10)
      ->execute()->fetchCol();
    foreach ($values as $value) {
      $matches[$value] = check_plain($value);
    }
  }

  drupal_json_output($matches);
}
