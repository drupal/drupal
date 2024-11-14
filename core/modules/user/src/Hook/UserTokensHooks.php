<?php

namespace Drupal\user\Hook;

use Drupal\user\Entity\User;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user.
 */
class UserTokensHooks {

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $types['user'] = [
      'name' => t('Users'),
      'description' => t('Tokens related to individual user accounts.'),
      'needs-data' => 'user',
    ];
    $types['current-user'] = [
      'name' => t('Current user'),
      'description' => t('Tokens related to the currently logged in user.'),
      'type' => 'user',
    ];
    $user['uid'] = ['name' => t('User ID'), 'description' => t("The unique ID of the user account.")];
    $user['uuid'] = ['name' => t('UUID'), 'description' => t("The UUID of the user account.")];
    $user['name'] = [
      'name' => t("Deprecated: User Name"),
      'description' => t("Deprecated: Use account-name or display-name instead."),
    ];
    $user['account-name'] = [
      'name' => t("Account Name"),
      'description' => t("The login name of the user account."),
    ];
    $user['display-name'] = [
      'name' => t("Display Name"),
      'description' => t("The display name of the user account."),
    ];
    $user['mail'] = [
      'name' => t("Email"),
      'description' => t("The email address of the user account."),
    ];
    $user['url'] = ['name' => t("URL"), 'description' => t("The URL of the account profile page.")];
    $user['edit-url'] = ['name' => t("Edit URL"), 'description' => t("The URL of the account edit page.")];
    $user['last-login'] = [
      'name' => t("Last login"),
      'description' => t("The date the user last logged in to the site."),
      'type' => 'date',
    ];
    $user['created'] = [
      'name' => t("Created"),
      'description' => t("The date the user account was created."),
      'type' => 'date',
    ];
    return ['types' => $types, 'tokens' => ['user' => $user]];
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $token_service = \Drupal::token();
    $url_options = ['absolute' => TRUE];
    if (isset($options['langcode'])) {
      $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
      $langcode = $options['langcode'];
    }
    else {
      $langcode = NULL;
    }
    $replacements = [];
    if ($type == 'user' && !empty($data['user'])) {
      /** @var \Drupal\user\UserInterface $account */
      $account = $data['user'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          // Basic user account information.
          case 'uid':
            // In the case of hook user_presave uid is not set yet.
            $replacements[$original] = $account->id() ?: t('not yet assigned');
            break;

          case 'uuid':
            $replacements[$original] = $account->uuid();
            break;

          case 'display-name':
            $replacements[$original] = $account->getDisplayName();
            if ($account->isAnonymous()) {
              $bubbleable_metadata->addCacheableDependency(\Drupal::config('user.settings'));
            }
            break;

          case 'name':
          case 'account-name':
            $display_name = $account->getAccountName();
            $replacements[$original] = $display_name;
            if ($account->isAnonymous()) {
              $bubbleable_metadata->addCacheableDependency(\Drupal::config('user.settings'));
            }
            break;

          case 'mail':
            $replacements[$original] = $account->getEmail();
            break;

          case 'url':
            $replacements[$original] = $account->id() ? $account->toUrl('canonical', $url_options)->toString() : t('not yet assigned');
            break;

          case 'edit-url':
            $replacements[$original] = $account->id() ? $account->toUrl('edit-form', $url_options)->toString() : t('not yet assigned');
            break;

          // These tokens are default variations on the chained tokens handled below.
          case 'last-login':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = $account->getLastLoginTime() ? \Drupal::service('date.formatter')->format($account->getLastLoginTime(), 'medium', '', NULL, $langcode) : t('never');
            break;

          case 'created':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            // In the case of user_presave the created date may not yet be set.
            $replacements[$original] = $account->getCreatedTime() ? \Drupal::service('date.formatter')->format($account->getCreatedTime(), 'medium', '', NULL, $langcode) : t('not yet created');
            break;
        }
      }
      if ($login_tokens = $token_service->findWithPrefix($tokens, 'last-login')) {
        $replacements += $token_service->generate('date', $login_tokens, ['date' => $account->getLastLoginTime()], $options, $bubbleable_metadata);
      }
      if ($registered_tokens = $token_service->findWithPrefix($tokens, 'created')) {
        $replacements += $token_service->generate('date', $registered_tokens, ['date' => $account->getCreatedTime()], $options, $bubbleable_metadata);
      }
    }
    if ($type == 'current-user') {
      $account = User::load(\Drupal::currentUser()->id());
      $bubbleable_metadata->addCacheContexts(['user']);
      $replacements += $token_service->generate('user', $tokens, ['user' => $account], $options, $bubbleable_metadata);
    }
    return $replacements;
  }

}
