<?php

/**
 * @file
 * User page callback file for the user module.
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Menu callback; process one time login link and redirects to the user page on success.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\user\Form\UserForm::resetPass().
 */
function user_pass_reset($form, &$form_state, $uid, $timestamp, $hashed_pass, $action = NULL) {
  global $user;

  // When processing the one-time login link, we have to make sure that a user
  // isn't already logged in.
  if ($user->isAuthenticated()) {
    // The existing user is already logged in.
    if ($user->id() == $uid) {
      drupal_set_message(t('You are logged in as %user. <a href="!user_edit">Change your password.</a>', array('%user' => $user->getUsername(), '!user_edit' => url("user/" . $user->id() . "/edit"))));
    }
    // A different user is already logged in on the computer.
    else {
      $reset_link_account = user_load($uid);
      if (!empty($reset_link_account)) {
        drupal_set_message(t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href="!logout">logout</a> and try using the link again.',
          array('%other_user' => $user->getUsername(), '%resetting_user' => $reset_link_account->getUsername(), '!logout' => url('user/logout'))));
      } else {
        // Invalid one-time link specifies an unknown user.
        drupal_set_message(t('The one-time login link you clicked is invalid.'));
      }
    }
    return new RedirectResponse(url('<front>', array('absolute' => TRUE)));
  }
  else {
    // Time out, in seconds, until login URL expires.
    $timeout = \Drupal::config('user.settings')->get('password_reset_timeout');
    $current = REQUEST_TIME;
    $account = user_load($uid);
    // Verify that the user exists and is active.
    if ($timestamp <= $current && $account && $account->isActive()) {
      // No time out for first time login.
      if ($account->getLastLoginTime() && $current - $timestamp > $timeout) {
        drupal_set_message(t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
        return new RedirectResponse(url('user/password', array('absolute' => TRUE)));
      }
      elseif ($account->isAuthenticated() && $timestamp >= $account->getLastLoginTime() && $timestamp <= $current && $hashed_pass == user_pass_rehash($account->getPassword(), $timestamp, $account->getLastLoginTime())) {
        // First stage is a confirmation form, then login
        if ($action == 'login') {
          // Set the new user.
          // user_login_finalize() also updates the login timestamp of the
          // user, which invalidates further use of the one-time login link.
          user_login_finalize($account);
          watchdog('user', 'User %name used one-time login link at time %timestamp.', array('%name' => $account->getUsername(), '%timestamp' => $timestamp));
          drupal_set_message(t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
          // Let the user's password be changed without the current password check.
          $token = Crypt::randomBytesBase64(55);
          $_SESSION['pass_reset_' . $user->id()] = $token;
          return new RedirectResponse(url('user/' . $user->id() . '/edit', array(
            'query' => array('pass-reset-token' => $token),
            'absolute' => TRUE,
          )));
        }
        else {
          if (!$account->getLastLoginTime()) {
            // No expiration for first time login.
            $form['message'] = array('#markup' => t('<p>This is a one-time login for %user_name.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $account->getUsername())));
          }
          else {
            $form['message'] = array('#markup' => t('<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $account->getUsername(), '%expiration_date' => format_date($timestamp + $timeout))));
          }
          $form['help'] = array('#markup' => '<p>' . t('This login can be used only once.') . '</p>');
          $form['actions'] = array('#type' => 'actions');
          $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Log in'));
          $form['#action'] = url("user/reset/$uid/$timestamp/$hashed_pass/login");
          return $form;
        }
      }
      else {
        drupal_set_message(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
        return new RedirectResponse(url('user/password', array('absolute' => TRUE)));
      }
    }
    else {
      // Deny access, no more clues.
      // Everything will be in the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }
  }
}

/**
 * Prepares variables for user templates.
 *
 * Default template: user.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - account: The user account.
 */
function template_preprocess_user(&$variables) {
  $account = $variables['elements']['#user'];

  // Helpful $content variable for templates.
  foreach (element_children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Set up attributes.
  $variables['attributes']['class'][] = 'profile';
}

/**
 * Menu callback; Cancel a user account via e-mail confirmation link.
 *
 * @see user_cancel_confirm_form()
 * @see user_cancel_url()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\user\Controller\UserController::confirmCancel().
 */
function user_cancel_confirm($account, $timestamp = 0, $hashed_pass = '') {
  // Time out in seconds until cancel URL expires; 24 hours = 86400 seconds.
  $timeout = 86400;
  $current = REQUEST_TIME;

  // Basic validation of arguments.
  $account_data = \Drupal::service('user.data')->get('user', $account->id());
  if (isset($account_data['cancel_method']) && !empty($timestamp) && !empty($hashed_pass)) {
    // Validate expiration and hashed password/login.
    if ($timestamp <= $current && $current - $timestamp < $timeout && $account->id() && $timestamp >= $account->getLastLoginTime() && $hashed_pass == user_pass_rehash($account->getPassword(), $timestamp, $account->getLastLoginTime())) {
      $edit = array(
        'user_cancel_notify' => isset($account_data['cancel_notify']) ? $account_data['cancel_notify'] : \Drupal::config('user.settings')->get('notify.status_canceled'),
      );
      user_cancel($edit, $account->id(), $account_data['cancel_method']);
      // Since user_cancel() is not invoked via Form API, batch processing needs
      // to be invoked manually and should redirect to the front page after
      // completion.
      return batch_process('');
    }
    else {
      drupal_set_message(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'));
      return new RedirectResponse(url("user/" . $account->id() . "/cancel", array('absolute' => TRUE)));
    }
  }
  throw new AccessDeniedHttpException();
}
