<?php
// $Id: user.admin.inc,v 1.101 2010/03/07 06:49:10 webchick Exp $

/**
 * @file
 * Admin page callback file for the user module.
 */

function user_admin($callback_arg = '') {
  $op = isset($_POST['op']) ? $_POST['op'] : $callback_arg;

  switch ($op) {
    case t('Create new account'):
    case 'create':
      $build['user_register'] = drupal_get_form('user_register_form');
      break;
    default:
      if (!empty($_POST['accounts']) && isset($_POST['operation']) && ($_POST['operation'] == 'cancel')) {
        $build['user_multiple_cancel_confirm'] = drupal_get_form('user_multiple_cancel_confirm');
      }
      else {
        $build['user_filter_form'] = drupal_get_form('user_filter_form');
        $build['user_admin_account'] = drupal_get_form('user_admin_account');
      }
  }
  return $build;
}

/**
 * Form builder; Return form for user administration filters.
 *
 * @ingroup forms
 * @see user_filter_form_submit()
 */
function user_filter_form() {
  $session = isset($_SESSION['user_overview_filter']) ? $_SESSION['user_overview_filter'] : array();
  $filters = user_filters();

  $i = 0;
  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Show only users where'),
    '#theme' => 'user_filters',
  );
  foreach ($session as $filter) {
    list($type, $value) = $filter;
    if ($type == 'permission') {
      // Merge arrays of module permissions into one.
      // Slice past the first element '[any]' whose value is not an array.
      $options = call_user_func_array('array_merge', array_slice($filters[$type]['options'], 1));
      $value = $options[$value];
    }
    else {
      $value = $filters[$type]['options'][$value];
    }
    $params = array('%property' => $filters[$type]['title'] , '%value' => $value);
    if ($i++) {
      $form['filters']['current'][] = array('#markup' => t('<em>and</em> where <strong>%property</strong> is <strong>%value</strong>', $params));
    }
    else {
      $form['filters']['current'][] = array('#markup' => t('<strong>%property</strong> is <strong>%value</strong>', $params));
    }
  }

  foreach ($filters as $key => $filter) {
    $names[$key] = $filter['title'];
    $form['filters']['status'][$key] = array(
      '#type' => 'select',
      '#options' => $filter['options'],
      '#title' => $filter['title'],
      '#default_value' => '[any]',
    );
  }

  $form['filters']['actions'] = array(
    '#type' => 'container',
    '#id' => 'user-admin-buttons',
    '#attributes' => array('class' => array('form-actions', 'container-inline')),
  );
  $form['filters']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => (count($session) ? t('Refine') : t('Filter')),
  );
  if (count($session)) {
    $form['filters']['actions']['undo'] = array(
      '#type' => 'submit',
      '#value' => t('Undo'),
    );
    $form['filters']['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
  }

  drupal_add_js('misc/form.js');

  return $form;
}

/**
 * Process result from user administration filter form.
 */
function user_filter_form_submit($form, &$form_state) {
  $op = $form_state['values']['op'];
  $filters = user_filters();
  switch ($op) {
    case t('Filter'):
    case t('Refine'):
      // Apply every filter that has a choice selected other than 'any'.
      foreach ($filters as $filter => $options) {
        if (isset($form_state['values'][$filter]) && $form_state['values'][$filter] != '[any]') {
          // Merge an array of arrays into one if necessary.
          $options = ($filter == 'permission') ? form_options_flatten($filters[$filter]['options']) : $filters[$filter]['options'];
          // Only accept valid selections offered on the dropdown, block bad input.
          if (isset($options[$form_state['values'][$filter]])) {
            $_SESSION['user_overview_filter'][] = array($filter, $form_state['values'][$filter]);
          }
        }
      }
      break;
    case t('Undo'):
      array_pop($_SESSION['user_overview_filter']);
      break;
    case t('Reset'):
      $_SESSION['user_overview_filter'] = array();
      break;
    case t('Update'):
      return;
  }

  $form_state['redirect'] = 'admin/people';
  return;
}

/**
 * Form builder; User administration page.
 *
 * @ingroup forms
 * @see user_admin_account_validate()
 * @see user_admin_account_submit()
 */
function user_admin_account() {

  $header = array(
    'username' => array('data' => t('Username'), 'field' => 'u.name'),
    'status' => array('data' => t('Status'), 'field' => 'u.status'),
    'roles' => array('data' => t('Roles')),
    'member_for' => array('data' => t('Member for'), 'field' => 'u.created', 'sort' => 'desc'),
    'access' => array('data' => t('Last access'), 'field' => 'u.access'),
    'operations' => array('data' => t('Operations')),
  );

  $query = db_select('users', 'u');
  $query->condition('u.uid', 0, '<>');
  user_build_filter_query($query);

  $count_query = clone $query;
  $count_query->addExpression('COUNT(u.uid)');

  $query = $query->extend('PagerDefault')->extend('TableSort');
  $query
    ->fields('u', array('uid', 'name', 'status', 'created', 'access'))
    ->limit(50)
    ->orderByHeader($header)
    ->setCountQuery($count_query);
  $result = $query->execute();

  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
  );
  $options = array();
  foreach (module_invoke_all('user_operations') as $operation => $array) {
    $options[$operation] = $array['label'];
  }
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => 'unblock',
  );
  $options = array();
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );

  $destination = drupal_get_destination();

  $status = array(t('blocked'), t('active'));
  $roles = user_roles(TRUE);
  $accounts = array();
  foreach ($result as $account) {
    $users_roles = array();
    $roles_result = db_query('SELECT rid FROM {users_roles} WHERE uid = :uid', array(':uid' => $account->uid));
    foreach ($roles_result as $user_role) {
      $users_roles[] = $roles[$user_role->rid];
    }
    asort($users_roles);

    $options[$account->uid] = array(
      'username' => theme('username', array('account' => $account)),
      'status' =>  $status[$account->status],
      'roles' => theme('item_list', array('items' => $users_roles)),
      'member_for' => format_interval(REQUEST_TIME - $account->created),
      'access' =>  $account->access ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $account->access))) : t('never'),
      'operations' => array('data' => array('#type' => 'link', '#title' => t('edit'), '#href' => "user/$account->uid/edit", '#options' => array('query' => $destination))),
    );
  }

  $form['accounts'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No people available.'),
  );
  $form['pager'] = array('#markup' => theme('pager', array('tags' => NULL)));

  return $form;
}

/**
 * Submit the user administration update form.
 */
function user_admin_account_submit($form, &$form_state) {
  $operations = module_invoke_all('user_operations', $form, $form_state);
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked accounts.
  $accounts = array_filter($form_state['values']['accounts']);
  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($accounts), $operation['callback arguments']);
    }
    else {
      $args = array($accounts);
    }
    call_user_func_array($function, $args);

    drupal_set_message(t('The update has been performed.'));
  }
}

function user_admin_account_validate($form, &$form_state) {
  $form_state['values']['accounts'] = array_filter($form_state['values']['accounts']);
  if (count($form_state['values']['accounts']) == 0) {
    form_set_error('', t('No users selected.'));
  }
}

/**
 * Form builder; Configure user settings for this site.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function user_admin_settings() {
  // Settings for anonymous users.
  $form['anonymous_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Anonymous users'),
  );
  $form['anonymous_settings']['anonymous'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => variable_get('anonymous', t('Anonymous')),
    '#description' => t('The name used to indicate anonymous users.'),
    '#required' => TRUE,
  );

  // Administrative role option.
  $form['admin_role'] = array(
    '#type' => 'fieldset',
    '#title' => t('Administrator role'),
  );

  // Do not allow users to set the anonymous or authenticated user roles as the
  // administrator role.
  $roles = user_roles();
  unset($roles[DRUPAL_ANONYMOUS_RID]);
  unset($roles[DRUPAL_AUTHENTICATED_RID]);
  $roles[0] = t('disabled');

  $form['admin_role']['user_admin_role'] = array(
    '#type' => 'select',
    '#title' => t('Administrator role'),
    '#default_value' => variable_get('user_admin_role', 0),
    '#options' => $roles,
    '#description' => t('This role will be automatically assigned new permissions whenever a module is enabled. Changing this setting will not affect existing permissions.'),
  );

  // User registration settings.
  $form['registration_cancellation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Registration and cancellation'),
  );
  $form['registration_cancellation']['user_register'] = array(
    '#type' => 'radios',
    '#title' => t('Who can register accounts?'),
    '#default_value' => variable_get('user_register', 1),
    '#options' => array(
      t('Administrators only'),
      t('Visitors'),
      t('Visitors, but administrator approval is required'),
    )
  );
  $form['registration_cancellation']['user_email_verification'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require e-mail verification when a visitor creates an account.'),
    '#default_value' => variable_get('user_email_verification', TRUE),
    '#description' => t('New users will be required to validate their e-mail address prior to logging into the site, and will be assigned a system-generated password. With this setting disabled, users will be logged in immediately upon registering, and may select their own passwords during registration.')
  );
  module_load_include('inc', 'user', 'user.pages');
  $form['registration_cancellation']['user_cancel_method'] = array(
    '#type' => 'item',
    '#title' => t('When cancelling a user account'),
    '#description' => t('Users with the %select-cancel-method or %administer-users <a href="@permissions-url">permissions</a> can override this default method.', array('%select-cancel-method' => t('Select method for cancelling account'), '%administer-users' => t('Administer users'), '@permissions-url' => url('admin/people/permissions'))),
  );
  $form['registration_cancellation']['user_cancel_method'] += user_cancel_methods();
  foreach (element_children($form['registration_cancellation']['user_cancel_method']) as $element) {
    // Remove all account cancellation methods that have #access defined, as
    // those cannot be configured as default method.
    if (isset($form['registration_cancellation']['user_cancel_method'][$element]['#access'])) {
      $form['registration_cancellation']['user_cancel_method'][$element]['#access'] = FALSE;
    }
    // Remove the description (only displayed on the confirmation form).
    else {
      unset($form['registration_cancellation']['user_cancel_method'][$element]['#description']);
    }
  }

  // Account settings.
  $form['personalization'] = array(
    '#type' => 'fieldset',
    '#title' => t('Personalization'),
  );
  $form['personalization']['user_signatures'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable signatures.'),
    '#default_value' => variable_get('user_signatures', 0),
  );
  // If picture support is enabled, check whether the picture directory exists.
  if (variable_get('user_pictures', 0)) {
    $picture_path =  variable_get('file_default_scheme', 'public') . '://' . variable_get('user_picture_path', 'pictures');
    if (!file_prepare_directory($picture_path, FILE_CREATE_DIRECTORY)) {
      form_set_error('user_picture_path', t('The directory %directory does not exist or is not writable.', array('%directory' => $picture_path)));
      watchdog('file system', 'The directory %directory does not exist or is not writable.', array('%directory' => $picture_path), WATCHDOG_ERROR);
    }
  }
  $picture_support = variable_get('user_pictures', 0);
  $form['personalization']['user_pictures'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable user pictures.'),
    '#default_value' => $picture_support,
  );
  drupal_add_js(drupal_get_path('module', 'user') . '/user.js');
  $form['personalization']['pictures'] = array(
    '#type' => 'container',
    '#states' => array(
      // Hide the additional picture settings when user pictures are disabled.
      'invisible' => array(
        'input[name="user_pictures"]' => array('checked' => FALSE),
      ),
    ),
  );
  $form['personalization']['pictures']['user_picture_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture directory'),
    '#default_value' => variable_get('user_picture_path', 'pictures'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('Subdirectory in the directory %dir where pictures will be stored.', array('%dir' => file_directory_path() . '/')),
  );
  $form['personalization']['pictures']['user_picture_default'] = array(
    '#type' => 'textfield',
    '#title' => t('Default picture'),
    '#default_value' => variable_get('user_picture_default', ''),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('URL of picture to display for users with no custom picture selected. Leave blank for none.'),
  );
  if (module_exists('image')) {
    $form['personalization']['pictures']['settings']['user_picture_style'] = array(
      '#type' => 'select',
      '#title' => t('Picture display style'),
      '#options' => image_style_options(TRUE),
      '#default_value' => variable_get('user_picture_style', ''),
      '#description' => t('The style selected will be used on display, while the original image is retained. Styles may be configured in the <a href="!url">Image styles</a> administration area.', array('!url' => url('admin/config/media/image-styles'))),
    );
  }
  $form['personalization']['pictures']['user_picture_dimensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture upload dimensions'),
    '#default_value' => variable_get('user_picture_dimensions', '85x85'),
    '#size' => 10,
    '#maxlength' => 10,
    '#field_suffix' => ' ' . t('pixels'),
    '#description' => t('Maximum allowed dimensions for uploaded pictures.'),
  );
  $form['personalization']['pictures']['user_picture_file_size'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture upload file size'),
    '#default_value' => variable_get('user_picture_file_size', '30'),
    '#size' => 10,
    '#maxlength' => 10,
    '#field_suffix' => ' ' . t('KB'),
    '#description' => t('Maximum allowed file size for uploaded pictures.'),
  );
  $form['personalization']['pictures']['user_picture_guidelines'] = array(
    '#type' => 'textarea',
    '#title' => t('Picture guidelines'),
    '#default_value' => variable_get('user_picture_guidelines', ''),
    '#description' => t("This text is displayed at the picture upload form in addition to the default guidelines. It's useful for helping or instructing your users."),
  );

  $form['email_title'] = array(
    '#type' => 'item',
    '#title' => t('E-mails'),
  );
  $form['email'] = array(
    '#type' => 'vertical_tabs',
  );
  // These email tokens are shared for all settings, so just define
  // the list once to help ensure they stay in sync.
  $email_token_help = t('You can use the following tokens in your e-mail message text: !site-name-token, !site-url-token, !user-name-token, !user-mail-token, !site-login-url-token, !user-edit-url-token, !user-one-time-login-url-token, !user-cancel-url-token', array('!site-name-token' => '[site:name]', '!site-url-token' => '[site:url]', '!user-name-token' => '[user:name]', '!user-mail-token' => '[user:mail]', '!site-login-url-token' => '[site:login-url]', '!user-edit-url-token' => '[user:edit-url]', '!user-one-time-login-url-token' => '[user:one-time-login-url]', '!user-cancel-url-token' => '[user:cancel-url]'));

  $form['email_admin_created'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome (new user created by administrator)'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 0),
    '#description' => t('Edit the welcome e-mail messages sent to new member accounts created by an administrator.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_admin_created']['user_mail_register_admin_created_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_admin_created_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_admin_created']['user_mail_register_admin_created_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_admin_created_body', NULL, array(), FALSE),
    '#rows' => 15,
  );

  $form['email_pending_approval'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome (awaiting approval)'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 2),
    '#description' => t('Edit the welcome e-mail messages sent to new members upon registering, when administrative approval is required.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_pending_approval']['user_mail_register_pending_approval_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_pending_approval_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_pending_approval']['user_mail_register_pending_approval_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_pending_approval_body', NULL, array(), FALSE),
    '#rows' => 8,
  );

  $form['email_no_approval_required'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome (no approval required)'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 1),
    '#description' => t('Edit the welcome e-mail messages sent to new members upon registering, when no administrator approval is required.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_no_approval_required']['user_mail_register_no_approval_required_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_no_approval_required_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_no_approval_required']['user_mail_register_no_approval_required_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_no_approval_required_body', NULL, array(), FALSE),
    '#rows' => 15,
  );

  $form['email_password_reset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Password recovery'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Edit the e-mail messages sent to users who request a new password.') . ' ' . $email_token_help,
    '#group' => 'email',
    '#weight' => 10,
  );
  $form['email_password_reset']['user_mail_password_reset_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('password_reset_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_password_reset']['user_mail_password_reset_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('password_reset_body', NULL, array(), FALSE),
    '#rows' => 12,
  );

  $form['email_activated'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account activation'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and edit e-mail messages sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required).') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_activated']['user_mail_status_activated_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is activated.'),
    '#default_value' => variable_get('user_mail_status_activated_notify', TRUE),
  );
  $form['email_activated']['settings'] = array(
    '#type' => 'container',
    '#states' => array(
      // Hide the additional settings when this email is disabled.
      'invisible' => array(
        'input[name="user_mail_status_activated_notify"]' => array('checked' => FALSE),
      ),
    ),
  );
  $form['email_activated']['settings']['user_mail_status_activated_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_activated_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_activated']['settings']['user_mail_status_activated_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_activated_body', NULL, array(), FALSE),
    '#rows' => 15,
  );

  $form['email_blocked'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account blocked'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and edit e-mail messages sent to users when their accounts are blocked.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_blocked']['user_mail_status_blocked_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is blocked.'),
    '#default_value' => variable_get('user_mail_status_blocked_notify', FALSE),
  );
  $form['email_blocked']['settings'] = array(
    '#type' => 'container',
    '#states' => array(
      // Hide the additional settings when the blocked email is disabled.
      'invisible' => array(
        'input[name="user_mail_status_blocked_notify"]' => array('checked' => FALSE),
      ),
    ),
  );
  $form['email_blocked']['settings']['user_mail_status_blocked_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_blocked_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_blocked']['settings']['user_mail_status_blocked_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_blocked_body', NULL, array(), FALSE),
    '#rows' => 3,
  );

  $form['email_cancel_confirm'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account cancellation confirmation'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Edit the e-mail messages sent to users when they attempt to cancel their accounts.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_cancel_confirm']['user_mail_cancel_confirm_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('cancel_confirm_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_cancel_confirm']['user_mail_cancel_confirm_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('cancel_confirm_body', NULL, array(), FALSE),
    '#rows' => 3,
  );

  $form['email_canceled'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account canceled'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and edit e-mail messages sent to users when their accounts are canceled.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_canceled']['user_mail_status_canceled_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is canceled.'),
    '#default_value' => variable_get('user_mail_status_canceled_notify', FALSE),
  );
  $form['email_canceled']['settings'] = array(
    '#type' => 'container',
    '#states' => array(
      // Hide the settings when the cancel notify checkbox is disabled.
      'invisible' => array(
        'input[name="user_mail_status_canceled_notify"]' => array('checked' => FALSE),
      ),
    ),
  );
  $form['email_canceled']['settings']['user_mail_status_canceled_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_canceled_subject', NULL, array(), FALSE),
    '#maxlength' => 180,
  );
  $form['email_canceled']['settings']['user_mail_status_canceled_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_canceled_body', NULL, array(), FALSE),
    '#rows' => 3,
  );

  return system_settings_form($form, FALSE);
}

/**
 * Menu callback: administer permissions.
 *
 * @ingroup forms
 * @see user_admin_permissions_submit()
 * @see theme_user_admin_permissions()
 */
function user_admin_permissions($form, $form_state, $rid = NULL) {

  // Retrieve role names for columns.
  $role_names = user_roles();
  if (is_numeric($rid)) {
    $role_names = array($rid => $role_names[$rid]);
  }
  // Fetch permissions for all roles or the one selected role.
  $role_permissions = user_role_permissions($role_names);

  // Store $role_names for use when saving the data.
  $form['role_names'] = array(
    '#type' => 'value',
    '#value' => $role_names,
  );
  // Render role/permission overview:
  $options = array();
  $module_info = system_get_info('module');
  $hide_descriptions = !system_admin_compact_mode();

  // Get a list of all the modules implementing a hook_permission() and sort by
  // display name.
  $modules = array();
  foreach (module_implements('permission') as $module) {
    $modules[$module_info[$module]['name']] = $module;
  }
  ksort($modules);

  foreach ($modules as $display_name => $module) {
    if ($permissions = module_invoke($module, 'permission')) {
      $form['permission'][] = array(
        '#markup' => $module_info[$module]['name'],
        '#id' => $module,
      );
      foreach ($permissions as $perm => $perm_item) {
        $options[$perm] = '';
        $form['permission'][$perm] = array(
          '#type' => 'item',
          '#markup' => $perm_item['title'],
          '#description' => $hide_descriptions && isset($perm_item['description']) ? $perm_item['description'] : NULL,
        );
        foreach ($role_names as $rid => $name) {
          // Builds arrays for checked boxes for each role
          if (isset($role_permissions[$rid][$perm])) {
            $status[$rid][] = $perm;
          }
        }
      }
    }
  }

  // Have to build checkboxes here after checkbox arrays are built
  foreach ($role_names as $rid => $name) {
    $form['checkboxes'][$rid] = array('#type' => 'checkboxes', '#options' => $options, '#default_value' => isset($status[$rid]) ? $status[$rid] : array());
    $form['role_names'][$rid] = array('#markup' => $name, '#tree' => TRUE);
  }

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save permissions'));

  $form['#attached']['js'][] = drupal_get_path('module', 'user') . '/user.permissions.js';

  return $form;
}

/**
 * Save permissions selected on the administer permissions page.
 *
 * @see user_admin_permissions()
 */
function user_admin_permissions_submit($form, &$form_state) {
  foreach ($form_state['values']['role_names'] as $rid => $name) {
    user_role_change_permissions($rid, $form_state['values'][$rid]);
  }

  drupal_set_message(t('The changes have been saved.'));

  // Clear the cached pages and blocks.
  cache_clear_all();
}

/**
 * Theme the administer permissions page.
 *
 * @ingroup themeable
 */
function theme_user_admin_permissions($variables) {
  $form = $variables['form'];

  $roles = user_roles();
  foreach (element_children($form['permission']) as $key) {
    $row = array();
    // Module name
    if (is_numeric($key)) {
      $row[] = array('data' => drupal_render($form['permission'][$key]), 'class' => array('module'), 'id' => 'module-' . $form['permission'][$key]['#id'], 'colspan' => count($form['role_names']['#value']) + 1);
    }
    else {
      // Permission row.
      $row[] = array(
        'data' => drupal_render($form['permission'][$key]),
        'class' => array('permission'),
      );
      foreach (element_children($form['checkboxes']) as $rid) {
        $row[] = array('data' => drupal_render($form['checkboxes'][$rid][$key]), 'class' => array('checkbox'), 'title' => $roles[$rid] . ' : ' . t($key));
      }
    }
    $rows[] = $row;
  }
  $header[] = (t('Permission'));
  foreach (element_children($form['role_names']) as $rid) {
    $header[] = array('data' => drupal_render($form['role_names'][$rid]), 'class' => array('checkbox'));
  }
  $output = theme('system_compact_link');
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'permissions')));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Menu callback: administer roles.
 *
 * @ingroup forms
 * @see user_admin_role_validate()
 * @see user_admin_role_submit()
 * @see theme_user_admin_new_role()
 */
function user_admin_role() {
  $rid = arg(5);
  if ($rid) {
    if ($rid == DRUPAL_ANONYMOUS_RID || $rid == DRUPAL_AUTHENTICATED_RID) {
      drupal_goto('admin/people/permissions/roles');
    }
    // Display the edit role form.
    $role = db_query('SELECT * FROM {role} WHERE rid = :rid', array(':rid' => $rid))->fetchObject();
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Role name'),
      '#default_value' => $role->name,
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => t('The name for this role. Example: "moderator", "editorial board", "site architect".'),
    );
    $form['rid'] = array(
      '#type' => 'value',
      '#value' => $rid,
    );
    $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save role'),
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete role'),
    );
  }
  else {
    $form['name'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#maxlength' => 64,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add role'),
    );
    $form['#submit'][] = 'user_admin_role_submit';
    $form['#validate'][] = 'user_admin_role_validate';
  }
  return $form;
}

function user_admin_role_validate($form, &$form_state) {
  if ($form_state['values']['name']) {
    if ($form_state['values']['op'] == t('Save role')) {
      $role = user_role_load($form_state['values']['name']);
      if ($role && $role->rid != $form_state['values']['rid']) {
        form_set_error('name', t('The role name %name already exists. Choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
    elseif ($form_state['values']['op'] == t('Add role')) {
      if (user_role_load($form_state['values']['name'])) {
        form_set_error('name', t('The role name %name already exists. Choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
  }
  else {
    form_set_error('name', t('You must specify a valid role name.'));
  }
}

function user_admin_role_submit($form, &$form_state) {
  $role = (object)$form_state['values'];
  if ($form_state['values']['op'] == t('Save role')) {
    user_role_save($role);
    drupal_set_message(t('The role has been renamed.'));
  }
  elseif ($form_state['values']['op'] == t('Delete role')) {
    user_role_delete($form_state['values']['rid']);
    drupal_set_message(t('The role has been deleted.'));
  }
  elseif ($form_state['values']['op'] == t('Add role')) {
    user_role_save($role);
    drupal_set_message(t('The role has been added.'));
  }
  $form_state['redirect'] = 'admin/people/permissions/roles';
  return;
}

/**
 * Theme the new-role form.
 *
 * @ingroup themeable
 */
function theme_user_admin_new_role($variables) {
  $form = $variables['form'];

  $header = array(t('Name'), array('data' => t('Operations'), 'colspan' => 2));
  foreach (user_roles() as $rid => $name) {
    $edit_permissions = l(t('edit permissions'), 'admin/people/permissions/' . $rid);
    if (in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
      $rows[] = array(t('!name %locked', array('!name' => $name, '%locked' => t('(locked)'))), '', $edit_permissions);
    }
    else {
      $rows[] = array($name, l(t('edit role'), 'admin/people/permissions/roles/edit/' . $rid), $edit_permissions);
    }
  }
  $rows[] = array(array('data' => drupal_render($form['name']) . drupal_render($form['submit']), 'colspan' => 3, 'class' => 'edit-name'));

  $output = drupal_render_children($form);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));

  return $output;
}

/**
 * Theme user administration filter selector.
 *
 * @ingroup themeable
 */
function theme_user_filters($variables) {
  $form = $variables['form'];

  $output = '<ul class="clearfix">';
  if (!empty($form['current'])) {
    foreach (element_children($form['current']) as $key) {
      $output .= '<li>' . drupal_render($form['current'][$key]) . '</li>';
    }
  }
  $output .= '</ul>';

  $output .= '<div class="clearfix">';

  $output .= '<dl class="multiselect">' . (!empty($form['current']) ? '<dt><em>' . t('and') . '</em> ' . t('where') . '</dt>' : '');

  $output .= '<dd>';

  foreach (element_children($form['status']) as $key) {
    $output .= drupal_render($form['status'][$key]);
  }
  $output .= '</dd>';

  $output .= '</dl>';
  $output .= drupal_render($form['actions']);

  $output .= '</div>';

  return $output;
}
