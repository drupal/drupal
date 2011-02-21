<?php
// $Id: compare_users.inc,v 1.6 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Ctools access plugin to provide access/visiblity if two user contexts are equal.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("User: compare"),
  'description' => t('Compare two users (logged-in user and user being viewed, for example)'),
  'callback' => 'ctools_compare_users_access_check',
  'default' => array(
    'equality' => 1,
  ),
  'settings form' => 'ctools_compare_users_settings',
  'summary' => 'ctools_compare_users_ctools_access_summary',
  'required context' => array(
    new ctools_context_required(t('First User'), 'user'),
    new ctools_context_required(t("Second User"), 'user')
  ),
);

/**
 * Settings form for the 'by perm' access plugin
 */
function ctools_compare_users_settings($form, &$form_state, $conf) {

  $form['settings']['helptext'] = array(
    '#type' => 'markup',
    '#value' => '<div>'. t('Grant access based on comparison of the two user contexts. For example, to grant access to a user to view their own profile, choose "logged in user" and "user being viewed" and say "grant access if equal". When they\'re the same, access will be granted.') . '</div>',
  );

  $form['settings']['equality'] = array(
    '#type' => 'radios',
    '#title' => t('Grant access if user contexts are'),
    '#options' => array(1 => t('Equal'), 0 => t('Not equal')),
    '#default_value' => $conf['equality'],
  );
  return $form;
}

/**
 * Check for access.
 */
function ctools_compare_users_access_check($conf, $context) {

  if (empty($context) || count($context) != 2 || empty($context[0]->data) || empty($context[1]->data)) {
    return FALSE;
  }
  $account1 = $context[0]->data;
  $account2 = $context[1]->data;

  // xor returns false if the two bools are the same, and true if they are not.
  // i.e, if we asked for equality and they are equal, return true.
  // If we asked for inequality and they are equal, return false.
  return ($account1->uid == $account2->uid) xor empty($conf['equality']);
}

/**
 * Describe an instance of this plugin.
 */
function ctools_compare_users_ctools_access_summary($conf, $context) {
  $comparison = !empty($conf['equality']) ? "is" : 'is not';

  return t('@id1 @comp @id2', array('@comp' => $comparison, '@id1' => $context[0]->identifier, '@id2' => $context[1]->identifier));
}
