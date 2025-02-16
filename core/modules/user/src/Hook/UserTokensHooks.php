<?php

namespace Drupal\user\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user.
 */
class UserTokensHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $types['user'] = [
      'name' => $this->t('Users'),
      'description' => $this->t('Tokens related to individual user accounts.'),
      'needs-data' => 'user',
    ];
    $types['current-user'] = [
      'name' => $this->t('Current user'),
      'description' => $this->t('Tokens related to the currently logged in user.'),
      'type' => 'user',
    ];
    $user['uid'] = ['name' => $this->t('User ID'), 'description' => $this->t("The unique ID of the user account.")];
    $user['uuid'] = ['name' => $this->t('UUID'), 'description' => $this->t("The UUID of the user account.")];
    $user['name'] = [
      'name' => $this->t("Deprecated: User Name"),
      'description' => $this->t("Deprecated: Use account-name or display-name instead."),
    ];
    $user['account-name'] = [
      'name' => $this->t("Account Name"),
      'description' => $this->t("The login name of the user account."),
    ];
    $user['display-name'] = [
      'name' => $this->t("Display Name"),
      'description' => $this->t("The display name of the user account."),
    ];
    $user['mail'] = [
      'name' => $this->t("Email"),
      'description' => $this->t("The email address of the user account."),
    ];
    $user['url'] = ['name' => $this->t("URL"), 'description' => $this->t("The URL of the account profile page.")];
    $user['edit-url'] = ['name' => $this->t("Edit URL"), 'description' => $this->t("The URL of the account edit page.")];
    $user['last-login'] = [
      'name' => $this->t("Last login"),
      'description' => $this->t("The date the user last logged in to the site."),
      'type' => 'date',
    ];
    $user['created'] = [
      'name' => $this->t("Created"),
      'description' => $this->t("The date the user account was created."),
      'type' => 'date',
    ];
    return ['types' => $types, 'tokens' => ['user' => $user]];
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
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
            $replacements[$original] = $account->id() ?: $this->t('not yet assigned');
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
            $replacements[$original] = $account->id() ? $account->toUrl('canonical', $url_options)->toString() : $this->t('not yet assigned');
            break;

          case 'edit-url':
            $replacements[$original] = $account->id() ? $account->toUrl('edit-form', $url_options)->toString() : $this->t('not yet assigned');
            break;

          // These tokens are default variations on the chained tokens handled
          // below.
          case 'last-login':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            $replacements[$original] = $account->getLastLoginTime() ? \Drupal::service('date.formatter')->format($account->getLastLoginTime(), 'medium', '', NULL, $langcode) : $this->t('never');
            break;

          case 'created':
            $date_format = DateFormat::load('medium');
            $bubbleable_metadata->addCacheableDependency($date_format);
            // In the case of user_presave the created date may not yet be set.
            $replacements[$original] = $account->getCreatedTime() ? \Drupal::service('date.formatter')->format($account->getCreatedTime(), 'medium', '', NULL, $langcode) : $this->t('not yet created');
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
