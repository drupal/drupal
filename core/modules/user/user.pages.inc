<?php

/**
 * @file
 * User page callback file for the user module.
 */

use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Prepares variables for user templates.
 *
 * Default template: user.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *     fields attached to the user. Properties used:
 *     - #user: A \Drupal\user\Entity\User object. The user account of the
 *       profile being viewed.
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user(&$variables) {
  $account = $variables['elements']['#user'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Menu callback; Cancel a user account via email confirmation link.
 *
 * @see \Drupal\user\Form\UserCancelForm
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
      return new RedirectResponse(\Drupal::url('entity.user.cancel_form', ['user' => $account->id()], array('absolute' => TRUE)));
    }
  }
  throw new AccessDeniedHttpException();
}
