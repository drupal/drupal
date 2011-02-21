<?php
// $Id: user.inc,v 1.12 2011/01/05 19:42:37 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide a user context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("User"),
  'description' => t('A single user object.'),
  'context' => 'ctools_context_create_user',
  'edit form' => 'ctools_context_user_settings_form',
  'defaults' => array('type' => 'select', 'uid' => ''),
  'keyword' => 'user',
  'context name' => 'user',
  'convert list' => 'ctools_context_user_convert_list',
  'convert' => 'ctools_context_user_convert',
  'convert default' => 'name',
  'js' => array('misc/autocomplete.js'),
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_user($empty, $data = NULL, $conf = FALSE) {
  $context = new ctools_context('user');
  $context->plugin = 'user';

  if ($empty) {
    return $context;
  }

  if ($conf) {
    if ($data['type'] == 'current') {
      global $user;
      $data = user_load($user->uid);
      $data->logged_in_user = TRUE;
    }
    else {
      $data = user_load($data['uid']);
    }
  }

  if (!empty($data)) {
    $context->data     = $data;
    $context->title    = isset($data->name) ? $data->name : t('Anonymous');
    $context->argument = $data->uid;
    return $context;
  }
}

function ctools_context_user_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];

  ctools_include('dependent');
  $form['type'] = array(
    '#title' => t('Enter the context type'),
    '#type' => 'radios',
    '#options' => array(
      'select' => t('Select a user'),
      'current' => t('Logged in user'),
    ),
    '#default_value' => $conf['type'],
  );

  $form['user'] = array(
    '#title' => t('Enter a user name'),
    '#type' => 'textfield',
    '#maxlength' => 512,
    '#autocomplete_path' => 'user/autocomplete',
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:type' => array('select')),
  );

  if (!empty($conf['uid'])) {
    $info = user_load($conf['uid']);
    if ($info) {
      $form['user']['#description'] = t('Currently set to !link', array('!link' => theme('username', $info)));
    }
  }

  $form['uid'] = array(
    '#type' => 'value',
    '#value' => $conf['uid'],
  );

  $form['set_identifier'] = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#title' => t('Reset identifier to username'),
    '#description' => t('If checked, the identifier will be reset to the user name of the selected user.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:context[context_settings][type]' => array('select')),
  );

  return $form;
}

/**
 * Validate a user.
 */
function ctools_context_user_settings_form_validate($form, &$form_state) {
  if ($form_state['values']['type'] != 'select') {
    return;
  }

  // Validate the autocomplete
  if (empty($form_state['values']['uid']) && empty($form_state['values']['user'])) {
    form_error($form['user'], t('You must select a user.'));
    return;
  }

  if (empty($form_state['values']['user'])) {
    return;
  }

  $account = user_load_by_name($form_state['values']['user']);

  if (!$account) {
    form_error($form['user'], t('Invalid user selected.'));
  }
  else {
    form_set_value($form['uid'], $account->uid, $form_state);
  }
}

function ctools_context_user_settings_form_submit($form, &$form_state) {
  if ($form_state['values']['set_identifier']) {
    $account = user_load($form_state['values']['uid']);
    $form_state['values']['identifier'] = $account->name;
  }

  $form_state['conf']['type'] = $form_state['values']['type'];
  $form_state['conf']['uid'] = $form_state['values']['uid'];
}

/**
 * Provide a list of replacements.
 */
function ctools_context_user_convert_list() {
  $tokens = token_info();
  foreach ($tokens['tokens']['user'] as $id => $info) {
    if (!isset($list[$id])) {
      $list[$id] = $info['name'];
    }
  }

  return $list;
}

/**
 * Convert a context into a string.
 */
function ctools_context_user_convert($context, $type) {
  $tokens = token_info();
  if (isset($tokens['tokens']['user'][$type])) {
    $values = token_generate('user', array($type => $type), array('user' => $context->data));
    if (isset($values[$type])) {
      return $values[$type];
    }
  }
}
