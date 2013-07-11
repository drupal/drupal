<?php

/**
 * @file
 * Admin page callback file for the user module.
 */

/**
 * Page callback: User administration page.
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

  $destination = drupal_get_destination();
  $status = array(t('blocked'), t('active'));
  $roles = array_map('check_plain', user_role_names(TRUE));
  $accounts = array();
  foreach ($result as $account) {
    $account = user_load($account->uid);
    $users_roles = array();
    $roles_result = db_query('SELECT rid FROM {users_roles} WHERE uid = :uid', array(':uid' => $account->id()));
    foreach ($roles_result as $user_role) {
      $users_roles[] = $roles[$user_role->rid];
    }
    asort($users_roles);
    $username = array(
      '#theme' => 'username',
      '#account' => $account,
    );
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => $users_roles,
    );
    $options[$account->id()] = array(
      'username' => drupal_render($username),
      'status' => $status[$account->status],
      'roles' => drupal_render($item_list),
      'member_for' => format_interval(REQUEST_TIME - $account->created),
      'access' => $account->access ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $account->access))) : t('never'),
    );
    $links = array();
    $links['edit'] = array(
      'title' => t('Edit'),
      'href' => 'user/' . $account->id() . '/edit',
      'query' => $destination,
    );
    if (module_invoke('content_translation', 'translate_access', $account)) {
      $links['translate'] = array(
        'title' => t('Translate'),
        'href' => 'user/' . $account->id() . '/translations',
        'query' => $destination,
      );
    }
    $options[$account->id()]['operations']['data'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );

    $options[$account->id()]['title']['data']['#title'] = check_plain($account->name);

  }

  $form['accounts'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $options,
    '#empty' => t('No people available.'),
  );
  $form['pager'] = array(
    '#theme' =>'pager',
  );

  return $form;
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
  $role_permissions = user_role_permissions(array_keys($role_names));

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
        $user_permission_description = array(
          '#theme' => 'user_permission_description',
          '#permission_item' => $perm_item,
          '#hide' => $hide_descriptions,
        );
        $form['permission'][$perm] = array(
          '#type' => 'item',
          '#markup' => $perm_item['title'],
          '#description' => drupal_render($user_permission_description),
        );
        foreach ($role_names as $rid => $name) {
          // Builds arrays for checked boxes for each role
          if (in_array($perm, $role_permissions[$rid])) {
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
  $system_compact_link = array(
    '#theme' => 'system_compact_link',
  );
  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'id' => 'permissions',
    ),
    '#sticky' => TRUE,
  );
  $output = drupal_render($system_compact_link);
  $output .= drupal_render($table);
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
