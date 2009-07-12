<?php
// $Id$

/**
 * @file
 * Admin page callback file for the user module.
 */

function user_admin($callback_arg = '') {
  $op = isset($_POST['op']) ? $_POST['op'] : $callback_arg;

  switch ($op) {
    case t('Create new account'):
    case 'create':
      $build['user_register'] = drupal_get_form('user_register');
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
    // Merge an array of arrays into one if necessary.
    $options = $type == 'permission' ? call_user_func_array('array_merge', $filters[$type]['options']) : $filters[$type]['options'];
    $params = array('%property' => $filters[$type]['title'] , '%value' => $options[$value]);
    if ($i++ > 0) {
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
    );
  }

  $form['filters']['filter'] = array(
    '#type' => 'radios',
    '#options' => $names,
  );
  $form['filters']['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => (count($session) ? t('Refine') : t('Filter')),
  );
  if (count($session)) {
    $form['filters']['buttons']['undo'] = array(
      '#type' => 'submit',
      '#value' => t('Undo'),
    );
    $form['filters']['buttons']['reset'] = array(
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
    case t('Filter'): case t('Refine'):
      if (isset($form_state['values']['filter'])) {
        $filter = $form_state['values']['filter'];
        // Merge an array of arrays into one if necessary.
        $options = $filter == 'permission' ? call_user_func_array('array_merge', $filters[$filter]['options']) : $filters[$filter]['options'];
        if (isset($options[$form_state['values'][$filter]])) {
          $_SESSION['user_overview_filter'][] = array($filter, $form_state['values'][$filter]);
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

  $form_state['redirect'] = 'admin/user/user';
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
    array(),
    array('data' => t('Username'), 'field' => 'u.name'),
    array('data' => t('Status'), 'field' => 'u.status'),
    t('Roles'),
    array('data' => t('Member for'), 'field' => 'u.created', 'sort' => 'desc'),
    array('data' => t('Last access'), 'field' => 'u.access'),
    t('Operations')
  );

  $query = db_select('users', 'u');
  $query->leftJoin('users_roles', 'ur', 'u.uid = ur.uid');
  $query->condition('u.uid', 0, '<>');
  user_build_filter_query($query);

  $count_query = clone $query;
  $count_query->addExpression('COUNT(DISTINCT u.uid)');

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
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
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
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );

  $destination = drupal_get_destination();

  $status = array(t('blocked'), t('active'));
  $roles = user_roles(TRUE);
  $accounts = array();
  foreach ($result as $account) {
    $accounts[$account->uid] = '';
    $form['name'][$account->uid] = array('#markup' => theme('username', $account));
    $form['status'][$account->uid] =  array('#markup' => $status[$account->status]);
    $users_roles = array();
    $roles_result = db_query('SELECT rid FROM {users_roles} WHERE uid = :uid', array(':uid' => $account->uid));
    foreach ($roles_result as $user_role) {
      $users_roles[] = $roles[$user_role->rid];
    }
    asort($users_roles);
    $form['roles'][$account->uid][0] = array('#markup' => theme('item_list', $users_roles));
    $form['member_for'][$account->uid] = array('#markup' => format_interval(REQUEST_TIME - $account->created));
    $form['last_access'][$account->uid] =  array('#markup' => $account->access ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $account->access))) : t('never'));
    $form['operations'][$account->uid] = array('#markup' => l(t('edit'), "user/$account->uid/edit", array('query' => $destination)));
  }
  $form['accounts'] = array(
    '#type' => 'checkboxes',
    '#options' => $accounts
  );
  $form['pager'] = array('#markup' => theme('pager', NULL));

  return $form;
}

/**
 * Submit the user administration update form.
 */
function user_admin_account_submit($form, &$form_state) {
  $operations = module_invoke_all('user_operations', $form_state);
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
    '#description' => t('New users will be required to validate their e-mail address prior to logging into the site, and will be assigned a system-generated password. If disabled, users will be logged in immediately upon registering, and may select their own passwords during registration.')
  );
  module_load_include('inc', 'user', 'user.pages');
  $form['registration_cancellation']['user_cancel_method'] = array(
    '#type' => 'item',
    '#title' => t('When cancelling a user account'),
    '#description' => t('Users with the %select-cancel-method or %administer-users <a href="@permissions-url">permissions</a> can override this default method.', array('%select-cancel-method' => t('Select method for cancelling account'), '%administer-users' => t('Administer users'), '@permissions-url' => url('admin/user/permissions'))),
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
    $picture_path = file_create_path(variable_get('user_picture_path', 'pictures'));
    file_check_directory($picture_path, FILE_CREATE_DIRECTORY, 'user_picture_path');
  }
  $picture_support = variable_get('user_pictures', 0);
  $form['personalization']['user_pictures'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable user pictures.'),
    '#default_value' => $picture_support,
  );
  drupal_add_js(drupal_get_path('module', 'user') . '/user.js');
  // If JS is enabled, and the checkbox defaults to off, hide all the settings
  // on page load via CSS using the js-hide class so there's no flicker.
  $css_class = 'user-admin-picture-settings';
  if (!$picture_support) {
    $css_class .= ' js-hide';
  }
  $form['personalization']['pictures'] = array(
    '#prefix' => '<div class="' . $css_class . '">',
    '#suffix' => '</div>',
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
      '#title' => t('Picture style'),
      '#options' => image_style_options(TRUE),
      '#default_value' => variable_get('user_picture_style', ''),
    );
  }
  $form['personalization']['pictures']['user_picture_dimensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture maximum dimensions'),
    '#default_value' => variable_get('user_picture_dimensions', '85x85'),
    '#size' => 15,
    '#maxlength' => 10,
    '#description' => t('Maximum dimensions for pictures, in pixels.'),
  );
  $form['personalization']['pictures']['user_picture_file_size'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture maximum file size'),
    '#default_value' => variable_get('user_picture_file_size', '30'),
    '#size' => 15,
    '#maxlength' => 10,
    '#description' => t('Maximum file size for pictures, in kB.'),
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
  $email_token_help = t('Available variables are:') . ' !username, !site, !password, !uri, !uri_brief, !mailto, !date, !login_uri, !edit_uri, !login_url, !cancel_url.';

  $form['email_admin_created'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome (new user created by administrator)'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 0),
    '#description' => t('Customize welcome e-mail messages sent to new member accounts created by an administrator.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_admin_created']['user_mail_register_admin_created_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_admin_created_subject'),
    '#maxlength' => 180,
  );
  $form['email_admin_created']['user_mail_register_admin_created_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_admin_created_body'),
    '#rows' => 15,
  );

  $form['email_pending_approval'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome (awaiting approval)'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 2),
    '#description' => t('Customize welcome e-mail messages sent to new members upon registering, when administrative approval is required.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_pending_approval']['user_mail_register_pending_approval_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_pending_approval_subject'),
    '#maxlength' => 180,
  );
  $form['email_pending_approval']['user_mail_register_pending_approval_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_pending_approval_body'),
    '#rows' => 8,
  );

  $form['email_no_approval_required'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome (no approval required)'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 1),
    '#description' => t('Customize welcome e-mail messages sent to new members upon registering, when no administrator approval is required.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_no_approval_required']['user_mail_register_no_approval_required_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_no_approval_required_subject'),
    '#maxlength' => 180,
  );
  $form['email_no_approval_required']['user_mail_register_no_approval_required_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_no_approval_required_body'),
    '#rows' => 15,
  );

  $form['email_password_reset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Password recovery'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Customize e-mail messages sent to users who request a new password.') . ' ' . $email_token_help,
    '#group' => 'email',
    '#weight' => 10,
  );
  $form['email_password_reset']['user_mail_password_reset_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('password_reset_subject'),
    '#maxlength' => 180,
  );
  $form['email_password_reset']['user_mail_password_reset_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('password_reset_body'),
    '#rows' => 12,
  );

  $form['email_activated'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account activation'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and customize e-mail messages sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required).') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_activated']['user_mail_status_activated_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is activated.'),
    '#default_value' => variable_get('user_mail_status_activated_notify', TRUE),
  );
  $form['email_activated']['user_mail_status_activated_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_activated_subject'),
    '#maxlength' => 180,
  );
  $form['email_activated']['user_mail_status_activated_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_activated_body'),
    '#rows' => 15,
  );

  $form['email_blocked'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account blocked'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and customize e-mail messages sent to users when their accounts are blocked.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_blocked']['user_mail_status_blocked_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is blocked.'),
    '#default_value' => variable_get('user_mail_status_blocked_notify', FALSE),
  );
  $form['email_blocked']['user_mail_status_blocked_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_blocked_subject'),
    '#maxlength' => 180,
  );
  $form['email_blocked']['user_mail_status_blocked_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_blocked_body'),
    '#rows' => 3,
  );

  $form['email_cancel_confirm'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account cancellation confirmation'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Customize e-mail messages sent to users when they attempt to cancel their accounts.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_cancel_confirm']['user_mail_cancel_confirm_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('cancel_confirm_subject'),
    '#maxlength' => 180,
  );
  $form['email_cancel_confirm']['user_mail_cancel_confirm_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('cancel_confirm_body'),
    '#rows' => 3,
  );

  $form['email_canceled'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account canceled'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and customize e-mail messages sent to users when their accounts are canceled.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_canceled']['user_mail_status_canceled_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is canceled.'),
    '#default_value' => variable_get('user_mail_status_canceled_notify', FALSE),
  );
  $form['email_canceled']['user_mail_status_canceled_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_canceled_subject'),
    '#maxlength' => 180,
  );
  $form['email_canceled']['user_mail_status_canceled_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_canceled_body'),
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
function user_admin_permissions($form_state, $rid = NULL) {

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
  $hide_descriptions = !system_admin_compact_mode();
  foreach (module_implements('permission') as $module) {
    if ($permissions = module_invoke($module, 'permission')) {
      $info = drupal_parse_info_file(drupal_get_path('module', $module) . "/$module.info");
      $form['permission'][] = array(
        '#markup' => $info['name'],
        '#id' => $module,
        );
      foreach ($permissions as $perm => $perm_item) {
        $options[$perm] = '';
        $form['permission'][$perm] = array(
          '#type' => 'item',
          '#markup' => $perm_item['title'],
          '#description' => $hide_descriptions ? $perm_item['description'] : NULL,
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
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save permissions'));

  $form['#attached_js'] = array(drupal_get_path('module', 'user') . '/user.permissions.js');

  return $form;
}

/**
 * Save permissions selected on the administer permissions page.
 *
 * @see user_admin_permissions()
 */
function user_admin_permissions_submit($form, &$form_state) {
  foreach ($form_state['values']['role_names'] as $rid => $name) {
    $checked = array_filter($form_state['values'][$rid]);
    // Delete existing permissions for the role. This handles "unchecking" checkboxes.
    db_delete('role_permission')
      ->condition('rid', $rid)
      ->execute();
    $query = db_insert('role_permission')->fields(array('rid', 'permission'));
    foreach ($checked as $permission) {
      $query->values(array(
        'rid' => $rid,
        'permission' => $permission,
      ));
    }
    $query->execute();
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
function theme_user_admin_permissions($form) {
  $roles = user_roles();
  foreach (element_children($form['permission']) as $key) {
    $row = array();
    // Module name
    if (is_numeric($key)) {
      $row[] = array('data' => drupal_render($form['permission'][$key]), 'class' => 'module', 'id' => 'module-' . $form['permission'][$key]['#id'], 'colspan' => count($form['role_names']['#value']) + 1);
    }
    else {
      // Permission row.
      $row[] = array(
        'data' => drupal_render($form['permission'][$key]),
        'class' => 'permission',
      );
      foreach (element_children($form['checkboxes']) as $rid) {
        $row[] = array('data' => drupal_render($form['checkboxes'][$rid][$key]), 'class' => 'checkbox', 'title' => $roles[$rid] . ' : ' . t($key));
      }
    }
    $rows[] = $row;
  }
  $header[] = (t('Permission'));
  foreach (element_children($form['role_names']) as $rid) {
    $header[] = array('data' => drupal_render($form['role_names'][$rid]), 'class' => 'checkbox');
  }
  $output = theme('system_compact_link');
  $output .= theme('table', $header, $rows, array('id' => 'permissions'));
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
  $rid = arg(4);
  if ($rid) {
    if ($rid == DRUPAL_ANONYMOUS_RID || $rid == DRUPAL_AUTHENTICATED_RID) {
      drupal_goto('admin/user/roles');
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
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save role'),
    );
    $form['delete'] = array(
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
      $existing_role = (bool) db_query_range("SELECT 1 FROM {role} WHERE name = :name AND rid <> :rid", array(':name' => $form_state['values']['name'], ':rid' => $form_state['values']['rid']), 0, 1)->fetchField();
      if ($existing_role) {
        form_set_error('name', t('The role name %name already exists. Please choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
    elseif ($form_state['values']['op'] == t('Add role')) {
      if ((bool) db_query_range('SELECT 1 FROM {role} WHERE name = :name', array(':name' => $form_state['values']['name']), 0, 1)->fetchField()) {
        form_set_error('name', t('The role name %name already exists. Please choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
  }
  else {
    form_set_error('name', t('You must specify a valid role name.'));
  }
}

function user_admin_role_submit($form, &$form_state) {
  if ($form_state['values']['op'] == t('Save role')) {
    db_update('role')
      ->fields(array('name' => $form_state['values']['name']))
      ->condition('rid', $form_state['values']['rid'])
      ->execute();
    drupal_set_message(t('The role has been renamed.'));
  }
  elseif ($form_state['values']['op'] == t('Delete role')) {
    db_delete('role')
      ->condition('rid', $form_state['values']['rid'])
      ->execute();
    db_delete('role_permission')
      ->condition('rid', $form_state['values']['rid'])
      ->execute();
    // Update the users who have this role set:
    db_delete('users_roles')
      ->condition('rid', $form_state['values']['rid'])
      ->execute();

    drupal_set_message(t('The role has been deleted.'));
  }
  elseif ($form_state['values']['op'] == t('Add role')) {
    db_insert('role')
      ->fields(array('name' => $form_state['values']['name']))
      ->execute();
    drupal_set_message(t('The role has been added.'));
  }
  $form_state['redirect'] = 'admin/user/roles';
  return;
}

/**
 * Theme user administration overview.
 *
 * @ingroup themeable
 */
function theme_user_admin_account($form) {
  // Overview table:
  $header = array(
    theme('table_select_header_cell'),
    array('data' => t('Username'), 'field' => 'u.name'),
    array('data' => t('Status'), 'field' => 'u.status'),
    t('Roles'),
    array('data' => t('Member for'), 'field' => 'u.created', 'sort' => 'desc'),
    array('data' => t('Last access'), 'field' => 'u.access'),
    t('Operations')
  );

  $output = drupal_render($form['options']);
  if (!empty($form['name'])) {
    foreach (element_children($form['name']) as $key) {
      $rows[] = array(
        drupal_render($form['accounts'][$key]),
        drupal_render($form['name'][$key]),
        drupal_render($form['status'][$key]),
        drupal_render($form['roles'][$key]),
        drupal_render($form['member_for'][$key]),
        drupal_render($form['last_access'][$key]),
        drupal_render($form['operations'][$key]),
      );
    }
  }
  else {
    $rows[] = array(array('data' => t('No users available.'), 'colspan' => '7'));
  }

  $output .= theme('table', $header, $rows);
  if ($form['pager']['#markup']) {
    $output .= drupal_render($form['pager']);
  }

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Theme the new-role form.
 *
 * @ingroup themeable
 */
function theme_user_admin_new_role($form) {
  $header = array(t('Name'), array('data' => t('Operations'), 'colspan' => 2));
  foreach (user_roles() as $rid => $name) {
    $edit_permissions = l(t('edit permissions'), 'admin/user/permissions/' . $rid);
    if (!in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
      $rows[] = array($name, l(t('edit role'), 'admin/user/roles/edit/' . $rid), $edit_permissions);
    }
    else {
      $rows[] = array($name, t('locked'), $edit_permissions);
    }
  }
  $rows[] = array(drupal_render($form['name']), array('data' => drupal_render($form['submit']), 'colspan' => 2));

  $output = drupal_render_children($form);
  $output .= theme('table', $header, $rows);

  return $output;
}

/**
 * Theme user administration filter form.
 *
 * @ingroup themeable
 */
function theme_user_filter_form($form) {
  $output = '<div id="user-admin-filter">';
  $output .= drupal_render($form['filters']);
  $output .= '</div>';
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Theme user administration filter selector.
 *
 * @ingroup themeable
 */
function theme_user_filters($form) {
  $output = '<ul class="clearfix">';
  if (!empty($form['current'])) {
    foreach (element_children($form['current']) as $key) {
      $output .= '<li>' . drupal_render($form['current'][$key]) . '</li>';
    }
  }

  $output .= '<li><dl class="multiselect">' . (!empty($form['current']) ? '<dt><em>' . t('and') . '</em> ' . t('where') . '</dt>' : '') . '<dd class="a">';
  foreach (element_children($form['filter']) as $key) {
    $output .= drupal_render($form['filter'][$key]);
  }
  $output .= '</dd>';

  $output .= '<dt>' . t('is') . '</dt><dd class="b">';

  foreach (element_children($form['status']) as $key) {
    $output .= drupal_render($form['status'][$key]);
  }
  $output .= '</dd>';

  $output .= '</dl>';
  $output .= '<div class="container-inline" id="user-admin-buttons">' . drupal_render($form['buttons']) . '</div>';
  $output .= '</li></ul>';

  return $output;
}

/**
 * Implementation of hook_modules_installed().
 */
function user_modules_installed($modules) {
  // Assign all available permissions to the administrator role.
  $rid = variable_get('user_admin_role', 0);
  if ($rid) {
    foreach ($modules as $module) {
      if ($permissions = module_invoke($module, 'permission')) {
        foreach (array_keys($permissions) as $permission) {
          db_insert('role_permission')
            ->fields(array(
              'rid' => $rid,
              'permission' => $permission,
            ))->execute();
        }
      }
    }
  }
}

/**
 * Implement hook_modules_uninstalled().
 */
function user_modules_uninstalled($modules) {
  $permissions = array();
  foreach ($modules as $module) {
    if (drupal_function_exists($module . '_permission')) {
      $permissions = array_merge($permissions, array_keys(module_invoke($module, 'permission')));
    }
  }
  if (!empty($permissions)) {
    db_delete('role_permission')
      ->condition('permission', $permissions, 'IN')
      ->execute();
  }
}
