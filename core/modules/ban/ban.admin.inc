<?php

/**
 * @file
 * Administrative functionality for the Ban module.
 */

/**
 * Page callback; Displays banned IP addresses.
 *
 * @param string $default_ip
 *   (optional) IP address to be passed on to drupal_get_form() for
 *   use as the default value of the IP address form field.
 */
function ban_admin_page($default_ip = '') {
  $rows = array();
  $header = array(t('banned IP addresses'), t('Operations'));
  $result = db_query('SELECT * FROM {ban_ip}');
  foreach ($result as $ip) {
    $row = array();
    $row[] = $ip->ip;
    $links = array();
    $links['delete'] = array(
      'title' => t('delete'),
      'href' => "admin/config/people/ban/delete/$ip->iid",
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $rows[] = $row;
  }

  $build['ban_ip_form'] = drupal_get_form('ban_ip_form', $default_ip);

  $build['ban_ip_banning_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No blocked IP addresses available.'),
  );

  return $build;
}

/**
 * Form constructor for banning an IP address.
 *
 * @param string $default_ip
 *   An IP address to ban, used as default value.
 *
 * @see ban_ip_form_validate()
 * @see ban_ip_form_submit()
 *
 * @ingroup forms
 */
function ban_ip_form($form, &$form_state, $default_ip) {
  $form['ip'] = array(
    '#title' => t('IP address'),
    '#type' => 'textfield',
    '#size' => 48,
    '#maxlength' => 40,
    '#default_value' => $default_ip,
    '#description' => t('Enter a valid IP address.'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add'),
  );
  return $form;
}

/**
 * Form validation handler for ban_ip_form().
 *
 * @see ban_ip_form_submit()
 */
function ban_ip_form_validate($form, &$form_state) {
  $ip = trim($form_state['values']['ip']);
  if (db_query("SELECT * FROM {ban_ip} WHERE ip = :ip", array(':ip' => $ip))->fetchField()) {
    form_set_error('ip', t('This IP address is already banned.'));
  }
  elseif ($ip == ip_address()) {
    form_set_error('ip', t('You may not ban your own IP address.'));
  }
  elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
    form_set_error('ip', t('Enter a valid IP address.'));
  }
}

/**
 * Form submission handler for ban_ip_form().
 *
 * @see ban_ip_form_validate()
 */
function ban_ip_form_submit($form, &$form_state) {
  $ip = trim($form_state['values']['ip']);
  db_insert('ban_ip')
    ->fields(array('ip' => $ip))
    ->execute();
  drupal_set_message(t('The IP address %ip has been banned.', array('%ip' => $ip)));
  $form_state['redirect'] = 'admin/config/people/ban';
}

/**
 * Form constructor to unban an IP address.
 *
 * @param array $ban_ip
 *   The IP address record to unban, as provided by ban_ip_load().
 *
 * @see ban_ip_delete_submit()
 */
function ban_ip_delete_form($form, &$form_state, array $ban_ip) {
  $form['ban_ip'] = array(
    '#type' => 'value',
    '#value' => $ban_ip,
  );
  return confirm_form($form,
    t('Are you sure you want to delete %ip?', array('%ip' => $ban_ip['ip'])),
    'admin/config/people/ban',
    NULL,
    t('Delete')
  );
}

/**
 * Form submission handler for ban_ip_delete_form().
 */
function ban_ip_delete_form_submit($form, &$form_state) {
  $banned_ip = $form_state['values']['ban_ip'];
  db_delete('ban_ip')
    ->condition('iid', $banned_ip['iid'])
    ->execute();
  watchdog('user', 'Deleted %ip', array('%ip' => $banned_ip['ip']));
  drupal_set_message(t('The IP address %ip was deleted.', array('%ip' => $banned_ip['ip'])));
  $form_state['redirect'] = 'admin/config/people/ban';
}
