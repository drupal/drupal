<?php

/**
 * @file
 * Admin page callback file for the user module.
 */

/**
 * Page callback: Generates the appropriate user administration form.
 *
 * This function generates the user registration, multiple user cancellation,
 * or filtered user list admin form, depending on the argument and the POST
 * form values.
 *
 * @param string $callback_arg
 *   (optional) Indicates which form to build. Defaults to '', which will
 *   trigger the user filter form. If the POST value 'op' is present, this
 *   function uses that value as the callback argument.
 *
 * @return string
 *   A renderable form array for the respective request.
 */
function user_admin($callback_arg = '') {
  $op = isset($_POST['op']) ? $_POST['op'] : $callback_arg;

  switch ($op) {
    case t('Create new account'):
    case 'create':
      $account = entity_create('user', array());
      $build['user_register'] = entity_get_form($account, 'register');
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
    '#type' => 'details',
    '#title' => t('Show only users where'),
    '#theme' => 'exposed_filters__user',
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
    $t_args = array('%property' => $filters[$type]['title'], '%value' => $value);
    if ($i++) {
      $form['filters']['current'][] = array('#markup' => t('and where %property is %value', $t_args));
    }
    else {
      $form['filters']['current'][] = array('#markup' => t('%property is %value', $t_args));
    }
  }

  $form['filters']['status'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('clearfix')),
    '#prefix' => ($i ? '<div class="additional-filters">' . t('and where') . '</div>' : ''),
  );
  $form['filters']['status']['filters'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('filters')),
  );
  foreach ($filters as $key => $filter) {
    $form['filters']['status']['filters'][$key] = array(
      '#type' => 'select',
      '#options' => $filter['options'],
      '#title' => $filter['title'],
      '#default_value' => '[any]',
    );
  }

  $form['filters']['status']['actions'] = array(
    '#type' => 'actions',
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filters']['status']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => (count($session) ? t('Refine') : t('Filter')),
  );
  if (count($session)) {
    $form['filters']['status']['actions']['undo'] = array(
      '#type' => 'submit',
      '#value' => t('Undo'),
    );
    $form['filters']['status']['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
  }

  drupal_add_library('system', 'drupal.form');

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
    'status' => array('data' => t('Status'), 'field' => 'u.status', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    'roles' => array('data' => t('Roles'), 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    'member_for' => array('data' => t('Member for'), 'field' => 'u.created', 'sort' => 'desc', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    'access' => array('data' => t('Last access'), 'field' => 'u.access', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    'operations' => t('Operations'),
  );

  $query = db_select('users', 'u');
  $query->condition('u.uid', 0, '<>');
  user_build_filter_query($query);

  $count_query = clone $query;
  $count_query->addExpression('COUNT(u.uid)');

  $query = $query
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  $query
    ->fields('u', array('uid', 'name', 'status', 'created', 'access'))
    ->limit(50)
    ->orderByHeader($header)
    ->setCountQuery($count_query);
  $result = $query->execute();

  $form['options'] = array(
    '#type' => 'details',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
  );
  $options = array();
  foreach (module_invoke_all('user_operations') as $operation => $array) {
    $options[$operation] = $array['label'];
  }
  $form['options']['operation'] = array(
    '#type' => 'select',
    '#title' => t('Operation'),
    '#title_display' => 'invisible',
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
  $roles = array_map('check_plain', user_role_names(TRUE));
  $accounts = array();
  foreach ($result as $account) {
    $account = user_load($account->uid);
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
    );
    $links = array();
    $links['edit'] = array(
      'title' => t('Edit'),
      'href' => 'user/' . $account->uid . '/edit',
      'query' => $destination,
    );
    if (module_invoke('translation_entity', 'translate_access', $account)) {
      $links['translate'] = array(
        'title' => t('Translate'),
        'href' => 'user/' . $account->uid . '/translations',
        'query' => $destination,
      );
    }
    $options[$account->uid]['operations']['data'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
  }

  $form['accounts'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No people available.'),
  );
  $form['pager'] = array('#markup' => theme('pager'));

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
 * @see user_admin_settings_submit()
 */
function user_admin_settings($form, &$form_state) {
  $config = config('user.settings');
  $mail_config = config('user.mail');

  // Settings for anonymous users.
  $form['anonymous_settings'] = array(
    '#type' => 'details',
    '#title' => t('Anonymous users'),
  );
  $form['anonymous_settings']['anonymous'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $config->get('anonymous'),
    '#description' => t('The name used to indicate anonymous users.'),
    '#required' => TRUE,
  );

  // Administrative role option.
  $form['admin_role'] = array(
    '#type' => 'details',
    '#title' => t('Administrator role'),
  );

  // Do not allow users to set the anonymous or authenticated user roles as the
  // administrator role.
  $roles = user_role_names();
  unset($roles[DRUPAL_ANONYMOUS_RID]);
  unset($roles[DRUPAL_AUTHENTICATED_RID]);
  $roles[0] = t('disabled');

  $form['admin_role']['user_admin_role'] = array(
    '#type' => 'select',
    '#title' => t('Administrator role'),
    '#default_value' => $config->get('admin_role'),
    '#options' => $roles,
    '#description' => t('This role will be automatically assigned new permissions whenever a module is enabled. Changing this setting will not affect existing permissions.'),
  );

  // @todo Remove this check once language settings are generalized.
  if (module_exists('translation_entity')) {
    $form['language'] = array(
      '#type' => 'details',
      '#title' => t('Language settings'),
      '#tree' => TRUE,
    );
    $form_state['translation_entity']['key'] = 'language';
    $form['language'] += translation_entity_enable_widget('user', 'user', $form, $form_state);
  }

  // User registration settings.
  $form['registration_cancellation'] = array(
    '#type' => 'details',
    '#title' => t('Registration and cancellation'),
  );
  $form['registration_cancellation']['user_register'] = array(
    '#type' => 'radios',
    '#title' => t('Who can register accounts?'),
    '#default_value' => $config->get('register'),
    '#options' => array(
      USER_REGISTER_ADMINISTRATORS_ONLY => t('Administrators only'),
      USER_REGISTER_VISITORS => t('Visitors'),
      USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL => t('Visitors, but administrator approval is required'),
    )
  );
  $form['registration_cancellation']['user_email_verification'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require e-mail verification when a visitor creates an account.'),
    '#default_value' => $config->get('verify_mail'),
    '#description' => t('New users will be required to validate their e-mail address prior to logging into the site, and will be assigned a system-generated password. With this setting disabled, users will be logged in immediately upon registering, and may select their own passwords during registration.')
  );
  $form['registration_cancellation']['user_password_strength'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable password strength indicator'),
    '#default_value' => $config->get('password_strength'),
  );
  form_load_include($form_state, 'inc', 'user', 'user.pages');
  $form['registration_cancellation']['user_cancel_method'] = array(
    '#type' => 'radios',
    '#title' => t('When cancelling a user account'),
    '#default_value' => $config->get('cancel_method'),
    '#description' => t('Users with the %select-cancel-method or %administer-users <a href="@permissions-url">permissions</a> can override this default method.', array('%select-cancel-method' => t('Select method for cancelling account'), '%administer-users' => t('Administer users'), '@permissions-url' => url('admin/people/permissions'))),
  );
  $form['registration_cancellation']['user_cancel_method'] += user_cancel_methods();
  foreach (element_children($form['registration_cancellation']['user_cancel_method']) as $key) {
    // All account cancellation methods that specify #access cannot be
    // configured as default method.
    // @see hook_user_cancel_methods_alter()
    if (isset($form['registration_cancellation']['user_cancel_method'][$key]['#access'])) {
      $form['registration_cancellation']['user_cancel_method'][$key]['#access'] = FALSE;
    }
  }

  // Account settings.
  $form['personalization'] = array(
    '#type' => 'details',
    '#title' => t('Personalization'),
  );
  $form['personalization']['user_signatures'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable signatures.'),
    '#default_value' => $config->get('signatures'),
  );

  $form['email'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('E-mails'),
  );
  // These email tokens are shared for all settings, so just define
  // the list once to help ensure they stay in sync.
  $email_token_help = t('Available variables are: [site:name], [site:url], [user:name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url].');

  $form['email_admin_created'] = array(
    '#type' => 'details',
    '#title' => t('Welcome (new user created by administrator)'),
    '#collapsed' => ($config->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY),
    '#description' => t('Edit the welcome e-mail messages sent to new member accounts created by an administrator.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_admin_created']['user_mail_register_admin_created_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $mail_config->get('register_admin_created.subject'),
    '#maxlength' => 180,
  );
  $form['email_admin_created']['user_mail_register_admin_created_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' =>  $mail_config->get('register_admin_created.body'),
    '#rows' => 15,
  );

  $form['email_pending_approval'] = array(
    '#type' => 'details',
    '#title' => t('Welcome (awaiting approval)'),
    '#collapsed' => ($config->get('register') != USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL),
    '#description' => t('Edit the welcome e-mail messages sent to new members upon registering, when administrative approval is required.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_pending_approval']['user_mail_register_pending_approval_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $mail_config->get('register_pending_approval.subject'),
    '#maxlength' => 180,
  );
  $form['email_pending_approval']['user_mail_register_pending_approval_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('register_pending_approval.body'),
    '#rows' => 8,
  );

  $form['email_pending_approval_admin'] = array(
    '#type' => 'details',
    '#title' => t('Admin (user awaiting approval)'),
    '#collapsed' => ($config->get('register') != USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL),
    '#description' => t('Edit the e-mail notifying the site administrator that there are new members awaiting administrative approval.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_pending_approval_admin']['register_pending_approval_admin_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $mail_config->get('register_pending_approval_admin.subject'),
    '#maxlength' => 180,
  );
  $form['email_pending_approval_admin']['register_pending_approval_admin_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('register_pending_approval_admin.body'),
    '#rows' => 8,
  );

  $form['email_no_approval_required'] = array(
    '#type' => 'details',
    '#title' => t('Welcome (no approval required)'),
    '#collapsed' => ($config->get('register') != USER_REGISTER_VISITORS),
    '#description' => t('Edit the welcome e-mail messages sent to new members upon registering, when no administrator approval is required.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_no_approval_required']['user_mail_register_no_approval_required_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $mail_config->get('register_no_approval_required.subject'),
    '#maxlength' => 180,
  );
  $form['email_no_approval_required']['user_mail_register_no_approval_required_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('register_no_approval_required.body'),
    '#rows' => 15,
  );

  $form['email_password_reset'] = array(
    '#type' => 'details',
    '#title' => t('Password recovery'),
    '#collapsed' => TRUE,
    '#description' => t('Edit the e-mail messages sent to users who request a new password.') . ' ' . $email_token_help,
    '#group' => 'email',
    '#weight' => 10,
  );
  $form['email_password_reset']['user_mail_password_reset_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $mail_config->get('password_reset.subject'),
    '#maxlength' => 180,
  );
  $form['email_password_reset']['user_mail_password_reset_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('password_reset.body'),
    '#rows' => 12,
  );

  $form['email_activated'] = array(
    '#type' => 'details',
    '#title' => t('Account activation'),
    '#collapsed' => TRUE,
    '#description' => t('Enable and edit e-mail messages sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required).') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_activated']['user_mail_status_activated_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is activated.'),
    '#default_value' => $config->get('notify.status_activated'),
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
    '#default_value' => $mail_config->get('status_activated.subject'),
    '#maxlength' => 180,
  );
  $form['email_activated']['settings']['user_mail_status_activated_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('status_activated.body'),
    '#rows' => 15,
  );

  $form['email_blocked'] = array(
    '#type' => 'details',
    '#title' => t('Account blocked'),
    '#collapsed' => TRUE,
    '#description' => t('Enable and edit e-mail messages sent to users when their accounts are blocked.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_blocked']['user_mail_status_blocked_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is blocked.'),
    '#default_value' => $config->get('notify.status_blocked'),
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
    '#default_value' => $mail_config->get('status_blocked.subject'),
    '#maxlength' => 180,
  );
  $form['email_blocked']['settings']['user_mail_status_blocked_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('status_blocked.body'),
    '#rows' => 3,
  );

  $form['email_cancel_confirm'] = array(
    '#type' => 'details',
    '#title' => t('Account cancellation confirmation'),
    '#collapsed' => TRUE,
    '#description' => t('Edit the e-mail messages sent to users when they attempt to cancel their accounts.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_cancel_confirm']['user_mail_cancel_confirm_subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#default_value' => $mail_config->get('cancel_confirm.subject'),
    '#maxlength' => 180,
  );
  $form['email_cancel_confirm']['user_mail_cancel_confirm_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('cancel_confirm.body'),
    '#rows' => 3,
  );

  $form['email_canceled'] = array(
    '#type' => 'details',
    '#title' => t('Account canceled'),
    '#collapsed' => TRUE,
    '#description' => t('Enable and edit e-mail messages sent to users when their accounts are canceled.') . ' ' . $email_token_help,
    '#group' => 'email',
  );
  $form['email_canceled']['user_mail_status_canceled_notify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Notify user when account is canceled.'),
    '#default_value' => $config->get('notify.status_canceled'),
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
    '#default_value' => $mail_config->get('status_canceled.subject'),
    '#maxlength' => 180,
  );
  $form['email_canceled']['settings']['user_mail_status_canceled_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Body'),
    '#default_value' => $mail_config->get('status_canceled.body'),
    '#rows' => 3,
  );

  return system_config_form($form, $form_state);
}

/**
 * Form submission handler for user_admin_settings().
 */
function user_admin_settings_submit($form, &$form_state) {
  config('user.settings')
    ->set('anonymous', $form_state['values']['anonymous'])
    ->set('admin_role', $form_state['values']['user_admin_role'])
    ->set('register', $form_state['values']['user_register'])
    ->set('password_strength', $form_state['values']['user_password_strength'])
    ->set('verify_mail', $form_state['values']['user_email_verification'])
    ->set('signatures', $form_state['values']['user_signatures'])
    ->set('cancel_method', $form_state['values']['user_cancel_method'])
    ->set('notify.status_activated', $form_state['values']['user_mail_status_activated_notify'])
    ->set('notify.status_blocked', $form_state['values']['user_mail_status_blocked_notify'])
    ->set('notify.status_canceled', $form_state['values']['user_mail_status_canceled_notify'])
    ->save();
  config('user.mail')
    ->set('cancel_confirm.body', $form_state['values']['user_mail_cancel_confirm_body'])
    ->set('cancel_confirm.subject', $form_state['values']['user_mail_cancel_confirm_subject'])
    ->set('password_reset.body', $form_state['values']['user_mail_password_reset_body'])
    ->set('password_reset.subject', $form_state['values']['user_mail_password_reset_subject'])
    ->set('register_admin_created.body', $form_state['values']['user_mail_register_admin_created_body'])
    ->set('register_admin_created.subject', $form_state['values']['user_mail_register_admin_created_subject'])
    ->set('register_no_approval_required.body', $form_state['values']['user_mail_register_no_approval_required_body'])
    ->set('register_no_approval_required.subject', $form_state['values']['user_mail_register_no_approval_required_subject'])
    ->set('register_pending_approval.body', $form_state['values']['user_mail_register_pending_approval_body'])
    ->set('register_pending_approval.subject', $form_state['values']['user_mail_register_pending_approval_subject'])
    ->set('status_activated.body', $form_state['values']['user_mail_status_activated_body'])
    ->set('status_activated.subject', $form_state['values']['user_mail_status_activated_subject'])
    ->set('status_blocked.body', $form_state['values']['user_mail_status_blocked_body'])
    ->set('status_blocked.subject', $form_state['values']['user_mail_status_blocked_subject'])
    ->set('status_canceled.body', $form_state['values']['user_mail_status_canceled_body'])
    ->set('status_canceled.subject', $form_state['values']['user_mail_status_canceled_subject'])
    ->save();
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
  $role_names = user_role_names();
  if (isset($rid)) {
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
  $hide_descriptions = system_admin_compact_mode();

  // Get a list of all the modules implementing a hook_permission() and sort by
  // display name.
  $modules = array();
  foreach (module_implements('permission') as $module) {
    $modules[$module] = $module_info[$module]['name'];
  }
  asort($modules);

  foreach ($modules as $module => $display_name) {
    if ($permissions = module_invoke($module, 'permission')) {
      $form['permission'][] = array(
        '#markup' => $module_info[$module]['name'],
        '#id' => $module,
      );
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += array(
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        );
        $options[$perm] = '';
        $form['permission'][$perm] = array(
          '#type' => 'item',
          '#markup' => $perm_item['title'],
          '#description' => theme('user_permission_description', array('permission_item' => $perm_item, 'hide' => $hide_descriptions)),
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
    $form['checkboxes'][$rid] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => isset($status[$rid]) ? $status[$rid] : array(),
      '#attributes' => array('class' => array('rid-' . $rid)),
    );
    $form['role_names'][$rid] = array('#markup' => check_plain($name), '#tree' => TRUE);
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save permissions'));

  $form['#attached']['library'][] = array('user', 'drupal.user.permissions');

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
  cache_invalidate_tags(array('content' => TRUE));
}

/**
 * Returns HTML for the administer permissions page.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_user_admin_permissions($variables) {
  $form = $variables['form'];

  $roles = user_role_names();
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
        $form['checkboxes'][$rid][$key]['#title'] = $roles[$rid] . ': ' . $form['permission'][$key]['#markup'];
        $form['checkboxes'][$rid][$key]['#title_display'] = 'invisible';
        $row[] = array('data' => drupal_render($form['checkboxes'][$rid][$key]), 'class' => array('checkbox'));
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
 * Returns HTML for an individual permission description.
 *
 * @param $variables
 *   An associative array containing:
 *   - permission_item: An associative array representing the permission whose
 *     description is being themed. Useful keys include:
 *     - description: The text of the permission description.
 *     - warning: A security-related warning message about the permission (if
 *       there is one).
 *   - hide: A boolean indicating whether or not the permission description was
 *     requested to be hidden rather than shown.
 *
 * @ingroup themeable
 */
function theme_user_permission_description($variables) {
  if (!$variables['hide']) {
    $description = array();
    $permission_item = $variables['permission_item'];
    if (!empty($permission_item['description'])) {
      $description[] = $permission_item['description'];
    }
    if (!empty($permission_item['warning'])) {
      $description[] = '<em class="permission-warning">' . $permission_item['warning'] . '</em>';
    }
    if (!empty($description)) {
      return implode(' ', $description);
    }
  }
}

/**
 * Form to re-order roles or add a new one.
 *
 * @ingroup forms
 * @see theme_user_admin_roles()
 */
function user_admin_roles($form, $form_state) {
  $roles = user_roles();

  $form['roles'] = array(
    '#tree' => TRUE,
  );

  foreach ($roles as $rid => $role) {
    $form['roles'][$rid]['#role'] = $role;
    $form['roles'][$rid]['#weight'] = $role->weight;
    $form['roles'][$rid]['name'] = array(
      '#markup' => check_plain($role->label()),
    );
    $form['roles'][$rid]['weight'] = array(
      '#type' => 'textfield',
      '#title' => t('Weight for @title', array('@title' => $role->label())),
      '#title_display' => 'invisible',
      '#size' => 4,
      '#default_value' => $role->weight,
      '#attributes' => array('class' => array('role-weight')),
    );
    $links['edit'] = array(
      'title' => t('Edit role'),
      'href' => 'admin/people/roles/edit/' . $rid,
      'weight' => 0,
    );
    $links['permissions'] = array(
      'title' => t('Edit permissions'),
      'href' => 'admin/people/permissions/' . $rid,
      'weight' => 5,
    );
    $form['roles'][$rid]['operations'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
  }

  // Embed the role add form.
  $add_role = entity_create('user_role', array(
    'id' => NULL,
    'label' => NULL,
  ));
  $add_form = user_admin_role(array(), $form_state, $add_role);
  $add_form['actions']['submit']['#submit'] = array('user_admin_role_submit');
  $add_form['role']['actions'] = $add_form['actions'];
  unset($add_form['actions']);
  $form += $add_form;

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save order'),
    // Do not validate the add form when saving the order.
    '#limit_validation_errors' => array(array('roles')),
    '#submit' => array('user_admin_roles_order_submit'),
  );

  return $form;
}

/**
 * Form submit function. Update the role weights.
 */
function user_admin_roles_order_submit($form, &$form_state) {
  foreach ($form_state['values']['roles'] as $rid => $role_values) {
    $role = $form['roles'][$rid]['#role'];
    $role->weight = $role_values['weight'];
    $role->save();
  }
  drupal_set_message(t('The role settings have been updated.'));
}

/**
 * Returns HTML for the role order and new role form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_user_admin_roles($variables) {
  $form = $variables['form'];

  $header = array(t('Name'), t('Weight'), t('Operations'));
  foreach (element_children($form['roles']) as $rid) {
    $row = array();
    foreach (element_children($form['roles'][$rid]) as $column) {
      $row[] = drupal_render($form['roles'][$rid][$column]);
    }
    $rows[] = array('data' => $row, 'class' => array('draggable'));
  }

  // Distribute the role add form into table columns.
  $form['role']['name']['#title_display'] = 'invisible';
  unset($form['role']['name']['#description']);
  unset($form['role']['rid']['#description']);

  $actions = $form['role']['actions'];
  unset($form['role']['actions']);
  unset($form['role']['weight']);
  $row = array();
  $row[] = drupal_render($form['role']);
  // Empty placeholder for the weight column.
  $row[] = '';
  $row[] = array('data' => drupal_render($actions), 'colspan' => 2);
  $rows[] = array('data' => $row);

  drupal_add_tabledrag('user-roles', 'order', 'sibling', 'role-weight');

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'user-roles')));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Form to configure a single role.
 *
 * @ingroup forms
 * @see user_admin_role_submit()
 *
 * @todo Move into a RoleFormController.
 */
function user_admin_role($form, $form_state, $role) {
  $form['role'] = array(
    '#tree' => TRUE,
    '#parents' => array('role'),
  );
  $form['role']['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Role name'),
    '#default_value' => $role->label(),
    '#size' => 30,
    '#required' => TRUE,
    '#maxlength' => 64,
    '#description' => t('The name for this role. Example: "Moderator", "Editorial board", "Site architect".'),
  );
  $form['role']['id'] = array(
    '#type' => 'machine_name',
    '#default_value' => $role->id(),
    '#required' => TRUE,
    '#disabled' => !$role->isNew(),
    '#size' => 30,
    '#maxlength' => 64,
    '#machine_name' => array(
      'exists' => 'user_role_load',
      'source' => array('role', 'label'),
    ),
  );
  $form['role']['weight'] = array(
    '#type' => 'value',
    '#value' => $role->weight,
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => !$role->isNew() ? t('Save role') : t('Add role'),
  );
  $form['actions']['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete role'),
    '#access' => !$role->isNew() && !in_array($role->id(), array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID)),
    '#submit' => array('user_admin_role_delete_submit'),
  );

  return $form;
}

/**
 * Form submit handler for the user_admin_role() form.
 */
function user_admin_role_submit($form, &$form_state) {
  // Prevent leading and trailing spaces in role names.
  $form_state['values']['role']['label'] = trim($form_state['values']['role']['label']);

  $role = entity_create('user_role', $form_state['values']['role']);
  if ($role->save() == SAVED_UPDATED) {
    drupal_set_message(t('The role has been renamed.'));
  }
  else {
    drupal_set_message(t('The role has been added.'));
  }
  $form_state['redirect'] = 'admin/people/roles';
}

/**
 * Form submit handler for the user_admin_role() form.
 */
function user_admin_role_delete_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/people/roles/delete/' . $form_state['values']['role']['id'];
}

/**
 * Form to confirm role delete operation.
 */
function user_admin_role_delete_confirm($form, &$form_state, $role) {
  $form['id'] = array(
    '#type' => 'value',
    '#value' => $role->id(),
  );
  return confirm_form($form, t('Are you sure you want to delete the role %name ?', array('%name' => $role->label())), 'admin/people/roles', t('This action cannot be undone.'), t('Delete'));
}

/**
 * Form submit handler for user_admin_role_delete_confirm().
 */
function user_admin_role_delete_confirm_submit($form, &$form_state) {
  entity_delete_multiple('user_role', array($form_state['values']['id']));
  drupal_set_message(t('The role has been deleted.'));
  $form_state['redirect'] = 'admin/people/roles';
}
