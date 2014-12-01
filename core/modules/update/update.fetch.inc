<?php

/**
 * @file
 * Code required only when fetching information about available updates.
 */

/**
 * Attempts to drain the queue of tasks for release history data to fetch.
 *
 * @see \Drupal\update\UpdateFetcher::fetchData()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.processor')->fetchData().
 */
function _update_fetch_data() {
  \Drupal::service('update.processor')->fetchData();
}

/**
 * Clears out all the available update data and initiates re-fetching.
 *
 * @see \Drupal\update\UpdateManager::refreshUpdateData()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.manager')->refreshUpdateData().
 */
function _update_refresh() {
  \Drupal::service('update.manager')->refreshUpdateData();
}

/**
 * Performs any notifications that should be done once cron fetches new data.
 *
 * This method checks the status of the site using the new data and, depending
 * on the configuration of the site, notifies administrators via email if there
 * are new releases or missing security updates.
 *
 * @see update_requirements()
 */
function _update_cron_notify() {
  $update_config = \Drupal::config('update.settings');
  module_load_install('update');
  $status = update_requirements('runtime');
  $params = array();
  $notify_all = ($update_config->get('notification.threshold') == 'all');
  foreach (array('core', 'contrib') as $report_type) {
    $type = 'update_' . $report_type;
    if (isset($status[$type]['severity'])
        && ($status[$type]['severity'] == REQUIREMENT_ERROR || ($notify_all && $status[$type]['reason'] == UPDATE_NOT_CURRENT))) {
      $params[$report_type] = $status[$type]['reason'];
    }
  }
  if (!empty($params)) {
    $notify_list = $update_config->get('notification.emails');
    if (!empty($notify_list)) {
      $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      foreach ($notify_list as $target) {
        if ($target_user = user_load_by_mail($target)) {
          $target_langcode = $target_user->getPreferredLangcode();
        }
        else {
          $target_langcode = $default_langcode;
        }
        $message = drupal_mail('update', 'status_notify', $target, $target_langcode, $params);
        // Track when the last mail was successfully sent to avoid sending
        // too many emails.
        if ($message['result']) {
          \Drupal::state()->set('update.last_email_notification', REQUEST_TIME);
        }
      }
    }
  }
}

