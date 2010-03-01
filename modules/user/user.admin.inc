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
      $output = drupal_get_form('user_register');
      break;
    default:
      if (!empty($_POST['accounts']) && isset($_POST['operation']) && ($_POST['operation'] == 'delete')) {
        $output = drupal_get_form('user_multiple_delete_confirm');
      }
      else {
        $output = drupal_get_form('user_filter_form');
        $output .= drupal_get_form('user_admin_account');
      }
  }
  return $output;
}

/**
 * Form builder; Return form for user administration filters.
 *
 * @ingroup forms
 * @see user_filter_form_submit()
 */
function user_filter_form() {
  $session = &$_SESSION['user_overview_filter'];
  $session = is_array($session) ? $session : array();
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
      $form['filters']['current'][] = array('#value' => t('<em>and</em> where <strong>%property</strong> is <strong>%value</strong>', $params));
    }
    else {
      $form['filters']['current'][] = array('#value' => t('<strong>%property</strong> is <strong>%value</strong>', $params));
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

  drupal_add_js('misc/form.js', 'core');

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
  $filter = user_build_filter_query();

  $header = array(
    array(),
    array('data' => t('Username'), 'field' => 'u.name'),
    array('data' => t('Status'), 'field' => 'u.status'),
    t('Roles'),
    array('data' => t('Member for'), 'field' => 'u.created', 'sort' => 'desc'),
    array('data' => t('Last access'), 'field' => 'u.access'),
    t('Operations')
  );

  if ($filter['join'] != "") {
    $sql = 'SELECT DISTINCT u.uid, u.name, u.status, u.created, u.access FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid '. $filter['join'] .' WHERE u.uid != 0 '. $filter['where'];
    $query_count = 'SELECT COUNT(DISTINCT u.uid) FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid '. $filter['join'] .' WHERE u.uid != 0 '. $filter['where'];
  }
  else {
    $sql = 'SELECT u.uid, u.name, u.status, u.created, u.access FROM {users} u WHERE u.uid != 0 '. $filter['where'];
    $query_count = 'SELECT COUNT(u.uid) FROM {users} u WHERE u.uid != 0 '. $filter['where'];
  }

  
  $sql .= tablesort_sql($header);

  $result = pager_query($sql, 50, 0, $query_count, $filter['args']);

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
  while ($account = db_fetch_object($result)) {
    $accounts[$account->uid] = '';
    $form['name'][$account->uid] = array('#value' => theme('username', $account));
    $form['status'][$account->uid] =  array('#value' => $status[$account->status]);
    $users_roles = array();
    $roles_result = db_query('SELECT rid FROM {users_roles} WHERE uid = %d', $account->uid);
    while ($user_role = db_fetch_object($roles_result)) {
      $users_roles[] = $roles[$user_role->rid];
    }
    asort($users_roles);
    $form['roles'][$account->uid][0] = array('#value' => theme('item_list', $users_roles));
    $form['member_for'][$account->uid] = array('#value' => format_interval(time() - $account->created));
    $form['last_access'][$account->uid] =  array('#value' => $account->access ? t('@time ago', array('@time' => format_interval(time() - $account->access))) : t('never'));
    $form['operations'][$account->uid] = array('#value' => l(t('edit'), "user/$account->uid/edit", array('query' => $destination)));
  }
  $form['accounts'] = array(
    '#type' => 'checkboxes',
    '#options' => $accounts
  );
  $form['pager'] = array('#value' => theme('pager', NULL, 50, 0));

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
  // User registration settings.
  $form['registration'] = array('#type' => 'fieldset', '#title' => t('User registration settings'));
  $form['registration']['user_register'] = array('#type' => 'radios', '#title' => t('Public registrations'), '#default_value' => variable_get('user_register', 1), '#options' => array(t('Only site administrators can create new user accounts.'), t('Visitors can create accounts and no administrator approval is required.'), t('Visitors can create accounts but administrator approval is required.')));
  $form['registration']['user_email_verification'] = array('#type' => 'checkbox', '#title' => t('Require e-mail verification when a visitor creates an account'), '#default_value' => variable_get('user_email_verification', TRUE), '#description' => t('If this box is checked, new users will be required to validate their e-mail address prior to logging into the site, and will be assigned a system-generated password. With it unchecked, users will be logged in immediately upon registering, and may select their own passwords during registration.'));
  $form['registration']['user_registration_help'] = array('#type' => 'textarea', '#title' => t('User registration guidelines'), '#default_value' => variable_get('user_registration_help', ''), '#description' => t('This text is displayed at the top of the user registration form and is useful for helping or instructing your users.'));

  // User e-mail settings.
  $form['email'] = array(
    '#type' => 'fieldset',
    '#title' => t('User e-mail settings'),
    '#description' => t('Drupal sends emails whenever new users register on your site, and optionally, may also notify users after other account actions. Using a simple set of content templates, notification e-mails can be customized to fit the specific needs of your site.'),
  );
  // These email tokens are shared for all settings, so just define
  // the list once to help ensure they stay in sync.
  $email_token_help = t('Available variables are:') .' !username, !site, !password, !uri, !uri_brief, !mailto, !date, !login_uri, !edit_uri, !login_url.';

  $form['email']['admin_created'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome, new user created by administrator'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 0),
    '#description' => t('Customize welcome e-mail messages sent to new member accounts created by an administrator.') .' '. $email_token_help,
  );
  $form['email']['admin_created']['user_mail_register_admin_created_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_admin_created_subject'),
    '#maxlength' => 180,
  );
  $form['email']['admin_created']['user_mail_register_admin_created_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_admin_created_body'),
    '#rows' => 15,
  );

  $form['email']['no_approval_required'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome, no approval required'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 1),
    '#description' => t('Customize welcome e-mail messages sent to new members upon registering, when no administrator approval is required.') .' '. $email_token_help
  );
  $form['email']['no_approval_required']['user_mail_register_no_approval_required_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_no_approval_required_subject'),
    '#maxlength' => 180,
  );
  $form['email']['no_approval_required']['user_mail_register_no_approval_required_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_no_approval_required_body'),
    '#rows' => 15,
  );

  $form['email']['pending_approval'] = array(
    '#type' => 'fieldset',
    '#title' => t('Welcome, awaiting administrator approval'),
    '#collapsible' => TRUE,
    '#collapsed' => (variable_get('user_register', 1) != 2),
    '#description' => t('Customize welcome e-mail messages sent to new members upon registering, when administrative approval is required.') .' '. $email_token_help,
  );
  $form['email']['pending_approval']['user_mail_register_pending_approval_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('register_pending_approval_subject'),
    '#maxlength' => 180,
  );
  $form['email']['pending_approval']['user_mail_register_pending_approval_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('register_pending_approval_body'),
    '#rows' => 8,
  );

  $form['email']['password_reset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Password recovery email'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Customize e-mail messages sent to users who request a new password.') .' '. $email_token_help,
  );
  $form['email']['password_reset']['user_mail_password_reset_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('password_reset_subject'),
    '#maxlength' => 180,
  );
  $form['email']['password_reset']['user_mail_password_reset_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('password_reset_body'),
    '#rows' => 12,
  );

  $form['email']['activated'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account activation email'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and customize e-mail messages sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required).') .' '. $email_token_help,
  );
  $form['email']['activated']['user_mail_status_activated_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is activated.'),
    '#default_value' => variable_get('user_mail_status_activated_notify', TRUE),
  );
  $form['email']['activated']['user_mail_status_activated_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_activated_subject'),
    '#maxlength' => 180,
  );
  $form['email']['activated']['user_mail_status_activated_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_activated_body'),
    '#rows' => 15,
  );

  $form['email']['blocked'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account blocked email'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and customize e-mail messages sent to users when their accounts are blocked.') .' '. $email_token_help,
  );
  $form['email']['blocked']['user_mail_status_blocked_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is blocked.'),
    '#default_value' => variable_get('user_mail_status_blocked_notify', FALSE),
  );
  $form['email']['blocked']['user_mail_status_blocked_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_blocked_subject'),
    '#maxlength' => 180,
  );
  $form['email']['blocked']['user_mail_status_blocked_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_blocked_body'),
    '#rows' => 3,
  );

  $form['email']['deleted'] = array(
    '#type' => 'fieldset',
    '#title' => t('Account deleted email'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Enable and customize e-mail messages sent to users when their accounts are deleted.') .' '. $email_token_help,
  );
  $form['email']['deleted']['user_mail_status_deleted_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is deleted.'),
    '#default_value' => variable_get('user_mail_status_deleted_notify', FALSE),
  );
  $form['email']['deleted']['user_mail_status_deleted_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => _user_mail_text('status_deleted_subject'),
    '#maxlength' => 180,
  );
  $form['email']['deleted']['user_mail_status_deleted_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => _user_mail_text('status_deleted_body'),
    '#rows' => 3,
  );

  // User signatures.
  $form['signatures'] = array(
    '#type' => 'fieldset',
    '#title' => t('Signatures'),
  );
  $form['signatures']['user_signatures'] = array(
    '#type' => 'radios',
    '#title' => t('Signature support'),
    '#default_value' => variable_get('user_signatures', 0),
    '#options' => array(t('Disabled'), t('Enabled')),
  );

  // If picture support is enabled, check whether the picture directory exists:
  if (variable_get('user_pictures', 0)) {
    $picture_path = file_create_path(variable_get('user_picture_path', 'pictures'));
    file_check_directory($picture_path, 1, 'user_picture_path');
  }

  $form['pictures'] = array(
    '#type' => 'fieldset',
    '#title' => t('Pictures'),
  );
  $picture_support = variable_get('user_pictures', 0);
  $form['pictures']['user_pictures'] = array(
    '#type' => 'radios',
    '#title' => t('Picture support'),
    '#default_value' => $picture_support,
    '#options' => array(t('Disabled'), t('Enabled')),
    '#prefix' => '<div class="user-admin-picture-radios">',
    '#suffix' => '</div>',
  );
  drupal_add_js(drupal_get_path('module', 'user') .'/user.js');
  // If JS is enabled, and the radio is defaulting to off, hide all
  // the settings on page load via .css using the js-hide class so
  // that there's no flicker.
  $css_class = 'user-admin-picture-settings';
  if (!$picture_support) {
    $css_class .= ' js-hide';
  }
  $form['pictures']['settings'] = array(
    '#prefix' => '<div class="'. $css_class .'">',
    '#suffix' => '</div>',
  );
  $form['pictures']['settings']['user_picture_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture image path'),
    '#default_value' => variable_get('user_picture_path', 'pictures'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('Subdirectory in the directory %dir where pictures will be stored.', array('%dir' => file_directory_path() .'/')),
  );
  $form['pictures']['settings']['user_picture_default'] = array(
    '#type' => 'textfield',
    '#title' => t('Default picture'),
    '#default_value' => variable_get('user_picture_default', ''),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('URL of picture to display for users with no custom picture selected. Leave blank for none.'),
  );
  $form['pictures']['settings']['user_picture_dimensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture maximum dimensions'),
    '#default_value' => variable_get('user_picture_dimensions', '85x85'),
    '#size' => 15,
    '#maxlength' => 10,
    '#description' => t('Maximum dimensions for pictures, in pixels.'),
  );
  $form['pictures']['settings']['user_picture_file_size'] = array(
    '#type' => 'textfield',
    '#title' => t('Picture maximum file size'),
    '#default_value' => variable_get('user_picture_file_size', '30'),
    '#size' => 15,
    '#maxlength' => 10,
    '#description' => t('Maximum file size for pictures, in kB.'),
  );
  $form['pictures']['settings']['user_picture_guidelines'] = array(
    '#type' => 'textarea',
    '#title' => t('Picture guidelines'),
    '#default_value' => variable_get('user_picture_guidelines', ''),
    '#description' => t("This text is displayed at the picture upload form in addition to the default guidelines. It's useful for helping or instructing your users."),
  );

  return system_settings_form($form);
}

/**
 * Menu callback: administer permissions.
 *
 * @ingroup forms
 * @see user_admin_perm_submit()
 * @see theme_user_admin_perm()
 */
function user_admin_perm($form_state, $rid = NULL) {
  if (is_numeric($rid)) {
    $result = db_query('SELECT r.rid, p.perm FROM {role} r LEFT JOIN {permission} p ON r.rid = p.rid WHERE r.rid = %d', $rid);
  }
  else {
    $result = db_query('SELECT r.rid, p.perm FROM {role} r LEFT JOIN {permission} p ON r.rid = p.rid ORDER BY name');
  }

  // Compile role array:
  // Add a comma at the end so when searching for a permission, we can
  // always search for "$perm," to make sure we do not confuse
  // permissions that are substrings of each other.
  while ($role = db_fetch_object($result)) {
    $role_permissions[$role->rid] = $role->perm .',';
  }

  // Retrieve role names for columns.
  $role_names = user_roles();
  if (is_numeric($rid)) {
    $role_names = array($rid => $role_names[$rid]);
  }

  // Render role/permission overview:
  $options = array();
  foreach (module_list(FALSE, FALSE, TRUE) as $module) {
    if ($permissions = module_invoke($module, 'perm')) {
      $form['permission'][] = array(
        '#value' => $module,
      );
      asort($permissions);
      foreach ($permissions as $perm) {
        $options[$perm] = '';
        $form['permission'][$perm] = array('#value' => t($perm));
        foreach ($role_names as $rid => $name) {
          // Builds arrays for checked boxes for each role
          if (strpos($role_permissions[$rid], $perm .',') !== FALSE) {
            $status[$rid][] = $perm;
          }
        }
      }
    }
  }

  // Have to build checkboxes here after checkbox arrays are built
  foreach ($role_names as $rid => $name) {
    $form['checkboxes'][$rid] = array('#type' => 'checkboxes', '#options' => $options, '#default_value' => isset($status[$rid]) ? $status[$rid] : array());
    $form['role_names'][$rid] = array('#value' => $name, '#tree' => TRUE);
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save permissions'));

  return $form;
}

function user_admin_perm_submit($form, &$form_state) {
  // Save permissions:
  $result = db_query('SELECT * FROM {role}');
  while ($role = db_fetch_object($result)) {
    if (isset($form_state['values'][$role->rid])) {
      // Delete, so if we clear every checkbox we reset that role;
      // otherwise permissions are active and denied everywhere.
      db_query('DELETE FROM {permission} WHERE rid = %d', $role->rid);
      $form_state['values'][$role->rid] = array_filter($form_state['values'][$role->rid]);
      if (count($form_state['values'][$role->rid])) {
        db_query("INSERT INTO {permission} (rid, perm) VALUES (%d, '%s')", $role->rid, implode(', ', array_keys($form_state['values'][$role->rid])));
      }
    }
  }

  drupal_set_message(t('The changes have been saved.'));

  // Clear the cached pages
  cache_clear_all();
}

/**
 * Theme the administer permissions page.
 *
 * @ingroup themeable
 */
function theme_user_admin_perm($form) {
  $roles = user_roles();
  foreach (element_children($form['permission']) as $key) {
    // Don't take form control structures
    if (is_array($form['permission'][$key])) {
      $row = array();
      // Module name
      if (is_numeric($key)) {
        $row[] = array('data' => t('@module module', array('@module' => drupal_render($form['permission'][$key]))), 'class' => 'module', 'id' => 'module-'. $form['permission'][$key]['#value'], 'colspan' => count($form['role_names']) + 1);
      }
      else {
        $row[] = array('data' => drupal_render($form['permission'][$key]), 'class' => 'permission');
        foreach (element_children($form['checkboxes']) as $rid) {
          if (is_array($form['checkboxes'][$rid])) {
            $row[] = array('data' => drupal_render($form['checkboxes'][$rid][$key]), 'class' => 'checkbox', 'title' => $roles[$rid] .' : '. t($key));
          }
        }
      }
      $rows[] = $row;
    }
  }
  $header[] = (t('Permission'));
  foreach (element_children($form['role_names']) as $rid) {
    if (is_array($form['role_names'][$rid])) {
      $header[] = array('data' => drupal_render($form['role_names'][$rid]), 'class' => 'checkbox');
    }
  }
  $output = theme('table', $header, $rows, array('id' => 'permissions'));
  $output .= drupal_render($form);
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
    $role = db_fetch_object(db_query('SELECT * FROM {role} WHERE rid = %d', $rid));
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
      if (db_result(db_query("SELECT COUNT(*) FROM {role} WHERE name = '%s' AND rid != %d", $form_state['values']['name'], $form_state['values']['rid']))) {
        form_set_error('name', t('The role name %name already exists. Please choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
    else if ($form_state['values']['op'] == t('Add role')) {
      if (db_result(db_query("SELECT COUNT(*) FROM {role} WHERE name = '%s'", $form_state['values']['name']))) {
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
    db_query("UPDATE {role} SET name = '%s' WHERE rid = %d", $form_state['values']['name'], $form_state['values']['rid']);
    drupal_set_message(t('The role has been renamed.'));
  }
  else if ($form_state['values']['op'] == t('Delete role')) {
    db_query('DELETE FROM {role} WHERE rid = %d', $form_state['values']['rid']);
    db_query('DELETE FROM {permission} WHERE rid = %d', $form_state['values']['rid']);
    // Update the users who have this role set:
    db_query('DELETE FROM {users_roles} WHERE rid = %d', $form_state['values']['rid']);

    drupal_set_message(t('The role has been deleted.'));
  }
  else if ($form_state['values']['op'] == t('Add role')) {
    db_query("INSERT INTO {role} (name) VALUES ('%s')", $form_state['values']['name']);
    drupal_set_message(t('The role has been added.'));
  }
  $form_state['redirect'] = 'admin/user/roles';
  return;
}

/**
 * Menu callback: list all access rules
 */
function user_admin_access_check() {
  $output = drupal_get_form('user_admin_check_user');
  $output .= drupal_get_form('user_admin_check_mail');
  $output .= drupal_get_form('user_admin_check_host');
  return $output;
}

/**
 * Menu callback: add an access rule.
 */
function user_admin_access_add($mask = NULL, $type = NULL) {
  $edit = array();
  $edit['aid'] = 0;
  $edit['mask'] = $mask;
  $edit['type'] = $type;
  return drupal_get_form('user_admin_access_add_form', $edit, t('Add rule'));
}

/**
 * Menu callback: edit an access rule.
 */
function user_admin_access_edit($aid = 0) {
  $edit = db_fetch_array(db_query('SELECT aid, type, status, mask FROM {access} WHERE aid = %d', $aid));
  return drupal_get_form('user_admin_access_edit_form', $edit, t('Save rule'));
}

/**
 * Form builder; Configure access rules.
 *
 * @ingroup forms
 */
function user_admin_access_form(&$form_state, $edit, $submit) {
  $form = array();
  $form['aid'] = array(
    '#type' => 'value',
    '#value' => $edit['aid'],
  );
  $form['status'] = array(
    '#type' => 'radios',
    '#title' => t('Access type'),
    '#default_value' => isset($edit['status']) ? $edit['status'] : 0,
    '#options' => array('1' => t('Allow'), '0' => t('Deny')),
  );
  $type_options = array('user' => t('Username'), 'mail' => t('E-mail'), 'host' => t('Host'));
  $form['type'] = array(
    '#type' => 'radios',
    '#title' => t('Rule type'),
    '#default_value' => (isset($type_options[$edit['type']]) ? $edit['type'] : 'user'),
    '#options' => $type_options,
  );
  $form['mask'] = array(
    '#type' => 'textfield',
    '#title' => t('Mask'),
    '#size' => 30,
    '#maxlength' => 64,
    '#default_value' => $edit['mask'],
    '#description' => '%: '. t('Matches any number of characters, even zero characters') .'.<br />_: '. t('Matches exactly one character.'),
    '#required' => TRUE,
  );
  $form['submit'] = array('#type' => 'submit', '#value' => $submit);
  $form['#submit'] = array('user_admin_access_form_submit');

  return $form;
}

/**
 * Submit callback for user_admin_access_form().
 */
function user_admin_access_form_submit($form, &$form_state) {
  $edit = $form_state['values'];
  if ($edit['aid']) {
    db_query("UPDATE {access} SET mask = '%s', type = '%s', status = '%s' WHERE aid = %d", $edit['mask'], $edit['type'], $edit['status'], $edit['aid']);
    drupal_set_message(t('The access rule has been saved.'));
  }
  else {
    db_query("INSERT INTO {access} (mask, type, status) VALUES ('%s', '%s', %d)", $edit['mask'], $edit['type'], $edit['status']);
    drupal_set_message(t('The access rule has been added.'));
  }
  $form_state['redirect'] = 'admin/user/rules';
}

function user_admin_access_check_validate($form, &$form_state) {
  if (empty($form_state['values']['test'])) {
    form_set_error($form_state['values']['type'], t('No value entered. Please enter a test string and try again.'));
  }
}

function user_admin_check_user() {
  $form['user'] = array('#type' => 'fieldset', '#title' => t('Username'));
  $form['user']['test'] = array('#type' => 'textfield', '#title' => '', '#description' => t('Enter a username to check if it will be denied or allowed.'), '#size' => 30, '#maxlength' => USERNAME_MAX_LENGTH);
  $form['user']['type'] = array('#type' => 'hidden', '#value' => 'user');
  $form['user']['submit'] = array('#type' => 'submit', '#value' => t('Check username'));
  $form['#submit'][] = 'user_admin_access_check_submit';
  $form['#validate'][] = 'user_admin_access_check_validate';
  $form['#theme'] = 'user_admin_access_check';
  return $form;
}

function user_admin_check_mail() {
  $form['mail'] = array('#type' => 'fieldset', '#title' => t('E-mail'));
  $form['mail']['test'] = array('#type' => 'textfield', '#title' => '', '#description' => t('Enter an e-mail address to check if it will be denied or allowed.'), '#size' => 30, '#maxlength' => EMAIL_MAX_LENGTH);
  $form['mail']['type'] = array('#type' => 'hidden', '#value' => 'mail');
  $form['mail']['submit'] = array('#type' => 'submit', '#value' => t('Check e-mail'));
  $form['#submit'][] = 'user_admin_access_check_submit';
  $form['#validate'][] = 'user_admin_access_check_validate';
  $form['#theme'] = 'user_admin_access_check';
  return $form;
}

function user_admin_check_host() {
  $form['host'] = array('#type' => 'fieldset', '#title' => t('Hostname'));
  $form['host']['test'] = array('#type' => 'textfield', '#title' => '', '#description' => t('Enter a hostname or IP address to check if it will be denied or allowed.'), '#size' => 30, '#maxlength' => 64);
  $form['host']['type'] = array('#type' => 'hidden', '#value' => 'host');
  $form['host']['submit'] = array('#type' => 'submit', '#value' => t('Check hostname'));
  $form['#submit'][] = 'user_admin_access_check_submit';
  $form['#validate'][] = 'user_admin_access_check_validate';
  $form['#theme'] = 'user_admin_access_check';
  return $form;
}

function user_admin_access_check_submit($form, &$form_state) {
  switch ($form_state['values']['type']) {
    case 'user':
      if (drupal_is_denied('user', $form_state['values']['test'])) {
        drupal_set_message(t('The username %name is not allowed.', array('%name' => $form_state['values']['test'])));
      }
      else {
        drupal_set_message(t('The username %name is allowed.', array('%name' => $form_state['values']['test'])));
      }
      break;
    case 'mail':
      if (drupal_is_denied('mail', $form_state['values']['test'])) {
        drupal_set_message(t('The e-mail address %mail is not allowed.', array('%mail' => $form_state['values']['test'])));
      }
      else {
        drupal_set_message(t('The e-mail address %mail is allowed.', array('%mail' => $form_state['values']['test'])));
      }
      break;
    case 'host':
      if (drupal_is_denied('host', $form_state['values']['test'])) {
        drupal_set_message(t('The hostname %host is not allowed.', array('%host' => $form_state['values']['test'])));
      }
      else {
        drupal_set_message(t('The hostname %host is allowed.', array('%host' => $form_state['values']['test'])));
      }
      break;
    default:
      break;
  }
}

/**
 * Menu callback: delete an access rule
 *
 * @ingroup forms
 * @see user_admin_access_delete_confirm_submit()
 */
function user_admin_access_delete_confirm($form_state, $aid = 0) {
  $access_types = array('user' => t('username'), 'mail' => t('e-mail'), 'host' => t('host'));
  $edit = db_fetch_object(db_query('SELECT aid, type, status, mask FROM {access} WHERE aid = %d', $aid));

  $form = array();
  $form['aid'] = array('#type' => 'hidden', '#value' => $aid);
  $output = confirm_form($form,
                  t('Are you sure you want to delete the @type rule for %rule?', array('@type' => $access_types[$edit->type], '%rule' => $edit->mask)),
                  'admin/user/rules',
                  t('This action cannot be undone.'),
                  t('Delete'),
                  t('Cancel'));
  return $output;
}

function user_admin_access_delete_confirm_submit($form, &$form_state) {
  db_query('DELETE FROM {access} WHERE aid = %d', $form_state['values']['aid']);
  drupal_set_message(t('The access rule has been deleted.'));
  $form_state['redirect'] = 'admin/user/rules';
  return;
}

/**
 * Menu callback: list all access rules
 */
function user_admin_access() {
  $header = array(array('data' => t('Access type'), 'field' => 'status'), array('data' => t('Rule type'), 'field' => 'type'), array('data' => t('Mask'), 'field' => 'mask'), array('data' => t('Operations'), 'colspan' => 2));
  $result = db_query("SELECT aid, type, status, mask FROM {access}". tablesort_sql($header));
  $access_types = array('user' => t('username'), 'mail' => t('e-mail'), 'host' => t('host'));
  $rows = array();
  while ($rule = db_fetch_object($result)) {
    $rows[] = array($rule->status ? t('allow') : t('deny'), $access_types[$rule->type], $rule->mask, l(t('edit'), 'admin/user/rules/edit/'. $rule->aid), l(t('delete'), 'admin/user/rules/delete/'. $rule->aid));
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => '<em>'. t('There are currently no access rules.') .'</em>', 'colspan' => 5));
  }
  return theme('table', $header, $rows);
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
  if (isset($form['name']) && is_array($form['name'])) {
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
  if ($form['pager']['#value']) {
    $output .= drupal_render($form['pager']);
  }

  $output .= drupal_render($form);

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
    $edit_permissions = l(t('edit permissions'), 'admin/user/permissions/'. $rid);
    if (!in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
      $rows[] = array($name, l(t('edit role'), 'admin/user/roles/edit/'. $rid), $edit_permissions);
    }
    else {
      $rows[] = array($name, t('locked'), $edit_permissions);
    }
  }
  $rows[] = array(drupal_render($form['name']), array('data' => drupal_render($form['submit']), 'colspan' => 2));

  $output = drupal_render($form);
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
  $output .= drupal_render($form);
  return $output;
}

/**
 * Theme user administration filter selector.
 *
 * @ingroup themeable
 */
function theme_user_filters($form) {
  $output = '<ul class="clear-block">';
  if (!empty($form['current'])) {
    foreach (element_children($form['current']) as $key) {
      $output .= '<li>'. drupal_render($form['current'][$key]) .'</li>';
    }
  }

  $output .= '<li><dl class="multiselect">'. (!empty($form['current']) ? '<dt><em>'. t('and') .'</em> '. t('where') .'</dt>' : '') .'<dd class="a">';
  foreach (element_children($form['filter']) as $key) {
    $output .= drupal_render($form['filter'][$key]);
  }
  $output .= '</dd>';

  $output .= '<dt>'. t('is') .'</dt><dd class="b">';

  foreach (element_children($form['status']) as $key) {
    $output .= drupal_render($form['status'][$key]);
  }
  $output .= '</dd>';

  $output .= '</dl>';
  $output .= '<div class="container-inline" id="user-admin-buttons">'. drupal_render($form['buttons']) .'</div>';
  $output .= '</li></ul>';

  return $output;
}
