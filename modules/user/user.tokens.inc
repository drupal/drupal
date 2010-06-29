<?php
// $Id: user.tokens.inc,v 1.7 2010/06/29 18:24:10 dries Exp $

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

/**
 * Implements hook_token_info().
 */
function user_token_info() {
  $types['user'] = array(
    'name' => t('Users'),
    'description' => t('Tokens related to individual user accounts.'),
    'needs-data' => 'user',
  );
  $types['current-user'] = array(
    'name' => t('Current user'),
    'description' => t('Tokens related to the currently logged in user.'),
    'type' => 'user',
  );

  $user['uid'] = array(
    'name' => t('User ID'),
    'description' => t("The unique ID of the user account."),
  );
  $user['name'] = array(
    'name' => t("Name"),
    'description' => t("The login name of the user account."),
  );
  $user['mail'] = array(
    'name' => t("Email"),
    'description' => t("The email address of the user account."),
  );
  $user['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the account profile page."),
  );
  $user['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The url of the account edit page."),
  );

  $user['last-login'] = array(
    'name' => t("Last login"),
    'description' => t("The date the user last logged in to the site."),
    'type' => 'date',
  );
  $user['created'] = array(
    'name' => t("Created"),
    'description' => t("The date the user account was created."),
    'type' => 'date',
  );

  return array(
    'types' => $types,
    'tokens' => array('user' => $user),
  );
}

/**
 * Implements hook_tokens().
 */
function user_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);
  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  else {
    $language_code = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'user' && !empty($data['user'])) {
    $account = $data['user'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Basic user account information.
        case 'uid':
          // In the case of hook user_presave uid is not set yet.
          $replacements[$original] = !empty($account->uid) ? $account->uid : t('not yet assigned');
          break;

        case 'name':
          $name = format_username($account);
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case 'mail':
          $replacements[$original] = $sanitize ? check_plain($account->mail) : $account->mail;
          break;

        case 'url':
          $replacements[$original] = !empty($account->uid) ? url("user/$account->uid", $url_options) : t('not yet assigned');
          break;

        case 'edit-url':
          $replacements[$original] = !empty($account->uid) ? url("user/$account->uid/edit", $url_options) : t('not yet assigned');
          break;

        // These tokens are default variations on the chained tokens handled below.
        case 'last-login':
          $replacements[$original] = !empty($account->login) ? format_date($account->login, 'medium', '', NULL, $language_code) : t('never');
          break;

        case 'created':
          // In the case of user_presave the created date may not yet be set.
          $replacements[$original] = !empty($account->created) ? format_date($account->created, 'medium', '', NULL, $language_code) : t('not yet created');
          break;
      }
    }

    if ($login_tokens = token_find_with_prefix($tokens, 'last-login')) {
      $replacements += token_generate('date', $login_tokens, array('date' => $account->login), $options);
    }

    if ($registered_tokens = token_find_with_prefix($tokens, 'created')) {
      $replacements += token_generate('date', $registered_tokens, array('date' => $account->created), $options);
    }
  }
  if ($type == 'current-user') {
    global $user;
    $replacements += token_generate('user', $tokens, array('user' => $user), $options);
  }

  return $replacements;
}
