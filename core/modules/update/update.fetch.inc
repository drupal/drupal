<?php

/**
 * @file
 */

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\update\UpdateManagerInterface;

/**
 * Performs any notifications that should be done once cron fetches new data.
 *
 * This method checks the status of the site using the new data and, depending
 * on the configuration of the site, notifies administrators via email if there
 * are new releases or missing security updates.
 *
 * @see update_requirements()
 */
function _update_cron_notify(): void {
  $update_config = \Drupal::config('update.settings');
  $status = \Drupal::moduleHandler()->invoke('update', 'runtime_requirements');
  $params = [];
  $notify_all = ($update_config->get('notification.threshold') == 'all');
  foreach (['core', 'contrib'] as $report_type) {
    $type = 'update_' . $report_type;
    if (isset($status[$type]['severity'])
        && ($status[$type]['severity'] == RequirementSeverity::Error || ($notify_all && $status[$type]['reason'] == UpdateManagerInterface::NOT_CURRENT))) {
      $params[$report_type] = $status[$type]['reason'];
    }
  }
  if (!empty($params)) {
    $notify_list = $update_config->get('notification.emails');
    if (!empty($notify_list)) {
      $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $request_time = \Drupal::time()->getRequestTime();

      $users = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['mail' => $notify_list]);

      // Index users by email for quick lookup.
      foreach ($users as $user) {
        $users_by_mail[$user->getEmail()] = $user;
      }
      foreach ($notify_list as $target) {
        $target_user = $users_by_mail[$target] ?? NULL;
        $target_langcode = $target_user ? $target_user->getPreferredLangcode() : $default_langcode;
        $message = \Drupal::service('plugin.manager.mail')->mail('update', 'status_notify', $target, $target_langcode, $params);
        // Track when the last mail was successfully sent to avoid sending
        // too many emails.
        if ($message['result']) {
          \Drupal::state()->set('update.last_email_notification', $request_time);
        }
      }
    }
  }
}
