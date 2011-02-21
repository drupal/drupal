<?php
// $Id: googleanalytics.tokens.inc,v 1.1.2.4 2011/02/07 15:33:38 hass Exp $

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

/**
 * Implements hook_token_info().
 */
function googleanalytics_token_info() {
  $user['role-names'] = array(
    'name' => t('User role names'),
    'description' => t('The role names the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  );
  $user['role-ids'] = array(
    'name' => t('User role ids'),
    'description' => t('The role ids the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  );

  return array(
    'tokens' => array('user' => $user),
  );
}

/**
 * Implements hook_tokens().
 */
function googleanalytics_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $sanitize = !empty($options['sanitize']);
  $replacements = array();

  if ($type == 'user' && !empty($data['user']->roles)) {
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Basic user account information.
        case 'role-names':
          $names = implode(',', $account->roles);
          $replacements[$original] = $sanitize ? check_plain($names) : $names;
          break;

        case 'role-ids':
          $ids = implode(',', array_keys($account->roles));
          $replacements[$original] = $sanitize ? check_plain($ids) : $ids;
          break;
      }
    }
  }

  return $replacements;
}
