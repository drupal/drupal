<?php
// $Id$

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

  $field = db_fetch_object(db_query("SELECT DISTINCT(fid), type, title, page, visibility FROM {profile_fields} WHERE name = '%s'", $name));

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
    $fields = array();
    $result = db_query('SELECT name, title, type, weight, page FROM {profile_fields} WHERE fid != %d AND visibility = %d ORDER BY weight', $field->fid, PROFILE_PUBLIC_LISTINGS);
    while ($record = db_fetch_object($result)) {
      $fields[] = $record;
    }

    // Determine what query to use:
    $arguments = array($field->fid);
    switch ($field->type) {
      case 'checkbox':
        $query = 'v.value = 1';
        break;
      case 'textfield':
      case 'selection':
        $query = "v.value = '%s'";
        $arguments[] = $value;
        break;
      case 'list':
        $query = "v.value LIKE '%%%s%%'";
        $arguments[] = $value;
        break;
      default:
        drupal_not_found();
        return;
    }

    // Extract the affected users:
    $result = pager_query("SELECT u.uid, u.access FROM {users} u INNER JOIN {profile_values} v ON u.uid = v.uid WHERE v.fid = %d AND $query AND u.access != 0 AND u.status != 0 ORDER BY u.access DESC", 20, 0, NULL, $arguments);

    $content = '';
    while ($account = db_fetch_object($result)) {
      $account = user_load(array('uid' => $account->uid));
      $profile = _profile_update_user_fields($fields, $account);
      $content .= theme('profile_listing', $account, $profile);
    }
    $output = theme('profile_wrapper', $content);
    $output .= theme('pager', NULL, 20);

    if ($field->type == 'selection' || $field->type == 'list' || $field->type == 'textfield') {
      $title = strtr(check_plain($field->page), array('%value' => theme('placeholder', $value)));
    }
    else {
      $title = check_plain($field->page);
    }

    drupal_set_title($title);
    return $output;
  }
  else if ($name && !$field->fid) {
    drupal_not_found();
  }
  else {
    // Compile a list of fields to show.
    $fields = array();
    $result = db_query('SELECT name, title, type, weight, page FROM {profile_fields} WHERE visibility = %d ORDER BY category, weight', PROFILE_PUBLIC_LISTINGS);
    while ($record = db_fetch_object($result)) {
      $fields[] = $record;
    }

    // Extract the affected users:
    $result = pager_query('SELECT uid, access FROM {users} WHERE uid > 0 AND status != 0 AND access != 0 ORDER BY access DESC', 20, 0, NULL);

    $content = '';
    while ($account = db_fetch_object($result)) {
      $account = user_load(array('uid' => $account->uid));
      $profile = _profile_update_user_fields($fields, $account);
      $content .= theme('profile_listing', $account, $profile);
    }
    $output = theme('profile_wrapper', $content);
    $output .= theme('pager', NULL, 20);

    drupal_set_title(t('User list'));
    return $output;
  }
}

/**
 * Callback to allow autocomplete of profile text fields.
 */
function profile_autocomplete($field, $string) {
  $matches = array();
  if (db_result(db_query("SELECT COUNT(*) FROM {profile_fields} WHERE fid = %d AND autocomplete = 1", $field))) {
    $result = db_query_range("SELECT value FROM {profile_values} WHERE fid = %d AND LOWER(value) LIKE LOWER('%s%%') GROUP BY value ORDER BY value ASC", $field, $string, 0, 10);
    while ($data = db_fetch_object($result)) {
      $matches[$data->value] = check_plain($data->value);
    }
  }

  drupal_json($matches);
}
