<?php

/**
 * @file
 * Code required only when rendering the available updates report.
 */

/**
 * Menu callback. Generate a page about the update status of projects.
 */
function update_status() {
  if ($available = update_get_available(TRUE)) {
    module_load_include('inc', 'update', 'update.compare');
    $data = update_calculate_project_data($available);
    return theme('update_report', $data);
  }
  else {
    return theme('update_report', _update_no_data());
  }
}

/**
 * Theme project status report.
 *
 * @ingroup themeable
 */
function theme_update_report($data) {
  $last = variable_get('update_last_check', 0);
  $output = '<div class="update checked">'. ($last ? t('Last checked: @time ago', array('@time' => format_interval(time() - $last))) : t('Last checked: never'));
  $output .= ' <span class="check-manually">('. l(t('Check manually'), 'admin/reports/updates/check') .')</span>';
  $output .= "</div>\n";

  if (!is_array($data)) {
    $output .= '<p>'. $data .'</p>';
    return $output;
  }

  $header = array();
  $rows = array();

  $notification_level = variable_get('update_notification_threshold', 'all');

  foreach ($data as $project) {
    switch ($project['status']) {
      case UPDATE_CURRENT:
        $class = 'ok';
        $icon = theme('image', 'misc/watchdog-ok.png', t('ok'), t('ok'));
        break;
      case UPDATE_UNKNOWN:
      case UPDATE_NOT_FETCHED:
        $class = 'unknown';
        $icon = theme('image', 'misc/watchdog-warning.png', t('warning'), t('warning'));
        break;
      case UPDATE_NOT_SECURE:
      case UPDATE_REVOKED:
      case UPDATE_NOT_SUPPORTED:
        $class = 'error';
        $icon = theme('image', 'misc/watchdog-error.png', t('error'), t('error'));
        break;
      case UPDATE_NOT_CHECKED:
      case UPDATE_NOT_CURRENT:
      default:
        $class = 'warning';
        $icon = theme('image', 'misc/watchdog-warning.png', t('warning'), t('warning'));
        break;
    }

    $row = '<div class="version-status">';
    switch ($project['status']) {
      case UPDATE_NOT_SECURE:
        $row .= '<span class="security-error">'. t('Security update required!') .'</span>';
        break;
      case UPDATE_REVOKED:
        $row .= '<span class="revoked">'. t('Revoked!') .'</span>';
        break;
      case UPDATE_NOT_SUPPORTED:
        $row .= '<span class="not-supported">'. t('Not supported!') .'</span>';
        break;
      case UPDATE_NOT_CURRENT:
        $row .= '<span class="not-current">'. t('Update available') .'</span>';
        break;
      case UPDATE_CURRENT:
        $row .= '<span class="current">'. t('Up to date') .'</span>';
        break;
      default:
        $row .= check_plain($project['reason']);
        break;
    }
    $row .= '<span class="icon">'. $icon .'</span>';
    $row .= "</div>\n";

    $row .= '<div class="project">';
    if (isset($project['title'])) {
      if (isset($project['link'])) {
        $row .= l($project['title'], $project['link']);
      }
      else {
        $row .= check_plain($project['title']);
      }
    }
    else {
      $row .= check_plain($project['name']);
    }
    $row .= ' '. check_plain($project['existing_version']);
    if ($project['install_type'] == 'dev' && !empty($project['datestamp'])) {
      $row .= ' <span class="version-date">('. format_date($project['datestamp'], 'custom', 'Y-M-d') .')</span>';
    }
    $row .= "</div>\n";

    $row .= "<div class=\"versions\">\n";

    if (isset($project['recommended'])) {
      if ($project['status'] != UPDATE_CURRENT || $project['existing_version'] !== $project['recommended']) {

        // First, figure out what to recommend.
        // If there's only 1 security update and it has the same version we're
        // recommending, give it the same CSS class as if it was recommended,
        // but don't print out a separate "Recommended" line for this project.
        if (!empty($project['security updates']) && count($project['security updates']) == 1 && $project['security updates'][0]['version'] === $project['recommended']) {
          $security_class = ' version-recommended version-recommended-strong';
        }
        else {
          $security_class = '';
          $version_class = 'version-recommended';
          // Apply an extra class if we're displaying both a recommended
          // version and anything else for an extra visual hint.
          if ($project['recommended'] !== $project['latest_version']
              || !empty($project['also'])
              || ($project['install_type'] == 'dev'
                 && isset($project['dev_version'])
                 && $project['latest_version'] !== $project['dev_version']
                 && $project['recommended'] !== $project['dev_version'])
              || (isset($project['security updates'][0])
                 && $project['recommended'] !== $project['security updates'][0])
              ) {
            $version_class .= ' version-recommended-strong';
          }
          $row .= theme('update_version', $project['releases'][$project['recommended']], t('Recommended version:'), $version_class);
        }

        // Now, print any security updates.
        if (!empty($project['security updates'])) {
          foreach ($project['security updates'] as $security_update) {
            $row .= theme('update_version', $security_update, t('Security update:'), 'version-security'. $security_class);
          }
        }
      }

      if ($project['recommended'] !== $project['latest_version']) {
        $row .= theme('update_version', $project['releases'][$project['latest_version']], t('Latest version:'), 'version-latest');
      }
      if ($project['install_type'] == 'dev'
          && $project['status'] != UPDATE_CURRENT
          && isset($project['dev_version'])
          && $project['recommended'] !== $project['dev_version']) {
        $row .= theme('update_version', $project['releases'][$project['dev_version']], t('Development version:'), 'version-latest');
      }
    }

    if (isset($project['also'])) {
      foreach ($project['also'] as $also) {
        $row .= theme('update_version', $project['releases'][$also], t('Also available:'), 'version-also-available');
      }
    }

    $row .= "</div>\n"; // versions div.

    $row .= "<div class=\"info\">\n";
    if (!empty($project['extra'])) {
      $row .= '<div class="extra">'."\n";
      foreach ($project['extra'] as $key => $value) {
        $row .= '<div class="'. $value['class'] .'">';
        $row .= check_plain($value['label']) .': ';
        $row .= theme('placeholder', $value['data']);
        $row .= "</div>\n";
      }
      $row .= "</div>\n";  // extra div.
    }

    $row .= '<div class="includes">';
    sort($project['includes']);
    $row .= t('Includes: %includes', array('%includes' => implode(', ', $project['includes'])));
    $row .= "</div>\n";

    if (!empty($project['base_themes'])) {
      $row .= '<div class="basethemes">';
      sort($project['base_themes']);
      // We use !dependencies and manually call theme('placeholder') here to
      // avoid breakding the D6 string freeze. This identical string is
      // already in modules/system/system.admin.inc.
      $row .= t('Depends on: !dependencies', array('!dependencies' => theme('placeholder', implode(', ', $project['base_themes']))));
      $row .= "</div>\n";
    }

    if (!empty($project['sub_themes'])) {
      $row .= '<div class="subthemes">';
      sort($project['sub_themes']);
      // We use !required and manually call theme('placeholder') here to avoid
      // breakding the D6 string freeze. This identical string is already in
      // modules/system/system.admin.inc.
      $row .= t('Required by: !required', array('!required' => theme('placeholder', implode(', ', $project['sub_themes']))));
      $row .= "</div>\n";
    }

    $row .= "</div>\n"; // info div.

    if (!isset($rows[$project['project_type']])) {
      $rows[$project['project_type']] = array();
    }
    $row_key = isset($project['title']) ? drupal_strtolower($project['title']) : drupal_strtolower($project['name']);
    $rows[$project['project_type']][$row_key] = array(
      'class' => $class,
      'data' => array($row),
    );
  }

  $project_types = array(
    'core' => t('Drupal core'),
    'module' => t('Modules'),
    'theme' => t('Themes'),
    'disabled-module' => t('Disabled modules'),
    'disabled-theme' => t('Disabled themes'),
  );
  foreach ($project_types as $type_name => $type_label) {
    if (!empty($rows[$type_name])) {
      ksort($rows[$type_name]);
      $output .= "\n<h3>". $type_label ."</h3>\n";
      $output .= theme('table', $header, $rows[$type_name], array('class' => 'update'));
    }
  }
  drupal_add_css(drupal_get_path('module', 'update') .'/update.css');
  return $output;
}

/**
 * Theme the version display of a project.
 *
 * @ingroup themeable
 */
function theme_update_version($version, $tag, $class) {
  $output = '';
  $output .= '<table class="version '. $class .'">';
  $output .= '<tr>';
  $output .= '<td class="version-title">'. $tag ."</td>\n";
  $output .= '<td class="version-details">';
  $output .= l($version['version'], $version['release_link']);
  $output .= ' <span class="version-date">('. format_date($version['date'], 'custom', 'Y-M-d') .')</span>';
  $output .= "</td>\n";
  $output .= '<td class="version-links">';
  $links = array();
  $links['update-download'] = array(
    'title' => t('Download'),
    'href' => $version['download_link'],
  );
  $links['update-release-notes'] = array(
    'title' => t('Release notes'),
    'href' => $version['release_link'],
  );
  $output .= theme('links', $links);
  $output .= '</td>';
  $output .= '</tr>';
  $output .= "</table>\n";
  return $output;
}
