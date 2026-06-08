<?php

declare(strict_types=1);

namespace Drupal\user;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * Generate and verify one time authentication codes.
 *
 * One time authentication codes are used to build a unique and secure URL that
 * is sent to the user by email for purposes such as resetting the user's
 * password. The authentication code includes a timestamp, the user's last login
 * time, the numeric user ID, the user's email address. This data is keyed by
 * the users hashed password and the site's hash salt. All of this data is used
 * to verify the authentication link whenever it is used.
 */
final readonly class OneTimeAuthentication {

  public function __construct(
    protected TimeInterface $time,
    protected LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * Create a one time authentication code.
   *
   * One time authentication codes are used to build a unique and secure URL
   * that is sent to the user by email for purposes such as resetting the user's
   * password.
   *
   * For a usage example, see
   * \Drupal\user\OneTimeAuthentication::generateCancelConfirmUrl() and
   * \Drupal\user\Controller\UserController::confirmCancel().
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   * @param int $timestamp
   *   A UNIX timestamp, typically \Drupal::time()->getRequestTime().
   *
   * @return string
   *   A string that is safe for use in URLs and SQL statements.
   */
  public function generateHmac(UserInterface $account, int $timestamp): string {
    $data = $timestamp;
    $data .= ':' . $account->getLastLoginTime();
    $data .= ':' . $account->id();
    $data .= ':' . $account->getEmail();
    return Crypt::hmacBase64($data, Settings::getHashSalt() . $account->getPassword());
  }

  /**
   * Verify a one time authentication code and its timestamp.
   *
   * For a usage example, see
   * \Drupal\user\OneTimeAuthentication::generateCancelConfirmUrl() and
   * \Drupal\user\Controller\UserController::confirmCancel().
   *
   * @param \Drupal\user\UserInterface $account
   *   An account for which to verify the authentication code.
   * @param int $timestamp
   *   The timestamp of the authentication code.
   * @param string $hmac
   *   One time authentication code.
   * @param int $timeout
   *   Expiration timeout of authentication code.
   *
   * @return bool
   *   Whether the provided data are valid.
   */
  public function verifyHmac(UserInterface $account, int $timestamp, string $hmac, int $timeout = 0): bool {
    $current = $this->time->getRequestTime();
    $timeout_valid = ((!empty($timeout) && $current - $timestamp < $timeout) || empty($timeout));
    return ($timestamp >= $account->getLastLoginTime()) && $timestamp <= $current && $timeout_valid && hash_equals($hmac, $this->generateHmac($account, $timestamp));
  }

  /**
   * Generates a unique URL for a user to log in and reset their password.
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *     URLs. If langcode is NULL the users preferred language is used.
   * @param bool $immediate
   *   Whether or not to perform the login action immediately when the URL is
   *   opened. Defaults to false.
   */
  public function generateOneTimeLoginUrl(UserInterface $account, array $options = [], bool $immediate = FALSE): Url {
    $timestamp = $this->time->getCurrentTime();
    $langcode = $options['langcode'] ?? $account->getPreferredLangcode();
    $routeName = $immediate ? 'user.reset.login' : 'user.reset';
    return Url::fromRoute($routeName,
      [
        'uid' => $account->id(),
        'timestamp' => $timestamp,
        'hash' => $this->generateHmac($account, $timestamp),
      ],
      [
        'absolute' => TRUE,
        'language' => $this->languageManager->getLanguage($langcode),
      ]
    );
  }

  /**
   * Generates a URL to confirm an account cancellation request.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account object.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *     URLs. If langcode is NULL the users preferred language is used.
   *
   * @see ::tokens()
   * @see \Drupal\user\Controller\UserController::confirmCancel()
   */
  public function generateCancelConfirmUrl(UserInterface $account, array $options = []): Url {
    $timestamp = $this->time->getRequestTime();
    $langcode = $options['langcode'] ?? $account->getPreferredLangcode();
    return Url::fromRoute('user.cancel_confirm',
      [
        'user' => $account->id(),
        'timestamp' => $timestamp,
        'hashed_pass' => $this->generateHmac($account, $timestamp),
      ],
      [
        'absolute' => TRUE,
        'language' => $this->languageManager->getLanguage($langcode),
      ]
    );
  }

  /**
   * Token callback to add unsafe tokens for user notifications.
   *
   * This function is used by \Drupal\Core\Utility\Token::replace() to set up
   * some additional tokens that can be used in notifications generated by
   * user_mail().
   *
   * @param array $replacements
   *   An associative array variable containing mappings from token names to
   *   values (for use with strtr()).
   * @param array $data
   *   An associative array of token replacement values. If the 'user' element
   *   exists, it must contain a user account.
   * @param array $options
   *   A keyed array of settings and flags to control the token replacement
   *   process. See \Drupal\Core\Utility\Token::replace().
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleableMetadata
   *   Target for adding metadata.
   *
   * @internal
   */
  public function tokens(&$replacements, $data, $options, BubbleableMetadata $bubbleableMetadata): void {
    if (isset($data['user'])) {
      $oneTimeLoginUrl = $this->generateOneTimeLoginUrl($data['user'], $options)->toString(TRUE);
      $bubbleableMetadata->addCacheableDependency($oneTimeLoginUrl);
      $replacements['[user:one-time-login-url]'] = $oneTimeLoginUrl->getGeneratedUrl();

      $cancelConfirmUrl = $this->generateCancelConfirmUrl($data['user'], $options)->toString(TRUE);
      $bubbleableMetadata->addCacheableDependency($cancelConfirmUrl);
      $replacements['[user:cancel-url]'] = $cancelConfirmUrl->getGeneratedUrl();
    }
  }

}
