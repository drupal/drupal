<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserController.
 */

namespace Drupal\user\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\user\UserStorageInterface;

/**
 * Controller routines for user routes.
 */
class UserController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(DateFormatter $date_formatter, UserStorageInterface $user_storage) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * Returns the user password reset page.
   *
   * @param int $uid
   *   UID of user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the login link is for a blocked user or invalid user ID.
   */
  public function resetPass($uid, $timestamp, $hash) {
    $account = $this->currentUser();
    $config = $this->config('user.settings');
    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      // The current user is already logged in.
      if ($account->id() == $uid) {
        drupal_set_message($this->t('You are logged in as %user. <a href="!user_edit">Change your password.</a>', array('%user' => $account->getUsername(), '!user_edit' => $this->url('user.edit', array('user' => $account->id())))));
      }
      // A different user is already logged in on the computer.
      else {
        if ($reset_link_user = $this->userStorage->load($uid)) {
          drupal_set_message($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href="!logout">logout</a> and try using the link again.',
            array('%other_user' => $account->getUsername(), '%resetting_user' => $reset_link_user->getUsername(), '!logout' => $this->url('user.logout'))));
        }
        else {
          // Invalid one-time link specifies an unknown user.
          drupal_set_message($this->t('The one-time login link you clicked is invalid.'));
        }
      }
      return $this->redirect('<front>');
    }
    else {
      // The current user is not logged in, so check the parameters.
      // Time out, in seconds, until login URL expires.
      $timeout = $config->get('password_reset_timeout');
      $current = REQUEST_TIME;
      /* @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->load($uid);
      // Verify that the user exists and is active.
      if ($user && $user->isActive()) {
        // No time out for first time login.
        if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
          drupal_set_message($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
          return $this->redirect('user.pass');
        }
        elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && ($hash === user_pass_rehash($user->getPassword(), $timestamp, $user->getLastLoginTime()))) {
          $expiration_date = $user->getLastLoginTime() ? $this->dateFormatter->format($timestamp + $timeout) : NULL;
          return $this->formBuilder()->getForm('Drupal\user\Form\UserPasswordResetForm', $user, $expiration_date, $timestamp, $hash);
        }
        else {
          drupal_set_message($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
          return $this->redirect('user.pass');
        }
      }
    }
    // Blocked or invalid user ID, so deny access. The parameters will be in the
    // watchdog's URL for the administrator to check.
    throw new AccessDeniedHttpException();
  }

  /**
   * Returns the user page.
   *
   * Displays user profile if user is logged in, or login form for anonymous
   * users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Returns either a redirect to the user page or the render
   *   array of the login form.
   */
  public function userPage(Request $request) {
    $user = $this->currentUser();
    if ($user->id()) {
      $response = $this->redirect('user.view', array('user' => $user->id()));
    }
    else {
      $form_builder = $this->formBuilder();
      $response = $form_builder->getForm('Drupal\user\Form\UserLoginForm');
    }
    return $response;
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return string
   *   The user account name.
   */
  public function userTitle(UserInterface $user = NULL) {
    return $user ? Xss::filter($user->getUsername()) : '';
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
   * @todo Remove user_cancel_confirm().
   */
  public function confirmCancel(UserInterface $user, $timestamp = 0, $hashed_pass = '') {
    module_load_include('pages.inc', 'user');
    return user_cancel_confirm($user, $timestamp, $hashed_pass);
  }

}
