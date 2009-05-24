<?php
// $Id$

/**
 * @file
 * Code required only when fetching information about available updates.
 */

/**
 * Callback to manually check the update status without cron.
 */
function update_manual_status() {
  if (_update_refresh()) {
    drupal_set_message(t('Fetched information about all available new releases and updates.'));
  }
  else {
    drupal_set_message(t('Unable to fetch any information about available new releases and updates.'), 'error');
  }
  drupal_goto('admin/reports/updates');
}

/**
 * Fetch project info via XML from a central server.
 */
function _update_refresh() {
  global $base_url;
  module_load_include('inc', 'update', 'update.compare');

  // Since we're fetching new available update data, we want to clear
  // our cache of both the projects we care about, and the current update
  // status of the site. We do *not* want to clear the cache of available
  // releases just yet, since that data (even if it's stale) can be useful
  // during update_get_projects(); for example, to modules that implement
  // hook_system_info_alter() such as cvs_deploy.
  _update_cache_clear('update_project_projects');
  _update_cache_clear('update_project_data');

  $available = array();
  $data = array();
  $site_key = md5($base_url . drupal_get_private_key());
  $projects = update_get_projects();

  // Now that we have the list of projects, we should also clear our cache of
  // available release data, since even if we fail to fetch new data, we need
  // to clear out the stale data at this point.
  _update_cache_clear('update_available_releases');

  foreach ($projects as $key => $project) {
    $url = _update_build_fetch_url($project, $site_key);
    $xml = drupal_http_request($url);
    if (isset($xml->data)) {
      $data[] = $xml->data;
    }
  }

  if ($data) {
    $available = update_parse_xml($data);
  }
  if (!empty($available) && is_array($available)) {
    $frequency = variable_get('update_check_frequency', 1);
    _update_cache_set('update_available_releases', $available, REQUEST_TIME + (60 * 60 * 24 * $frequency));
    variable_set('update_last_check', REQUEST_TIME);
    watchdog('update', 'Fetched information about all available new releases and updates.', array(), WATCHDOG_NOTICE, l(t('view'), 'admin/reports/updates'));
  }
  else {
    watchdog('update', 'Unable to fetch any information about available new releases and updates.', array(), WATCHDOG_ERROR, l(t('view'), 'admin/reports/updates'));
  }
  return $available;
}

/**
 * Generates the URL to fetch information about project updates.
 *
 * This figures out the right URL to use, based on the project's .info file
 * and the global defaults. Appends optional query arguments when the site is
 * configured to report usage stats.
 *
 * @param $project
 *   The array of project information from update_get_projects().
 * @param $site_key
 *   The anonymous site key hash (optional).
 *
 * @see update_refresh()
 * @see update_get_projects()
 */
function _update_build_fetch_url($project, $site_key = '') {
  $default_url = variable_get('update_fetch_url', UPDATE_DEFAULT_URL);
  if (!isset($project['info']['project status url'])) {
    $project['info']['project status url'] = $default_url;
  }
  $name = $project['name'];
  $url = $project['info']['project status url'];
  $url .= '/' . $name . '/' . DRUPAL_CORE_COMPATIBILITY;
  if (!empty($site_key)) {
    $url .= (strpos($url, '?') === TRUE) ? '&' : '?';
    $url .= 'site_key=';
    $url .= drupal_urlencode($site_key);
    if (!empty($project['info']['version'])) {
      $url .= '&version=';
      $url .= drupal_urlencode($project['info']['version']);
    }
  }
  return $url;
}

/**
 * Perform any notifications that should be done once cron fetches new data.
 *
 * This method checks the status of the site using the new data and depending
 * on the configuration of the site, notifies administrators via email if there
 * are new releases or missing security updates.
 *
 * @see update_requirements()
 */
function _update_cron_notify() {
  include_once DRUPAL_ROOT . '/includes/install.inc';
  $status = update_requirements('runtime');
  $params = array();
  $notify_all = (variable_get('update_notification_threshold', 'all') == 'all');
  foreach (array('core', 'contrib') as $report_type) {
    $type = 'update_' . $report_type;
    if (isset($status[$type]['severity'])
        && ($status[$type]['severity'] == REQUIREMENT_ERROR || ($notify_all && $status[$type]['reason'] == UPDATE_NOT_CURRENT))) {
      $params[$report_type] = $status[$type]['reason'];
    }
  }
  if (!empty($params)) {
    $notify_list = variable_get('update_notify_emails', '');
    if (!empty($notify_list)) {
      $default_language = language_default();
      foreach ($notify_list as $target) {
        if ($target_user = user_load_by_mail($target)) {
          $target_language = user_preferred_language($target_user);
        }
        else {
          $target_language = $default_language;
        }
        drupal_mail('update', 'status_notify', $target, $target_language, $params);
      }
    }
  }
}

/**
 * Parse the XML of the Drupal release history info files.
 *
 * @param $raw_xml_list
 *   Array of raw XML strings, one for each fetched project.
 *
 * @return
 *   Nested array of parsed data about projects and releases.
 */
function update_parse_xml($raw_xml_list) {
  $data = array();
  foreach ($raw_xml_list as $raw_xml) {
    $xml = new SimpleXMLElement($raw_xml);
    $short_name = (string)$xml->short_name;
    $data[$short_name] = array();
    foreach ($xml as $k => $v) {
      $data[$short_name][$k] = (string)$v;
    }
    $data[$short_name]['releases'] = array();
    foreach ($xml->releases->children() as $release) {
      $version = (string)$release->version;
      $data[$short_name]['releases'][$version] = array();
      foreach ($release->children() as $k => $v) {
        $data[$short_name]['releases'][$version][$k] = (string)$v;
      }
      $data[$short_name]['releases'][$version]['terms'] = array();
      if ($release->terms) {
        foreach ($release->terms->children() as $term) {
          if (!isset($data[$short_name]['releases'][$version]['terms'][(string)$term->name])) {
            $data[$short_name]['releases'][$version]['terms'][(string)$term->name] = array();
          }
          $data[$short_name]['releases'][$version]['terms'][(string)$term->name][] = (string)$term->value;
        }
      }
    }
  }
  return $data;
}
