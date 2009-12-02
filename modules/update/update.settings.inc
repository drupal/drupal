<?php
// $Id: update.settings.inc,v 1.10 2009/12/02 01:05:11 dries Exp $

/**
 * @file
 * Code required only for the update status settings form.
 */

/**
 * Form builder for the update settings tab.
 */
function update_settings($form) {
  $form['update_check_frequency'] = array(
    '#type' => 'radios',
    '#title' => t('Check for updates'),
    '#default_value' => variable_get('update_check_frequency', 1),
    '#options' => array(
      '1' => t('Daily'),
      '7' => t('Weekly'),
    ),
    '#description' => t('Select how frequently you want to automatically check for new releases of your currently installed modules and themes.'),
  );

  $form['update_check_disabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check for updates of disabled modules and themes'),
    '#default_value' => variable_get('update_check_disabled', FALSE),
  );

  $notify_emails = variable_get('update_notify_emails', array());
  $form['update_notify_emails'] = array(
    '#type' => 'textarea',
    '#title' => t('E-mail addresses to notify when updates are available'),
    '#rows' => 4,
    '#default_value' => implode("\n", $notify_emails),
    '#description' => t('Whenever your site checks for available updates and finds new releases, it can notify a list of users via e-mail. Put each address on a separate line. If blank, no e-mails will be sent.'),
  );

  $form['update_notification_threshold'] = array(
    '#type' => 'radios',
    '#title' => t('E-mail notification threshold'),
    '#default_value' => variable_get('update_notification_threshold', 'all'),
    '#options' => array(
      'all' => t('All newer versions'),
      'security' => t('Only security updates'),
    ),
    '#description' => t('You can choose to send e-mail only if a security update is available, or to be notified about all newer versions. If there are updates available of Drupal core or any of your installed modules and themes, your site will always print a message on the <a href="@status_report">status report</a> page, and will also display an error message on administration pages if there is a security update.', array('@status_report' => url('admin/reports/status')))
  );

  $form = system_settings_form($form, FALSE);
  // Custom validation callback for the email notification setting.
  $form['#validate'][] = 'update_settings_validate';
  // We need to call our own submit callback first, not the one from
  // system_settings_form(), so that we can process and save the emails.
  unset($form['#submit']);

  return $form;
}

/**
 * Validation callback for the settings form.
 *
 * Validates the email addresses and ensures the field is formatted correctly.
 */
function update_settings_validate($form, &$form_state) {
  if (!empty($form_state['values']['update_notify_emails'])) {
    $valid = array();
    $invalid = array();
    foreach (explode("\n", trim($form_state['values']['update_notify_emails'])) as $email) {
      $email = trim($email);
      if (!empty($email)) {
        if (valid_email_address($email)) {
          $valid[] = $email;
        }
        else {
          $invalid[] = $email;
        }
      }
    }
    if (empty($invalid)) {
      $form_state['notify_emails'] = $valid;
    }
    elseif (count($invalid) == 1) {
      form_set_error('update_notify_emails', t('%email is not a valid e-mail address.', array('%email' => reset($invalid))));
    }
    else {
      form_set_error('update_notify_emails', t('%emails are not valid e-mail addresses.', array('%emails' => implode(', ', $invalid))));
    }
  }
}

/**
 * Submit handler for the settings tab.
 *
 * Also invalidates the cache of available updates if the "Check for updates
 * of disabled modules and themes" setting is being changed. The available
 * updates report need to refetch available update data after this setting
 * changes or it would show misleading things (e.g. listing the disabled
 * projects on the site with the "No available releases found" warning).
 */
function update_settings_submit($form, $form_state) {
  $op = $form_state['values']['op'];

  if (empty($form_state['notify_emails'])) {
    variable_del('update_notify_emails');
  }
  else {
    variable_set('update_notify_emails', $form_state['notify_emails']);
  }
  unset($form_state['notify_emails']);
  unset($form_state['values']['update_notify_emails']);

  // See if the update_check_disabled setting is being changed, and if so,
  // invalidate all cached update status data.
  $check_disabled = variable_get('update_check_disabled', FALSE);
  if ($form_state['values']['update_check_disabled'] != $check_disabled) {
    _update_cache_clear();
  }

  system_settings_form_submit($form, $form_state);
}
