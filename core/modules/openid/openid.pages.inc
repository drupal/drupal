<?php

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
  drupal_set_title(format_username($account));
  drupal_add_css(drupal_get_path('module', 'openid') . '/openid.css');

  // Check to see if we got a response
  $result = openid_complete();
  if ($result['status'] == 'success') {
    $identity = $result['openid.claimed_id'];
    $query = db_insert('authmap')
      ->fields(array(
        'uid' => $account->uid,
        'authname' => $identity,
        'module' => 'openid',
      ))
      ->execute();
    drupal_set_message(t('Successfully added %identity', array('%identity' => $identity)));
  }

  $header = array(t('OpenID'), t('Operations'));
  $rows = array();

  $result = db_query("SELECT * FROM {authmap} WHERE module='openid' AND uid=:uid", array(':uid' => $account->uid));
  foreach ($result as $identity) {
    $rows[] = array(check_plain($identity->authname), l(t('Delete'), 'user/' . $account->uid . '/openid/delete/' . $identity->aid));
  }

  $build['openid_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  $build['openid_user_add'] = drupal_get_form('openid_user_add');
  return $build;
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
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Add an OpenID'));
  return $form;
}

function openid_user_add_validate($form, &$form_state) {
  // Check for existing entries.
  $claimed_id = openid_normalize($form_state['values']['openid_identifier']);
  if (db_query("SELECT authname FROM {authmap} WHERE authname = :authname", (array(':authname' => $claimed_id)))->fetchField()) {
    form_set_error('openid_identifier', t('That OpenID is already in use on this site.'));
  }
}

function openid_user_add_submit($form, &$form_state) {
  $return_to = url('user/' . arg(1) . '/openid', array('absolute' => TRUE));
  openid_begin($form_state['values']['openid_identifier'], $return_to);
}

/**
 * Menu callback; Delete the specified OpenID identity from the system.
 */
function openid_user_delete_form($form, $form_state, $account, $aid = 0) {
  $authname = db_query("SELECT authname FROM {authmap} WHERE uid = :uid AND aid = :aid AND module = 'openid'", array(
    ':uid' => $account->uid,
    ':aid' => $aid,
  ))
  ->fetchField();
  return confirm_form(array(), t('Are you sure you want to delete the OpenID %authname for %user?', array('%authname' => $authname, '%user' => $account->name)), 'user/' . $account->uid . '/openid');
}

function openid_user_delete_form_submit($form, &$form_state) {
  $query = db_delete('authmap')
    ->condition('uid', $form_state['build_info']['args'][0]->uid)
    ->condition('aid', $form_state['build_info']['args'][1])
    ->condition('module', 'openid')
    ->execute();
  if ($query) {
    drupal_set_message(t('OpenID deleted.'));
  }
  $form_state['redirect'] = 'user/' . $form_state['build_info']['args'][0]->uid . '/openid';
}
