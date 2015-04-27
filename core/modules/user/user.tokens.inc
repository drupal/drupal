<?php

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\user\Entity\User;

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
    'description' => t("The URL of the account edit page."),
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

  $token_service = \Drupal::token();
  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
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
          $replacements[$original] = $account->id() ?: t('not yet assigned');
          break;

        case 'name':
          $name = user_format_name($account);
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($name) : $name;
          break;

        case 'mail':
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($account->getEmail()) : $account->getEmail();
          break;

        case 'url':
          $replacements[$original] = $account->id() ? $account->url('canonical', $url_options) : t('not yet assigned');
          break;

        case 'edit-url':
          $replacements[$original] = $account->id() ? $account->url('edit-form', $url_options) : t('not yet assigned');
          break;

        // These tokens are default variations on the chained tokens handled below.
        case 'last-login':
          $replacements[$original] = $account->getLastLoginTime() ? format_date($account->getLastLoginTime(), 'medium', '', NULL, $langcode) : t('never');
          break;

        case 'created':
          // In the case of user_presave the created date may not yet be set.
          $replacements[$original] = $account->getCreatedTime() ? format_date($account->getCreatedTime(), 'medium', '', NULL, $langcode) : t('not yet created');
          break;
      }
    }

    if ($login_tokens = $token_service->findWithPrefix($tokens, 'last-login')) {
      $replacements += $token_service->generate('date', $login_tokens, array('date' => $account->getLastLoginTime()), $options);
    }

    if ($registered_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $registered_tokens, array('date' => $account->getCreatedTime()), $options);
    }
  }

  if ($type == 'current-user') {
    $account = User::load(\Drupal::currentUser()->id());
    $replacements += $token_service->generate('user', $tokens, array('user' => $account), $options);
  }

  return $replacements;
}
