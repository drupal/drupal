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
  drupal_set_title(user_format_name($account));
  drupal_add_css(drupal_get_path('module', 'openid') . '/openid.css');

  // Check to see if we got a response
  $response = openid_complete();
  if ($response['status'] == 'success') {
    $identity = $response['openid.claimed_id'];
    $query = db_insert('openid_identities')
      ->fields(array(
        'uid' => $account->uid,
        'identifier' => $identity,
      ))
      ->execute();
    drupal_set_message(t('Successfully added %identity', array('%identity' => $identity)));
    // Let other modules act on OpenID authentication.
    module_invoke_all('openid_response', $response, $account);
  }

  $header = array(t('OpenID'), t('Operations'));
  $rows = array();

  $result = db_query("SELECT * FROM {openid_identities} WHERE uid=:uid", array(':uid' => $account->uid));
  foreach ($result as $identity) {
    $row = array();
    $row[] = check_plain($identity->identifier);
    $links = array();
    $links['delete'] = array(
      'title' => t('Delete'),
      'href' => 'user/' . $account->uid . '/openid/delete/' . $identity->aid,
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $rows[] = $row;
  }

  $build['openid_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No OpenID identities available for this account.'),
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
  if (db_query("SELECT identifier FROM {openid_identities} WHERE identifier = :identifier", array(':identifier' => $claimed_id))->fetchField()) {
    form_set_error('openid_identifier', t('That OpenID is already in use on this site.'));
  }
}

function openid_user_add_submit($form, &$form_state) {
  $return_to = url('user/' . arg(1) . '/openid', array('absolute' => TRUE));
  openid_begin($form_state['values']['openid_identifier'], $return_to);
}
