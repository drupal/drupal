<?php

/**
 * @file
 * Code required only when rendering the available updates report.
 */

/**
 * Returns HTML for the project status report.
 *
 * @param array $variables
 *   An associative array containing:
 *   - data: An array of data about each project's status.
 *
 * @ingroup themeable
 */
function theme_update_report($variables) {
  $data = $variables['data'];

  $last = \Drupal::state()->get('update.last_check') ?: 0;
  $update_last_check = array(
    '#theme' => 'update_last_check',
    '#last' => $last,
  );
  $output = drupal_render($update_last_check);

  if (!is_array($data)) {
    $output .= '<p>' . $data . '</p>';
    return $output;
  }

  $header = array();
  $rows = array();

  // Create an array of status values keyed by module or theme name, since
  // we'll need this while generating the report if we have to cross reference
  // anything (e.g. subthemes which have base themes missing an update).
  foreach ($data as $project) {
    foreach ($project['includes'] as $key => $name) {
      $status[$key] = $project['status'];
    }
  }

  foreach ($data as $project) {
    switch ($project['status']) {
      case UPDATE_CURRENT:
        $class = 'ok';
        $uri = 'core/misc/watchdog-ok.png';
        $text = t('ok');
        break;
      case UPDATE_UNKNOWN:
      case UPDATE_FETCH_PENDING:
      case UPDATE_NOT_FETCHED:
        $class = 'unknown';
        $uri = 'core/misc/watchdog-warning.png';
        $text = t('warning');
        break;
      case UPDATE_NOT_SECURE:
      case UPDATE_REVOKED:
      case UPDATE_NOT_SUPPORTED:
        $class = 'error';
        $uri = 'core/misc/watchdog-error.png';
        $text = t('error');
        break;
      case UPDATE_NOT_CHECKED:
      case UPDATE_NOT_CURRENT:
      default:
        $class = 'warning';
        $uri = 'core/misc/watchdog-warning.png';
        $text = t('warning');
        break;
    }

    $icon = array(
      '#theme' => 'image',
      '#width' => 18,
      '#height' => 18,
      '#uri' => $uri,
      '#alt' => $text,
      '#title' => $text,
    );

    $row = '<div class="version-status">';
    $update_status_label = array('#theme' => 'update_status_label', '#status' => $project['status']);
    $status_label = drupal_render($update_status_label);
    $row .= !empty($status_label) ? $status_label : check_plain($project['reason']);
    $row .= '<span class="icon">' . drupal_render($icon) . '</span>';
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
    $row .= ' ' . check_plain($project['existing_version']);
    if ($project['install_type'] == 'dev' && !empty($project['datestamp'])) {
      $row .= ' <span class="version-date">(' . format_date($project['datestamp'], 'custom', 'Y-M-d') . ')</span>';
    }
    $row .= "</div>\n";

    $versions_inner = '';
    $security_class = array();
    $version_class = array();
    if (isset($project['recommended'])) {
      if ($project['status'] != UPDATE_CURRENT || $project['existing_version'] !== $project['recommended']) {

        // First, figure out what to recommend.
        // If there's only 1 security update and it has the same version we're
        // recommending, give it the same CSS class as if it was recommended,
        // but don't print out a separate "Recommended" line for this project.
        if (!empty($project['security updates']) && count($project['security updates']) == 1 && $project['security updates'][0]['version'] === $project['recommended']) {
          $security_class[] = 'version-recommended';
          $security_class[] = 'version-recommended-strong';
        }
        else {
          $version_class[] = 'version-recommended';
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
            $version_class[] = 'version-recommended-strong';
          }
          $update_version = array(
            '#theme' => 'update_version',
            '#version' => $project['releases'][$project['recommended']],
            '#tag' => t('Recommended version:'),
            '#class' => $version_class,
          );
          $versions_inner .= drupal_render($update_version);
        }

        // Now, print any security updates.
        if (!empty($project['security updates'])) {
          $security_class[] = 'version-security';
          foreach ($project['security updates'] as $security_update) {
            $update_version = array(
              '#theme' => 'update_version',
              '#version' => $security_update,
              '#tag' => t('Security update:'),
              '#class' => $security_class,
            );
            $versions_inner .= drupal_render($update_version);
          }
        }
      }

      if ($project['recommended'] !== $project['latest_version']) {
        $update_version = array(
          '#theme' => 'update_version',
          '#version' => $project['releases'][$project['latest_version']],
          '#tag' => t('Latest version:'),
          '#class' => array('version-latest'),
        );
        $versions_inner .= drupal_render($update_version);
      }
      if ($project['install_type'] == 'dev'
          && $project['status'] != UPDATE_CURRENT
          && isset($project['dev_version'])
          && $project['recommended'] !== $project['dev_version']) {
        $update_version = array(
          '#theme' => 'update_version',
          '#version' => $project['releases'][$project['dev_version']],
          '#tag' => t('Development version:'),
          '#class' => array('version-latest'),
        );
        $versions_inner .= drupal_render($update_version);
      }
    }

    if (isset($project['also'])) {
      foreach ($project['also'] as $also) {
        $update_version = array(
          '#theme' => 'update_version',
          '#version' => $project['releases'][$also],
          '#tag' => t('Also available:'),
          '#class' => array('version-also-available'),
        );
        $versions_inner .= drupal_render($update_version);
      }
    }

    if (!empty($versions_inner)) {
      $row .= "<div class=\"versions\">\n" . $versions_inner . "</div>\n";
    }
    $row .= "<div class=\"info\">\n";
    if (!empty($project['extra'])) {
      $row .= '<div class="extra">' . "\n";
      foreach ($project['extra'] as $value) {
        $row .= '<div class="' . implode(' ', $value['class']) . '">';
        $row .= check_plain($value['label']) . ': ';
        $row .= drupal_placeholder($value['data']);
        $row .= "</div>\n";
      }
      $row .= "</div>\n";  // extra div.
    }

    $row .= '<div class="includes">';
    sort($project['includes']);
    if (!empty($project['disabled'])) {
      sort($project['disabled']);
      // Make sure we start with a clean slate for each project in the report.
      $includes_items = array();
      $row .= t('Includes:');
      $includes_items[] = t('Enabled: %includes', array('%includes' => implode(', ', $project['includes'])));
      $includes_items[] = t('Disabled: %disabled', array('%disabled' => implode(', ', $project['disabled'])));
      $item_list = array(
        '#theme' => 'item_list',
        '#items' => $includes_items,
      );
      $row .= drupal_render($item_list);
    }
    else {
      $row .= t('Includes: %includes', array('%includes' => implode(', ', $project['includes'])));
    }
    $row .= "</div>\n";

    if (!empty($project['base_themes'])) {
      $row .= '<div class="basethemes">';
      asort($project['base_themes']);
      $base_themes = array();
      foreach ($project['base_themes'] as $base_key => $base_theme) {
        $update_status_label = array(
          '#theme' => 'update_status_label',
          '#status' => $status[$base_key],
        );
        switch ($status[$base_key]) {
          case UPDATE_NOT_SECURE:
          case UPDATE_REVOKED:
          case UPDATE_NOT_SUPPORTED:
            $base_themes[] = t('%base_theme (!base_label)', array('%base_theme' => $base_theme, '!base_label' => drupal_render($update_status_label)));
            break;

          default:
            $base_themes[] = drupal_placeholder($base_theme);
        }
      }
      $row .= t('Depends on: !basethemes', array('!basethemes' => implode(', ', $base_themes)));
      $row .= "</div>\n";
    }

    if (!empty($project['sub_themes'])) {
      $row .= '<div class="subthemes">';
      sort($project['sub_themes']);
      $row .= t('Required by: %subthemes', array('%subthemes' => implode(', ', $project['sub_themes'])));
      $row .= "</div>\n";
    }

    $row .= "</div>\n"; // info div.

    if (!isset($rows[$project['project_type']])) {
      $rows[$project['project_type']] = array();
    }
    $row_key = isset($project['title']) ? drupal_strtolower($project['title']) : drupal_strtolower($project['name']);
    $rows[$project['project_type']][$row_key] = array(
      'class' => array($class),
      'data' => array($row),
    );
  }

  $project_types = array(
    'core' => t('Drupal core'),
    'module' => t('Modules'),
    'theme' => t('Themes'),
    'module-disabled' => t('Disabled modules'),
    'theme-disabled' => t('Disabled themes'),
  );
  foreach ($project_types as $type_name => $type_label) {
    if (!empty($rows[$type_name])) {
      ksort($rows[$type_name]);
      $output .= "\n<h3>" . $type_label . "</h3>\n";
      $table = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows[$type_name],
        '#attributes' => array(
          'class' => array('update'),
        ),
      );
      $output .= drupal_render($table);
    }
  }

  $assets = array(
    '#attached' => array(
      'library' => array(
        'update/drupal.update.admin',
      ),
    )
  );
  drupal_render($assets);

  return $output;
}

/**
 * Returns HTML for a label to display for a project's update status.
 *
 * @param array $variables
 *   An associative array containing:
 *   - status: The integer code for a project's current update status.
 *
 * @see update_calculate_project_data()
 * @ingroup themeable
 */
function theme_update_status_label($variables) {
  switch ($variables['status']) {
    case UPDATE_NOT_SECURE:
      return '<span class="security-error">' . t('Security update required!') . '</span>';

    case UPDATE_REVOKED:
      return '<span class="revoked">' . t('Revoked!') . '</span>';

    case UPDATE_NOT_SUPPORTED:
      return '<span class="not-supported">' . t('Not supported!') . '</span>';

    case UPDATE_NOT_CURRENT:
      return '<span class="not-current">' . t('Update available') . '</span>';

    case UPDATE_CURRENT:
      return '<span class="current">' . t('Up to date') . '</span>';

  }
}

/**
 * Returns HTML for the version display of a project.
 *
 * @param array $variables
 *   An associative array containing:
 *   - version: An array of data about the latest released version, containing:
 *     - version: The version number.
 *     - release_link: The URL for the release notes.
 *     - date: The date of the release.
 *     - download_link: The URL for the downloadable file.
 *   - tag: The title of the project.
 *   - class: A string containing extra classes for the wrapping table.
 *
 * @ingroup themeable
 */
function theme_update_version($variables) {
  $version = $variables['version'];
  $tag = $variables['tag'];
  $class = implode(' ', $variables['class']);

  $output = '';
  $output .= '<table class="version ' . $class . '">';
  $output .= '<tr>';
  $output .= '<td class="version-title">' . $tag . "</td>\n";
  $output .= '<td class="version-details">';
  $output .= l($version['version'], $version['release_link']);
  $output .= ' <span class="version-date">(' . format_date($version['date'], 'custom', 'Y-M-d') . ')</span>';
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
  $links__update_version = array(
    '#theme' => 'links__update_version',
    '#links' => $links,
  );
  $output .= drupal_render($links__update_version);
  $output .= '</td>';
  $output .= '</tr>';
  $output .= "</table>\n";
  return $output;
}
