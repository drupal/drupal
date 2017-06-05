<?php

namespace Drupal\user\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Form\UserPasswordResetForm;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for user routes.
 */
class UserController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data, LoggerInterface $logger) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('logger.factory')->get('user')
    );
  }

  /**
   * Redirects to the user password reset form.
   *
   * In order to never disclose a reset link via a referrer header this
   * controller must always return a redirect response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
    $account = $this->currentUser();
    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      // The current user is already logged in.
      if ($account->id() == $uid) {
        user_logout();
        // We need to begin the redirect process again because logging out will
        // destroy the session.
        return $this->redirect(
          'user.reset',
          [
            'uid' => $uid,
            'timestamp' => $timestamp,
            'hash' => $hash,
          ]
        );
      }
      // A different user is already logged in on the computer.
      else {
        /** @var \Drupal\user\UserInterface $reset_link_user */
        if ($reset_link_user = $this->userStorage->load($uid)) {
          drupal_set_message($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
            ['%other_user' => $account->getUsername(), '%resetting_user' => $reset_link_user->getUsername(), ':logout' => $this->url('user.logout')]), 'warning');
        }
        else {
          // Invalid one-time link specifies an unknown user.
          drupal_set_message($this->t('The one-time login link you clicked is invalid.'), 'error');
        }
        return $this->redirect('<front>');
      }
    }

    $session = $request->getSession();
    $session->set('pass_reset_hash', $hash);
    $session->set('pass_reset_timeout', $timestamp);
    return $this->redirect(
      'user.reset.form',
      ['uid' => $uid]
    );
  }

  /**
   * Returns the user password reset form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $uid
   *   User ID of the user requesting reset.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the pass_reset_timeout or pass_reset_hash are not available in the
   *   session. Or if $uid is for a blocked user or invalid user ID.
   */
  public function getResetPassForm(Request $request, $uid) {
    $session = $request->getSession();
    $timestamp = $session->get('pass_reset_timeout');
    $hash = $session->get('pass_reset_hash');
    // As soon as the session variables are used they are removed to prevent the
    // hash and timestamp from being leaked unexpectedly. This could occur if
    // the user does not click on the log in button on the form.
    $session->remove('pass_reset_timeout');
    $session->remove('pass_reset_hash');
    if (!$hash || !$timestamp) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');

    $expiration_date = $user->getLastLoginTime() ? $this->dateFormatter->format($timestamp + $timeout) : NULL;
    return $this->formBuilder()->getForm(UserPasswordResetForm::class, $user, $expiration_date, $timestamp, $hash);
  }

  /**
   * Validates user, hash, and timestamp; logs the user in if correct.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the user edit form if the information is correct.
   *   If the information is incorrect redirects to 'user.pass' route with a
   *   message for the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If $uid is for a blocked user or invalid user ID.
   */
  public function resetPassLogin($uid, $timestamp, $hash) {
    // The current user is not logged in, so check the parameters.
    $current = REQUEST_TIME;
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    // No time out for first time login.
    if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
      drupal_set_message($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'error');
      return $this->redirect('user.pass');
    }
    elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
      user_login_finalize($user);
      $this->logger->notice('User %name used one-time login link at time %timestamp.', ['%name' => $user->getDisplayName(), '%timestamp' => $timestamp]);
      drupal_set_message($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
      // Let the user's password be changed without the current password
      // check.
      $token = Crypt::randomBytesBase64(55);
      $_SESSION['pass_reset_' . $user->id()] = $token;
      return $this->redirect(
        'entity.user.edit_form',
        ['user' => $user->id()],
        [
          'query' => ['pass-reset-token' => $token],
          'absolute' => TRUE,
        ]
      );
    }

    drupal_set_message($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
    return $this->redirect('user.pass');
  }

  /**
   * Redirects users to their profile page.
   *
   * This controller assumes that it is only invoked for authenticated users.
   * This is enforced for the 'user.page' route with the '_user_is_logged_in'
   * requirement.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the profile of the currently logged in user.
   */
  public function userPage() {
    return $this->redirect('entity.user.canonical', ['user' => $this->currentUser()->id()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return string|array
   *   The user account name as a render array or an empty string if $user is
   *   NULL.
   */
  public function userTitle(UserInterface $user = NULL) {
    return $user ? ['#markup' => $user->getUsername(), '#allowed_tags' => Xss::getHtmlTagList()] : '';
  }

  /**
   * Logs the current user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout() {
    user_logout();
    return $this->redirect('<front>');
  }

  /**
   * Confirms cancelling a user account via an email link.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param int $timestamp
   *   The timestamp.
   * @param string $hashed_pass
   *   The hashed password.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function confirmCancel(UserInterface $user, $timestamp = 0, $hashed_pass = '') {
    // Time out in seconds until cancel URL expires; 24 hours = 86400 seconds.
    $timeout = 86400;
    $current = REQUEST_TIME;

    // Basic validation of arguments.
    $account_data = $this->userData->get('user', $user->id());
    if (isset($account_data['cancel_method']) && !empty($timestamp) && !empty($hashed_pass)) {
      // Validate expiration and hashed password/login.
      if ($timestamp <= $current && $current - $timestamp < $timeout && $user->id() && $timestamp >= $user->getLastLoginTime() && Crypt::hashEquals($hashed_pass, user_pass_rehash($user, $timestamp))) {
        $edit = [
          'user_cancel_notify' => isset($account_data['cancel_notify']) ? $account_data['cancel_notify'] : $this->config('user.settings')->get('notify.status_canceled'),
        ];
        user_cancel($edit, $user->id(), $account_data['cancel_method']);
        // Since user_cancel() is not invoked via Form API, batch processing
        // needs to be invoked manually and should redirect to the front page
        // after completion.
        return batch_process('');
      }
      else {
        drupal_set_message(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'), 'error');
        return $this->redirect('entity.user.cancel_form', ['user' => $user->id()], ['absolute' => TRUE]);
      }
    }
    throw new AccessDeniedHttpException();
  }

}
