<?php

/**
 * @file
 * User page callback file for the user module.
 */

/**
 * Menu callback; Retrieve a JSON object containing autocomplete suggestions for existing users.
 */
function user_autocomplete($string = '') {
  $matches = array();
  if ($string) {
    $result = db_select('users')->fields('users', array('name'))->condition('name', db_like($string) . '%', 'LIKE')->range(0, 10)->execute();
    foreach ($result as $user) {
      $matches[$user->name] = check_plain($user->name);
    }
  }

  drupal_json_output($matches);
}

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
    '#default_value' => isset($_GET['name']) ? $_GET['name'] : '',
  );
  // Allow logged in users to request this also.
  if ($user->uid > 0) {
    $form['name']['#type'] = 'value';
    $form['name']['#value'] = $user->mail;
    $form['mail'] = array(
      '#prefix' => '<p>',
      // As of https://www.drupal.org/node/889772 the user no longer must log
      // out (if they are still logged in when using the password reset link,
      // they will be logged out automatically then), but this text is kept as
      // is to avoid breaking translations as well as to encourage the user to
      // log out manually at a time of their own choosing (when it will not
      // interrupt anything else they may have been in the middle of doing).
      '#markup' =>  t('Password reset instructions will be mailed to %email. You must log out to use the password reset link in the e-mail.', array('%email' => $user->mail)),
      '#suffix' => '</p>',
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('E-mail new password'));

  return $form;
}

/**
 * Form validation handler for user_pass().
 *
 * @see user_pass_submit()
 */
function user_pass_validate($form, &$form_state) {
  if (isset($form_state['values']['name']) && !is_scalar($form_state['values']['name'])) {
    form_set_error('name', t('An illegal value has been detected. Please contact the site administrator.'));
    return;
  }
  $user_pass_reset_ip_window = variable_get('user_pass_reset_ip_window', 3600);
  // Do not allow any password reset from the current user's IP if the limit
  // has been reached. Default is 50 attempts allowed in one hour. This is
  // independent of the per-user limit to catch attempts from one IP to request
  // resets for many different user accounts. We have a reasonably high limit
  // since there may be only one apparent IP for all users at an institution.
  if (!flood_is_allowed('pass_reset_ip', variable_get('user_pass_reset_ip_limit', 50), $user_pass_reset_ip_window)) {
    form_set_error('name', t('Sorry, too many password reset attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href="@url">request a new password</a>.', array('@url' => url('user/password'))));
    return;
  }
  // Always register an per-IP event.
  flood_register_event('pass_reset_ip', $user_pass_reset_ip_window);
  $name = trim($form_state['values']['name']);
  // Try to load by email.
  $users = user_load_multiple(array(), array('mail' => $name, 'status' => '1'));
  $account = reset($users);
  if (!$account) {
    // No success, try to load by name.
    $users = user_load_multiple(array(), array('name' => $name, 'status' => '1'));
    $account = reset($users);
  }
  if (isset($account->uid)) {
    // Register user flood events based on the uid only, so they can be cleared
    // when a password is reset successfully.
    $identifier = $account->uid;
    $user_pass_reset_user_window = variable_get('user_pass_reset_user_window', 21600);
    $user_pass_reset_user_limit = variable_get('user_pass_reset_user_limit', 5);
    // Don't allow password reset if the limit for this user has been reached.
    // Default is to allow 5 passwords resets every 6 hours.
    if (!flood_is_allowed('pass_reset_user', $user_pass_reset_user_limit, $user_pass_reset_user_window, $identifier)) {
      form_set_error('name', format_plural($user_pass_reset_user_limit, 'Sorry, there has been more than one password reset attempt for this account. It is temporarily blocked. Try again later or <a href="@url">login with your password</a>.', 'Sorry, there have been more than @count password reset attempts for this account. It is temporarily blocked. Try again later or <a href="@url">login with your password</a>.', array('@url' => url('user/login'))));
      return;
    }
    // Register a per-user event.
    flood_register_event('pass_reset_user', $user_pass_reset_user_window, $identifier);
    form_set_value(array('#parents' => array('account')), $account, $form_state);
  }
}

/**
 * Form submission handler for user_pass().
 *
 * @see user_pass_validate()
 */
function user_pass_submit($form, &$form_state) {
  global $language;

  $name = $form_state['values']['name'];
  if (isset($form_state['values']['account'])) {
    $account = $form_state['values']['account'];
    // Mail one time login URL and instructions using current language.
    $mail = _user_mail_notify('password_reset', $account, $language);
    if (!empty($mail)) {
      watchdog('user', 'Password reset instructions mailed to %name at %email.', array('%name' => $account->name, '%email' => $account->mail));
    }
  }
  else {
    watchdog('user', 'Password reset form was submitted with an unknown or inactive account: %name.', array('%name' => $name));
  }

  $password_reset_text = variable_get('user_password_reset_text', t('If %identifier is a valid account, an email will be sent with instructions to reset your password.'));
  drupal_set_message(format_string($password_reset_text, array('%identifier' => $name)));
  $form_state['redirect'] = 'user';
  return;
}

/**
 * Menu callback; process one time login link and redirects to the user page on success.
 *
 * In order to never disclose password reset hashes via referrer headers or
 * web browser history, this function always issues a redirect when a valid
 * password reset hash is in the URL.
 */
function user_pass_reset($form, &$form_state, $uid, $timestamp, $hashed_pass, $action = NULL) {
  global $user;

  // Check for a reset hash in the session. Immediately remove it (to prevent it
  // from being reused, for example if this page is visited again via the
  // browser history) and store it for later use.
  if (isset($_SESSION['pass_reset_hash'])) {
    $session_reset_hash = $_SESSION['pass_reset_hash'];
    unset($_SESSION['pass_reset_hash']);
  }
  else {
    $session_reset_hash = NULL;
  }

  // When processing the one-time login link, we have to make sure that a user
  // isn't already logged in.
  if ($user->uid) {
    // The existing user is already logged in. Log them out and reload the
    // current page so the password reset process can continue.
    if ($user->uid == $uid) {
      // Preserve the current destination (if any) and ensure the redirect goes
      // back to the current page; any custom destination set in
      // hook_user_logout() and intended for regular logouts would not be
      // appropriate here.
      $destination = array();
      if (isset($_GET['destination'])) {
        $destination = drupal_get_destination();
      }
      user_logout_current_user();
      unset($_GET['destination']);
      drupal_goto(current_path(), array('query' => drupal_get_query_parameters() + $destination));
    }
    // A different user is already logged in on the computer.
    else {
      $reset_link_account = user_load($uid);
      $is_valid = FALSE;
      if (!empty($reset_link_account) && $timestamp >= $reset_link_account->login && $timestamp <= REQUEST_TIME) {
        if ($hashed_pass == user_pass_rehash($reset_link_account->pass, $timestamp, $reset_link_account->login, $reset_link_account->uid, $reset_link_account->mail)) {
          $is_valid = TRUE;
        }
        elseif ($session_reset_hash && $session_reset_hash == user_pass_rehash($reset_link_account->pass, $timestamp, $reset_link_account->login, $reset_link_account->uid, $reset_link_account->mail)) {
          $is_valid = TRUE;
        }
      }
      if ($is_valid) {
        drupal_set_message(t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href="!logout">logout</a> and try using the link again.',
          array('%other_user' => $user->name, '%resetting_user' => $reset_link_account->name, '!logout' => url('user/logout'))), 'warning');
      } else {
        // Invalid one-time link specifies an unknown user.
        drupal_set_message(t('The one-time login link you clicked is invalid.'), 'error');
      }
      drupal_goto();
    }
  }
  else {
    // Time out, in seconds, until login URL expires. Defaults to 24 hours =
    // 86400 seconds.
    $timeout = variable_get('user_password_reset_timeout', 86400);
    $current = REQUEST_TIME;
    // Some redundant checks for extra security ?
    $users = user_load_multiple(array($uid), array('status' => '1'));
    if ($timestamp <= $current && $account = reset($users)) {
      // No time out for first time login.
      if ($account->login && $current - $timestamp > $timeout) {
        drupal_set_message(t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'error');
        drupal_goto('user/password');
      }
      // Validate the reset hash from the URL or from the session.
      $is_valid = FALSE;
      if ($account->uid && $timestamp >= $account->login && $timestamp <= $current) {
        // If the reset hash in the URL is valid, put it in the session and
        // redirect to the same URL but with the hash replaced by an invalid
        // one ("confirm"). This prevents disclosing the hash via referrer
        // headers or web browser history.
        if ($hashed_pass == user_pass_rehash($account->pass, $timestamp, $account->login, $account->uid, $account->mail)) {
          if ($action === 'login') {
            // The 'login' action redirects directly to the user edit form.
            $is_valid = TRUE;
          }
          else {
            $_SESSION['pass_reset_hash'] = $hashed_pass;
            $args = arg();
            foreach ($args as &$arg) {
              if ($arg == $hashed_pass) {
                $arg = 'confirm';
              }
            }
            $path = implode('/', $args);
            drupal_goto($path, array('query' => drupal_get_query_parameters()));
          }
        }
        // If the reset hash from the session is valid, use that.
        elseif ($session_reset_hash && $session_reset_hash == user_pass_rehash($account->pass, $timestamp, $account->login, $account->uid, $account->mail)) {
          $is_valid = TRUE;
        }
      }
      if ($is_valid) {
        // First stage is a confirmation form, then login
        if ($action == 'login') {
          if (variable_get('user_failed_login_identifier_uid_only', FALSE)) {
            // Clear flood events based on the uid only if configured.
            $identifier = $account->uid;
          }
          else {
            // The default identifier is a combination of uid and IP address.
            $identifier = $account->uid . '-' . ip_address();
          }
          // Only clear the user specific flood events. We cannot clear the more
          // broad IP address flood events because that would open a
          // vulnerability where an attacker with a valid account could use that
          // to brute force other accounts.
          flood_clear_event('failed_login_attempt_user', $identifier);

          // Set the new user.
          $user = $account;
          // user_login_finalize() also updates the login timestamp of the
          // user, which invalidates further use of the one-time login link.
          user_login_finalize();
          // Clear any password reset flood events for this user.
          flood_clear_event('pass_reset_user', $account->uid);
          watchdog('user', 'User %name used one-time login link at time %timestamp.', array('%name' => $account->name, '%timestamp' => $timestamp));
          drupal_set_message(t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
          // Let the user's password be changed without the current password check.
          $token = drupal_random_key();
          $_SESSION['pass_reset_' . $user->uid] = $token;
          drupal_goto('user/' . $user->uid . '/edit', array('query' => array('pass-reset-token' => $token)));
        }
        else {
          $form['message'] = array('#markup' => t('<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to log in to the site and change your password.</p>', array('%user_name' => $account->name, '%expiration_date' => format_date($timestamp + $timeout))));
          $form['help'] = array('#markup' => '<p>' . t('This login can be used only once.') . '</p>');
          $form['actions'] = array('#type' => 'actions');
          $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Log in'));
          $form['#action'] = url("user/reset/$uid/$timestamp/$session_reset_hash/login");
          // Prevent the browser from storing this page so that the token will
          // not be visible in the form action if the back button is used to
          // revisit this page.
          drupal_add_http_header('Cache-Control', 'no-store');
          return $form;
        }
      }
      else {
        drupal_set_message(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
        drupal_goto('user/password');
      }
    }
    else {
      // Deny access, no more clues.
      // Everything will be in the watchdog's URL for the administrator to check.
      drupal_access_denied();
      drupal_exit();
    }
  }
}

/**
 * Menu callback; logs the current user out, and redirects to the home page.
 */
function user_logout() {
  user_logout_current_user();
  drupal_goto();
}

/**
 * Logs the current user out.
 */
function user_logout_current_user() {
  global $user;

  watchdog('user', 'Session closed for %name.', array('%name' => $user->name));

  module_invoke_all('user_logout', $user);

  // Destroy the current session, and reset $user to the anonymous user.
  session_destroy();
}

/**
 * Process variables for user-profile.tpl.php.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *     fields attached to the user. Properties used:
 *     - #account: The user account of the profile being viewed.
 *
 * @see user-profile.tpl.php
 */
function template_preprocess_user_profile(&$variables) {
  $account = $variables['elements']['#account'];

  // Helpful $user_profile variable for templates.
  foreach (element_children($variables['elements']) as $key) {
    $variables['user_profile'][$key] = $variables['elements'][$key];
  }

  // Preprocess fields.
  field_attach_preprocess('user', $account, $variables['elements'], $variables);
}

/**
 * Process variables for user-profile-item.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $element
 *
 * @see user-profile-item.tpl.php
 */
function template_preprocess_user_profile_item(&$variables) {
  $variables['title'] = $variables['element']['#title'];
  $variables['value'] = $variables['element']['#markup'];
  $variables['attributes'] = '';
  if (isset($variables['element']['#attributes'])) {
    $variables['attributes'] = drupal_attributes($variables['element']['#attributes']);
  }
}

/**
 * Process variables for user-profile-category.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $element
 *
 * @see user-profile-category.tpl.php
 */
function template_preprocess_user_profile_category(&$variables) {
  $variables['title'] = check_plain($variables['element']['#title']);
  $variables['profile_items'] = $variables['element']['#children'];
  $variables['attributes'] = '';
  if (isset($variables['element']['#attributes'])) {
    $variables['attributes'] = drupal_attributes($variables['element']['#attributes']);
  }
}

/**
 * Form builder; edit a user account or one of their profile categories.
 *
 * @ingroup forms
 * @see user_account_form()
 * @see user_account_form_validate()
 * @see user_profile_form_validate()
 * @see user_profile_form_submit()
 * @see user_cancel_confirm_form_submit()
 */
function user_profile_form($form, &$form_state, $account, $category = 'account') {
  global $user;

  // During initial form build, add the entity to the form state for use during
  // form building and processing. During a rebuild, use what is in the form
  // state.
  if (!isset($form_state['user'])) {
    $form_state['user'] = $account;
  }
  else {
    $account = $form_state['user'];
  }

  // @todo Legacy support. Modules are encouraged to access the entity using
  //   $form_state. Remove in Drupal 8.
  $form['#user'] = $account;
  $form['#user_category'] = $category;

  if ($category == 'account') {
    user_account_form($form, $form_state);
    // Attach field widgets.
    $langcode = entity_language('user', $account);
    field_attach_form('user', $account, $form, $form_state, $langcode);
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  if ($category == 'account') {
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => t('Cancel account'),
      '#submit' => array('user_edit_cancel_submit'),
      '#access' => $account->uid > 1 && (($account->uid == $user->uid && user_access('cancel account')) || user_access('administer users')),
    );
  }

  $form['#validate'][] = 'user_profile_form_validate';
  // Add the final user profile form submit handler.
  $form['#submit'][] = 'user_profile_form_submit';

  return $form;
}

/**
 * Form validation handler for user_profile_form().
 *
 * @see user_profile_form_submit()
 */
function user_profile_form_validate($form, &$form_state) {
  entity_form_field_validate('user', $form, $form_state);
}

/**
 * Form submission handler for user_profile_form().
 *
 * @see user_profile_form_validate()
 */
function user_profile_form_submit($form, &$form_state) {
  $account = $form_state['user'];
  $category = $form['#user_category'];
  // Remove unneeded values.
  form_state_values_clean($form_state);

  // Before updating the account entity, keep an unchanged copy for use with
  // user_save() later. This is necessary for modules implementing the user
  // hooks to be able to react on changes by comparing the values of $account
  // and $edit.
  $account_unchanged = clone $account;

  entity_form_submit_build_entity('user', $account, $form, $form_state);

  // Populate $edit with the properties of $account, which have been edited on
  // this form by taking over all values, which appear in the form values too.
  $edit = array_intersect_key((array) $account, $form_state['values']);

  user_save($account_unchanged, $edit, $category);
  $form_state['values']['uid'] = $account->uid;

  if ($category == 'account' && !empty($edit['pass'])) {
    // Remove the password reset tag since a new password was saved.
    unset($_SESSION['pass_reset_'. $account->uid]);
  }
  // Clear the page cache because pages can contain usernames and/or profile information:
  cache_clear_all();

  drupal_set_message(t('The changes have been saved.'));
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
  $form_state['redirect'] = array("user/" . $form['#user']->uid . "/cancel", array('query' => $destination));
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
    '#type' => 'item',
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
  $default_notify = variable_get('user_mail_status_canceled_notify', FALSE);
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
  $description = '';
  if ($can_select_method) {
    $description = t('Select the method to cancel the account above.');
    foreach (element_children($form['user_cancel_method']) as $element) {
      unset($form['user_cancel_method'][$element]['#description']);
    }
  }
  else {
    // The radio button #description is used as description for the confirmation
    // form.
    foreach (element_children($form['user_cancel_method']) as $element) {
      if ($form['user_cancel_method'][$element]['#default_value'] == $form['user_cancel_method'][$element]['#return_value']) {
        $description = $form['user_cancel_method'][$element]['#description'];
      }
      unset($form['user_cancel_method'][$element]['#description']);
    }
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
    $edit = array(
      'user_cancel_method' => $form_state['values']['user_cancel_method'],
      'user_cancel_notify' => $form_state['values']['user_cancel_notify'],
    );
    $account = user_save($account, $edit);
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
      'title' => t('Delete the account and make its content belong to the %anonymous-name user.', array('%anonymous-name' => variable_get('anonymous', t('Anonymous')))),
      'description' => t('Your account will be removed and all account information deleted. All of your content will be assigned to the %anonymous-name user.', array('%anonymous-name' => variable_get('anonymous', t('Anonymous')))),
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
  $default_method = variable_get('user_cancel_method', 'user_cancel_block');
  foreach ($methods as $name => $method) {
    $form[$name] = array(
      '#type' => 'radio',
      '#title' => $method['title'],
      '#description' => (isset($method['description']) ? $method['description'] : NULL),
      '#return_value' => $name,
      '#default_value' => $default_method,
      '#parents' => array('user_cancel_method'),
    );
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
  if (isset($account->data['user_cancel_method']) && !empty($timestamp) && !empty($hashed_pass)) {
    // Validate expiration and hashed password/login.
    if ($timestamp <= $current && $current - $timestamp < $timeout && $account->uid && $timestamp >= $account->login && $hashed_pass == user_pass_rehash($account->pass, $timestamp, $account->login, $account->uid, $account->mail)) {
      $edit = array(
        'user_cancel_notify' => isset($account->data['user_cancel_notify']) ? $account->data['user_cancel_notify'] : variable_get('user_mail_status_canceled_notify', FALSE),
      );
      user_cancel($edit, $account->uid, $account->data['user_cancel_method']);
      // Since user_cancel() is not invoked via Form API, batch processing needs
      // to be invoked manually and should redirect to the front page after
      // completion.
      batch_process('');
    }
    else {
      drupal_set_message(t('You have tried to use an account cancellation link that has expired. Please request a new one using the form below.'), 'error');
      drupal_goto("user/$account->uid/cancel");
    }
  }
  return MENU_ACCESS_DENIED;
}

/**
 * Page callback: Displays the user page.
 *
 * Displays user profile if user is logged in, or login form for anonymous
 * users.
 *
 * @return
 *   A render array for either a user profile or a login form.
 *
 * @see user_view_page()
 * @see user_login()
 */
function user_page() {
  global $user;
  if ($user->uid) {
    menu_set_active_item('user/' . $user->uid);
    return menu_execute_active_handler(NULL, FALSE);
  }
  else {
    return drupal_get_form('user_login');
  }
}
