<?php
// $Id$

/**
 * @file
 * User page callbacks for the openid module.
 */

/**
 * Menu callback; Process an OpenID authentication.
 */
function openid_authentication_page() {
  $result = openid_complete();
  switch ($result['status']) {
    case 'success':
      return openid_authentication($result);
    case 'failed':
      drupal_set_message(t('OpenID login failed.'), 'error');
      break;
    case 'cancel':
      drupal_set_message(t('OpenID login cancelled.'));
      break;
  }
  drupal_goto();
}

/**
 * Menu callback; Manage OpenID identities for the specified user.
 */
function openid_user_identities($account) {
  drupal_set_title($account->name);
  drupal_add_css(drupal_get_path('module', 'openid') . '/openid.css', 'module');

  // Check to see if we got a response
  $result = openid_complete();
  if ($result['status'] == 'success') {
    $identity = $result['openid.claimed_id'];
    db_query("INSERT INTO {authmap} (uid, authname, module) VALUES (%d, '%s','openid')", $account->uid, $identity);
    drupal_set_message(t('Successfully added %identity', array('%identity' => $identity)));
  }

  $header = array(t('OpenID'), t('Operations'));
  $rows = array();

  $result = db_query("SELECT * FROM {authmap} WHERE module='openid' AND uid=%d", $account->uid);
  while ($identity = db_fetch_object($result)) {
    $rows[] = array($identity->authname, l(t('Delete'), 'user/' . $account->uid . '/openid/delete/' . $identity->aid));
  }

  $output = theme('table', $header, $rows);
  $output .= drupal_get_form('openid_user_add');
  return $output;
}

/**
 * Form builder; Add an OpenID identity.
 *
 * @ingroup forms
 * @see openid_user_add_validate()
 */
function openid_user_add() {
  $form['openid_identifier'] = array(
    '#type' => 'textfield',
    '#title' => t('OpenID'),
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Add an OpenID'));
  return $form;
}

function openid_user_add_validate($form, &$form_state) {
  // Check for existing entries.
  $claimed_id = _openid_normalize($form_state['values']['openid_identifier']);
  if (db_result(db_query("SELECT authname FROM {authmap} WHERE authname='%s'", $claimed_id))) {
    form_set_error('openid_identifier', t('That OpenID is already in use on this site.'));
  }
  else {
    $return_to = url('user/' . arg(1) . '/openid', array('absolute' => TRUE));
    openid_begin($form_state['values']['openid_identifier'], $return_to);
  }
}

/**
 * Menu callback; Delete the specified OpenID identity from the system.
 */
function openid_delete_user($account, $aid = 0) {
  db_query("DELETE FROM {authmap} WHERE uid=%d AND aid=%d AND module='openid'", $account->uid, $aid);
  if (db_affected_rows()) {
    drupal_set_message(t('OpenID deleted.'));
  }
  drupal_goto('user/' . $account->uid . '/openid');
}
