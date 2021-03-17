<?php

namespace Drupal\user\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller Email change routes.
 */
class MailChangeController extends ControllerBase {

  /**
   * The date-time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Builds a new controller.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The date-time service.
   */
  public function __construct(TimeInterface $date_time) {
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('datetime.time'));
  }

  /**
   * Returns the user mail change page.
   *
   * In order to never disclose a mail change link via a referrer header this
   * controller must always return a redirect response.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account requesting Email change.
   * @param string $new_mail
   *   The user's new email address.
   * @param int $timestamp
   *   The timestamp when the hash was created.
   * @param string $hash
   *   Unique hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   An HTTP response doing a redirect.
   */
  public function page(UserInterface $user, $new_mail, $timestamp, $hash) {
    $timeout = $this->config('user.settings')->get('mail_change_timeout');
    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $this->currentUser();
    $request_time = $this->dateTime->getRequestTime();
    $messenger = $this->messenger();

    // Other user is authenticated.
    if ($current_user->isAuthenticated() && $current_user->id() != $user->id()) {
      $arguments = [
        '%user' => $current_user->getAccountName(),
        ':logout' => Url::fromRoute('user.logout')->toString(),
      ];
      $messenger->addError($this->t('You are currently logged in as %user, and are attempting to confirm an email address change for another account. Please <a href=":logout">log out</a> and try using the link again.', $arguments));
    }
    // The link has expired.
    elseif ($request_time - $timestamp > $timeout) {
      $messenger->addError($this->t('You have tried to use an email address change link that has expired. Please visit your account and change your email again.'));
    }
    // The link is valid.
    elseif ($timestamp <= $request_time && $timestamp >= $user->getLastLoginTime() && hash_equals($hash, user_pass_rehash($user, $timestamp, $new_mail))) {
      // Save the new email but refresh also the last login time so that this
      // mail change link gets expired.
      $user->setEmail($new_mail)->setLastLoginTime($request_time)->save();
      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage = $this->entityTypeManager()->getStorage('user');
      $user_storage->updateLastLoginTimestamp($user);
      // Reflect the changes in the session if the user is logged in.
      if ($current_user->isAuthenticated() && $current_user->id() == $user->id()) {
        $current_user->setAccount($user);
      }
      $arguments = ['%mail' => $new_mail];
      $messenger->addStatus($this->t('Your email address has been changed to %mail.', $arguments));
    }
    // Timestamp from the link is abnormal (in the future) or user registered a
    // new login in the meantime or the hash is not valid.
    else {
      $messenger->addError($this->t('You have tried to use an email address change link that has either been used or is no longer valid. Please visit your account and change your email again.'));
    }

    return $this->redirect('<front>');
  }

  /**
   * Checks access to change email url.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account requesting Email change.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result
   */
  public function access(UserInterface $user) {
    return AccessResult::allowedIf($user->isActive());
  }

  /**
   * Generates a unique URL for a one time mail change confirmation.
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *   URLs. If langcode is NULL the users preferred language is used.
   * @param int $timestamp
   *   (optional) The timestamp when hash is created. If missed, the current
   *   request time is used.
   * @param string $hash
   *   (optional) Unique hash. If missed, the hash is computed based on the
   *   account data and timestamp.
   *
   * @return \Drupal\Core\Url
   *   A unique url that provides a one-time email change confirmation.
   */
  public static function getUrl(UserInterface $account, array $options = [], $timestamp = NULL, $hash = NULL) {
    $timestamp = $timestamp ?: \Drupal::time()->getRequestTime();
    $langcode = isset($options['langcode']) ? $options['langcode'] : $account->getPreferredLangcode();
    $hash = empty($hash) ? user_pass_rehash($account, $timestamp) : $hash;
    $url_options = ['absolute' => TRUE, 'language' => \Drupal::service('language_manager')->getLanguage($langcode)];
    return Url::fromRoute('user.mail_change', [
      'user' => $account->id(),
      'timestamp' => $timestamp,
      'new_mail' => $account->getEmail(),
      'hash' => $hash,
    ], $url_options);
  }

}
