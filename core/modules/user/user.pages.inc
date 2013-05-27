<?php

/**
 * @file
 * User page callback file for the user module.
 */

use Drupal\Core\Language\Language;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Form builder; Request a password reset.
 *
 * @ingroup forms
 * @see user_pass_validate()
 * @see user_pass_submit()
 */
function user_pass() {
  global $user;

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Username or e-mail address'),
    '#size' => 60,
    '#maxlength' => max(USERNAME_MAX_LENGTH, EMAIL_MAX_LENGTH),
    '#required' => TRUE,
    '#attributes' => array(
      'autocorrect' => 'off',
      'autocapitalize' => 'off',
      'spellcheck' => 'false',
      'autofocus' => 'autofocus',
    ),
  );
  // Allow logged in users to request this also.
  if ($user->uid > 0) {
    $form['name']['#type'] = 'value';
    $form['name']['#value'] = $user->mail;
    $form['mail'] = array(
      '#prefix' => '<p>',
      '#markup' =>  t('Password reset instructions will be mailed to %email. You must log out to use the password reset link in the e-mail.', array('%email' => $user->mail)),
      '#suffix' => '</p>',
    );
  }
  else {
    $form['name']['#default_value'] = Drupal::Request()->query->get('name');
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('E-mail new password'));

  return $form;
}

function user_pass_validate($form, &$form_state) {
  $name = trim($form_state['values']['name']);
  // Try to load by email.
  $users = entity_load_multiple_by_properties('user', array('mail' => $name, 'status' => '1'));
  $account = reset($users);
  if (!$account) {
    // No success, try to load by name.
    $users = entity_load_multiple_by_properties('user', array('name' => $name, 'status' => '1'));
    $account = reset($users);
  }
  if (isset($account->uid)) {
    form_set_value(array('#parents' => array('account')), $account, $form_state);
  }
  else {
    form_set_error('name', t('Sorry, %name is not recognized as a username or an e-mail address.', array('%name' => $name)));
  }
}

function user_pass_submit($form, &$form_state) {
  $language_interface = language(Language::TYPE_INTERFACE);

  $account = $form_state['values']['account'];
  // Mail one time login URL and instructions using current language.
  $mail = _user_mail_notify('password_reset', $account, $language_interface->langcode);
  if (!empty($mail)) {
    watchdog('user', 'Password reset instructions mailed to %name at %email.', array('%name' => $account->name, '%email' => $account->mail));
    drupal_set_message(t('Further instructions have been sent to your e-mail address.'));
  }

  $form_state['redirect'] = 'user';
  return;
}

/**
 * Menu callback; process one time login link and redirects to the user page on success.
 */
function user_pass_reset($form, &$form_state, $uid, $timestamp, $hashed_pass, $action = NULL) {
  global $user;

  // When processing the one-time login link, we have to make sure that a user
  // isn't already logged in.
  if ($user->uid) {
    // The existing user is already logged in.
    if ($user->uid == $uid) {
      drupal_set_message(t('You are logged in as %user. <a href="!user_edit">Change your password.</a>', array('%user' => $user->name, '!user_edit' => url("user/$user->uid/edit"))));
    }
    // A different user is already logged in on the computer.
    else {
      $reset_link_account = user_load($uid);
      if (!empty($reset_link_account)) {
        drupal_set_message(t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href="!logout">logout</a> and try using the link again.',
          array('%other_user' => $user->name, '%resetting_user' => $reset_link_account->name, '!logout' => url('user/logout'))));
      } else {
        // Invalid one-time link specifies an unknown user.
        drupal_set_message(t('The one-time login link you clicked is invalid.'));
      }
    }
    drupal_goto();
  }
  else {
    // Time out, in seconds, until login URL expires.
    $timeout = config('user.settings')->get('password_reset_timeout');
    $current = REQUEST_TIME;
    $account = user_load($uid);
    // Verify that the user exists and is active.
    if ($timestamp <= $current && $account && $account->status) {
      // No time out for first time login.
      if ($account->login && $current - $timestamp > $timeout) {
        drupal_set_message(t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'));
        drupal_goto('user/password');
      }
      elseif ($account->uid && $timestamp >= $account->login && $timestamp <= $current && $hashed_pass == user_pass_rehash($account->pass, $timestamp, $account->login)) {
        // First stage is a confirmation form, then login
        if ($action == 'login') {
          // Set the new user.
          $user = $account;
          // user_login_finalize() also updates the login timestamp of the
          // user, which invalidates further use of the one-time login link.
          user_login_finalize();
          watchdog('user', 'User %name used one-time login link at time %timestamp.', array('%name' => $account->name, '%timestamp' => $timestamp));
          drupal_set_message(t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
          // Let the user's password be changed without the current password check.
          $token = Crypt::randomStringHashed(55);
          $_SESSION['pass_reset_' . $user->uid] = $token;
          drupal_goto('user/' . $user->uid . '/edit', array('query' => array('pass-reset-token' => $token)));
        }
        else {
          if (!$account->login) {
            // No expiration for first time login.
            $form['message'] = array('#markup' => t('<p>This is a one-time login for %user_name.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $account->name)));
          }
          else {
            $form['message'] = array('#markup' => t('<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $account->name, '%expiration_date' => format_date($timestamp + $timeout))));
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
        drupal_goto('user/password');
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
 * Logs the current user out, and redirects to the home page.
 */
function user_logout() {
  global $user;

  watchdog('user', 'Session closed for %name.', array('%name' => $user->name));

  module_invoke_all('user_logout', $user);

  // Destroy the current session, and reset $user to the anonymous user.
  session_destroy();

  drupal_goto();
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

  // Preprocess fields.
  field_attach_preprocess($account, $variables['elements'], $variables);

  // Set up attributes.
  $variables['attributes']['class'][] = 'profile';
}

/**
 * Submit function for the 'Cancel account' button on the user edit form.
 */
function user_edit_cancel_submit($form, &$form_state) {
  $destination = array();
  if (isset($_GET['destination'])) {
    $destination = drupal_get_destination();
    unset($_GET['destination']);
  }
  // Note: We redirect from user/uid/edit to user/uid/cancel to make the tabs disappear.
  $account = $form_state['controller']->getEntity();
  $form_state['redirect'] = array("user/" . $account->uid . "/cancel", array('query' => $destination));
}

/**
 * Form builder; confirm form for cancelling user account.
 *
 * @ingroup forms
 * @see user_edit_cancel_submit()
 */
function user_cancel_confirm_form($form, &$form_state, $account) {
  global $user;

  $form['_account'] = array('#type' => 'value', '#value' => $account);

  // Display account cancellation method selection, if allowed.
  $admin_access = user_access('administer users');
  $can_select_method = $admin_access || user_access('select account cancellation method');
  $form['user_cancel_method'] = array(
    '#type' => 'radios',
    '#title' => ($account->uid == $user->uid ? t('When cancelling your account') : t('When cancelling the account')),
    '#access' => $can_select_method,
  );
  $form['user_cancel_method'] += user_cancel_methods();

  // Allow user administrators to skip the account cancellation confirmation
  // mail (by default), as long as they do not attempt to cancel their own
  // account.
  $override_access = $admin_access && ($account->uid != $user->uid);
  $form['user_cancel_confirm'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require e-mail confirmation to cancel account.'),
    '#default_value' => ($override_access ? FALSE : TRUE),
    '#access' => $override_access,
    '#description' => t('When enabled, the user must confirm the account cancellation via e-mail.'),
  );
  // Also allow to send account canceled notification mail, if enabled.
  $default_notify = config('user.settings')->get('notify.status_canceled');
  $form['user_cancel_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is canceled.'),
    '#default_value' => ($override_access ? FALSE : $default_notify),
    '#access' => $override_access && $default_notify,
    '#description' => t('When enabled, the user will receive an e-mail notification after the account has been cancelled.'),
  );

  // Prepare confirmation form page title and description.
  if ($account->uid == $user->uid) {
    $question = t('Are you sure you want to cancel your account?');
  }
  else {
    $question = t('Are you sure you want to cancel the account %name?', array('%name' => $account->name));
  }
  $default_method = config('user.settings')->get('cancel_method');
  $description = NULL;
  if ($can_select_method) {
    $description = t('Select the method to cancel the account above.');
  }
  // Options supplied via user_cancel_methods() can have a custom
  // #confirm_description property for the confirmation form description.
  elseif (isset($form['user_cancel_method'][$default_method]['#confirm_description'])) {
    $description = $form['user_cancel_method'][$default_method]['#confirm_description'];
  }

  // Always provide entity id in the same form key as in the entity edit form.
  $form['uid'] = array('#type' => 'value', '#value' => $account->uid);
  return confirm_form($form,
    $question,
    'user/' . $account->uid,
    $description . ' ' . t('This action cannot be undone.'),
    t('Cancel account'), t('Cancel'));
}

/**
 * Submit handler for the account cancellation confirm form.
 *
 * @see user_cancel_confirm_form()
 * @see user_multiple_cancel_confirm_submit()
 */
function user_cancel_confirm_form_submit($form, &$form_state) {
  global $user;
  $account = $form_state['values']['_account'];

  // Cancel account immediately, if the current user has administrative
  // privileges, no confirmation mail shall be sent, and the user does not
  // attempt to cancel the own account.
  if (user_access('administer users') && empty($form_state['values']['user_cancel_confirm']) && $account->uid != $user->uid) {
    user_cancel($form_state['values'], $account->uid, $form_state['values']['user_cancel_method']);

    $form_state['redirect'] = 'admin/people';
  }
  else {
    // Store cancelling method and whether to notify the user in $account for
    // user_cancel_confirm().
    $account->user_cancel_method = $form_state['values']['user_cancel_method'];
    $account->user_cancel_notify = $form_state['values']['user_cancel_notify'];
    $account->save();
    _user_mail_notify('cancel_confirm', $account);
    drupal_set_message(t('A confirmation request to cancel your account has been sent to your e-mail address.'));
    watchdog('user', 'Sent account cancellation request to %name %email.', array('%name' => $account->name, '%email' => '<' . $account->mail . '>'), WATCHDOG_NOTICE);

    $form_state['redirect'] = "user/$account->uid";
  }
}

/**
 * Helper function to return available account cancellation methods.
 *
 * See documentation of hook_user_cancel_methods_alter().
 *
 * @return
 *   An array containing all account cancellation methods as form elements.
 *
 * @see hook_user_cancel_methods_alter()
 * @see user_admin_settings()
 * @see user_cancel_confirm_form()
 * @see user_multiple_cancel_confirm()
 */
function user_cancel_methods() {
  $anonymous_name = config('user.settings')->get('anonymous');
  $methods = array(
    'user_cancel_block' => array(
      'title' => t('Disable the account and keep its content.'),
      'description' => t('Your account will be blocked and you will no longer be able to log in. All of your content will remain attributed to your user name.'),
    ),
    'user_cancel_block_unpublish' => array(
      'title' => t('Disable the account and unpublish its content.'),
      'description' => t('Your account will be blocked and you will no longer be able to log in. All of your content will be hidden from everyone but administrators.'),
    ),
    'user_cancel_reassign' => array(
      'title' => t('Delete the account and make its content belong to the %anonymous-name user.', array('%anonymous-name' => $anonymous_name)),
      'description' => t('Your account will be removed and all account information deleted. All of your content will be assigned to the %anonymous-name user.', array('%anonymous-name' => $anonymous_name)),
    ),
    'user_cancel_delete' => array(
      'title' => t('Delete the account and its content.'),
      'description' => t('Your account will be removed and all account information deleted. All of your content will also be deleted.'),
      'access' => user_access('administer users'),
    ),
  );
  // Allow modules to customize account cancellation methods.
  drupal_alter('user_cancel_methods', $methods);

  // Turn all methods into real form elements.
  $default_method = config('user.settings')->get('cancel_method');
  $form = array(
    '#options' => array(),
    '#default_value' => $default_method,
  );
  foreach ($methods as $name => $method) {
    $form['#options'][$name] = $method['title'];
    // Add the description for the confirmation form. This description is never
    // shown for the cancel method option, only on the confirmation form.
    // Therefore, we use a custom #confirm_description property.
    if (isset($method['description'])) {
      $form[$name]['#confirm_description'] = $method['description'];
    }
    if (isset($method['access'])) {
      $form[$name]['#access'] = $method['access'];
    }
  }
  return $form;
}

/**
 * Menu callback; Cancel a user account via e-mail confirmation link.
 *
 * @see user_cancel_confirm_form()
 * @see user_cancel_url()
 */
function user_cancel_confirm($account, $timestamp = 0, $hashed_pass = '') {
  // Time out in seconds until cancel URL expires; 24 hours = 86400 seconds.
  $timeout = 86400;
  $current = REQUEST_TIME;

  // Basic validation of arguments.
  $account_data = drupal_container()->get('user.data')->get('user', $account->id());
  if (isset($account_data['cancel_method']) && !empty($timestamp) && !empty($hashed_pass)) {
    // Validate expiration and hashed password/login.
    if ($timestamp <= $current && $current - $timestamp < $timeout && $account->uid && $timestamp >= $account->login && $hashed_pass == user_pass_rehash($account->pass, $timestamp, $account->login)) {
      $edit = array(
        'user_cancel_notify' => isset($account_data['cancel_notify']) ? $account_data['cancel_notify'] : config('user.settings')->get('notify.status_canceled'),
      );
      user_cancel($edit, $account->id(), $account_data['cancel_method']);
      // Since user_cancel() is not invoked via Form API, batch processing needs
      // to be invoked manually and should redirect to the front page after
      // completion.
      batch_process('');
    }
    else {
      drupal_set_message(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'));
      drupal_goto("user/$account->uid/cancel");
    }
  }
  throw new AccessDeniedHttpException();
}

/**
 * Access callback for path /user.
 *
 * Displays user profile if user is logged in, or login form for anonymous
 * users.
 */
function user_page() {
  global $user;
  if ($user->uid) {
    return new RedirectResponse(url('user/' . $user->uid, array('absolute' => TRUE)));
  }
  else {
    return drupal_get_form('user_login_form');
  }
}
