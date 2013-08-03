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
    ->fields('u', array('uid'))
    ->limit(50)
    ->orderByHeader($header)
    ->setCountQuery($count_query);
  $uids = $query->execute()
    ->fetchCol('uid');

  $destination = drupal_get_destination();
  $accounts = user_load_multiple($uids);

  foreach ($accounts as $account) {
    $users_roles = $account->getRoles();
    unset($users_roles[0]);
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
      'status' => $account->isActive() ? t('active') : t('blocked'),
      'roles' => drupal_render($item_list),
      'member_for' => format_interval(REQUEST_TIME - $account->getCreatedTime()),
      'access' => $account->getLastAccessedTime() ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $account->getLastAccessedTime()))) : t('never'),
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
