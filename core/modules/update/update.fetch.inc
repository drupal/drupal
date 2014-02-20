<?php

/**
 * @file
 * Code required only when fetching information about available updates.
 */

/**
 * Batch callback: Processes a step in batch for fetching available update data.
 *
 * @param $context
 *   Reference to an array used for Batch API storage.
 *
 * @see \Drupal\update\UpdateManager::fetchDataBatch()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.manager')->fetchDataBatch().
 */
function update_fetch_data_batch(&$context) {
  \Drupal::service('update.manager')->fetchDataBatch($context);
}

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
 * Processes a task to fetch available update data for a single project.
 *
 * Once the release history XML data is downloaded, it is parsed and saved in an
 * entry just for that project.
 *
 * @param $project
 *   Associative array of information about the project to fetch data for.
 *
 * @return
 *   TRUE if we fetched parsable XML, otherwise FALSE.
 *
 * @see \Drupal\update\UpdateFetcher::processFetchTask()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.processor')->processFetchTask().
 */
function _update_process_fetch_task($project) {
  return \Drupal::service('update.processor')->processFetchTask($project);
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
 * Adds a task to the queue for fetching release history data for a project.
 *
 * We only create a new fetch task if there's no task already in the queue for
 * this particular project (based on 'update_fetch_task' key-value collection).
 *
 * @param $project
 *   Associative array of information about a project as created by
 *   update_get_projects(), including keys such as 'name' (short name), and the
 *   'info' array with data from a .info.yml file for the project.
 *
 * @see \Drupal\update\UpdateFetcher::createFetchTask()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('update.processor')->createFetchTask().
 */
function _update_create_fetch_task($project) {
  \Drupal::service('update.processor')->createFetchTask($project);
}

/**
 * Performs any notifications that should be done once cron fetches new data.
 *
 * This method checks the status of the site using the new data and, depending
 * on the configuration of the site, notifies administrators via e-mail if there
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
      $default_langcode = language_default()->id;
      foreach ($notify_list as $target) {
        if ($target_user = user_load_by_mail($target)) {
          $target_langcode = $target_user->getPreferredLangcode();
        }
        else {
          $target_langcode = $default_langcode;
        }
        $message = drupal_mail('update', 'status_notify', $target, $target_langcode, $params);
        // Track when the last mail was successfully sent to avoid sending
        // too many e-mails.
        if ($message['result']) {
          \Drupal::state()->set('update.last_email_notification', REQUEST_TIME);
        }
      }
    }
  }
}

/**
 * Parses the XML of the Drupal release history info files.
 *
 * @param $raw_xml
 *   A raw XML string of available release data for a given project.
 *
 * @return
 *   Array of parsed data about releases for a given project, or NULL if there
 *   was an error parsing the string.
 */
function update_parse_xml($raw_xml) {
  try {
    $xml = new SimpleXMLElement($raw_xml);
  }
  catch (Exception $e) {
    // SimpleXMLElement::__construct produces an E_WARNING error message for
    // each error found in the XML data and throws an exception if errors
    // were detected. Catch any exception and return failure (NULL).
    return;
  }
  // If there is no valid project data, the XML is invalid, so return failure.
  if (!isset($xml->short_name)) {
    return;
  }
  $data = array();
  foreach ($xml as $k => $v) {
    $data[$k] = (string) $v;
  }
  $data['releases'] = array();
  if (isset($xml->releases)) {
    foreach ($xml->releases->children() as $release) {
      $version = (string) $release->version;
      $data['releases'][$version] = array();
      foreach ($release->children() as $k => $v) {
        $data['releases'][$version][$k] = (string) $v;
      }
      $data['releases'][$version]['terms'] = array();
      if ($release->terms) {
        foreach ($release->terms->children() as $term) {
          if (!isset($data['releases'][$version]['terms'][(string) $term->name])) {
            $data['releases'][$version]['terms'][(string) $term->name] = array();
          }
          $data['releases'][$version]['terms'][(string) $term->name][] = (string) $term->value;
        }
      }
    }
  }
  return $data;
}
