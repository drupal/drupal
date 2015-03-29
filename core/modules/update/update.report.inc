<?php

/**
 * @file
 * Code required only when rendering the available updates report.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Prepares variables for project status report templates.
 *
 * Default template: update-report.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - data: An array of data about each project's status.
 */
function template_preprocess_update_report(&$variables) {
  $data = $variables['data'];

  $last = \Drupal::state()->get('update.last_check') ?: 0;

  $variables['last_checked'] = array(
    '#theme' => 'update_last_check',
    '#last' => $last,
    // Attach the library to a variable that gets printed always.
    '#attached' => array(
      'library' => array(
        'update/drupal.update.admin',
      ),
    )
  );

  // For no project update data, populate no data message.
  if (empty($data)) {
    $variables['no_updates_message'] = _update_no_data();
  }

  $status = array();

  // Create an array of status values keyed by module or theme name, since
  // we'll need this while generating the report if we have to cross reference
  // anything (e.g. subthemes which have base themes missing an update).
  foreach ($data as $project) {
    foreach ($project['includes'] as $key => $name) {
      $status[$key] = $project['status'];
    }
  }

  $rows = array();

  foreach ($data as $project) {
    $project_status = array(
      '#theme' =>  'update_project_status',
      '#project' => $project,
      '#includes_status' => $status,
    );

    // Build project rows.
    if (!isset($rows[$project['project_type']])) {
      $rows[$project['project_type']] = array(
        '#type' => 'table',
        '#attributes' => array('class' => array('update')),
      );
    }
    $row_key = !empty($project['title']) ? Unicode::strtolower($project['title']) : Unicode::strtolower($project['name']);

    // Add the project status row and details.
    $rows[$project['project_type']][$row_key]['status'] = $project_status;

    // Add project status class attribute to the table row.
    switch ($project['status']) {
      case UPDATE_CURRENT:
        $rows[$project['project_type']][$row_key]['#attributes'] = array('class' => array('ok'));
        break;
      case UPDATE_UNKNOWN:
      case UPDATE_FETCH_PENDING:
      case UPDATE_NOT_FETCHED:
        $rows[$project['project_type']][$row_key]['#attributes'] = array('class' => array('unknown'));
        break;
      case UPDATE_NOT_SECURE:
      case UPDATE_REVOKED:
      case UPDATE_NOT_SUPPORTED:
        $rows[$project['project_type']][$row_key]['#attributes'] = array('class' => array('error'));
        break;
      case UPDATE_NOT_CHECKED:
      case UPDATE_NOT_CURRENT:
      default:
        $rows[$project['project_type']][$row_key]['#attributes'] = array('class' => array('warning'));
        break;
    }
  }

  $project_types = array(
    'core' => t('Drupal core'),
    'module' => t('Modules'),
    'theme' => t('Themes'),
    'module-disabled' => t('Disabled modules'),
    'theme-disabled' => t('Disabled themes'),
  );

  $variables['project_types'] = array();
  foreach ($project_types as $type_name => $type_label) {
    if (!empty($rows[$type_name])) {
      ksort($rows[$type_name]);
      $variables['project_types'][] = array(
        'label' => $type_label,
        'table' => $rows[$type_name],
      );
    }
  }
}

/**
 * Prepares variables for update project status templates.
 *
 * Default template: update-project-status.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - project: An array of information about the project.
 *   - includes_status: An array of sub-project statuses where the keys are the
 *     shortnames of each project and the values are UPDATE_* integer constants
 *     as defined in update.module.
 */
function template_preprocess_update_project_status(&$variables) {
  // Storing by reference because we are sorting the project values.
  $project = &$variables['project'];
  $includes_status = $variables['includes_status'];

  // Set the project title and URL.
  $variables['title'] = (isset($project['title'])) ? $project['title'] : $project['name'];
  $variables['url'] = (isset($project['link'])) ? Url::fromUri($project['link'])->toString() : NULL;

  $variables['install_type'] = $project['install_type'];
  if ($project['install_type'] == 'dev' && !empty($project['datestamp'])) {
    $variables['datestamp'] = format_date($project['datestamp'], 'custom', 'Y-M-d');
  }

  $variables['existing_version'] = $project['existing_version'];

  $versions_inner = array();
  $security_class = array();
  $version_class = array();
  if (isset($project['recommended'])) {
    if ($project['status'] != UPDATE_CURRENT || $project['existing_version'] !== $project['recommended']) {

      // First, figure out what to recommend.
      // If there's only 1 security update and it has the same version we're
      // recommending, give it the same CSS class as if it was recommended,
      // but don't print out a separate "Recommended" line for this project.
      if (!empty($project['security updates'])
        && count($project['security updates']) == 1
        && $project['security updates'][0]['version'] === $project['recommended']
      ) {
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
        $versions_inner[] = array(
          '#theme' => 'update_version',
          '#version' => $project['releases'][$project['recommended']],
          '#title' => t('Recommended version:'),
          '#attributes' => array('class' => $version_class),
        );
      }

      // Now, print any security updates.
      if (!empty($project['security updates'])) {
        $security_class[] = 'version-security';
        foreach ($project['security updates'] as $security_update) {
          $versions_inner[] = array(
            '#theme' => 'update_version',
            '#version' => $security_update,
            '#title' => t('Security update:'),
            '#attributes' => array('class' => $security_class),
          );
        }
      }
    }

    if ($project['recommended'] !== $project['latest_version']) {
      $versions_inner[] = array(
        '#theme' => 'update_version',
        '#version' => $project['releases'][$project['latest_version']],
        '#title' => t('Latest version:'),
        '#attributes' => array('class' => array('version-latest')),
      );
    }
    if ($project['install_type'] == 'dev'
      && $project['status'] != UPDATE_CURRENT
      && isset($project['dev_version'])
      && $project['recommended'] !== $project['dev_version']) {
      $versions_inner[] = array(
        '#theme' => 'update_version',
        '#version' => $project['releases'][$project['dev_version']],
        '#title' => t('Development version:'),
        '#attributes' => array('class' => array('version-latest')),
      );
    }
  }

  if (isset($project['also'])) {
    foreach ($project['also'] as $also) {
      $versions_inner[] = array(
        '#theme' => 'update_version',
        '#version' => $project['releases'][$also],
        '#title' => t('Also available:'),
        '#attributes' => array('class' => array('version-also-available')),
      );
    }
  }

  if (!empty($versions_inner)) {
    $variables['versions'] = $versions_inner;
  }

  if (!empty($project['disabled'])) {
    sort($project['disabled']);
    $variables['disabled'] = $project['disabled'];
  }

  sort($project['includes']);
  $variables['includes'] = $project['includes'];

  $variables['extras'] = array();
  if (!empty($project['extra'])) {
    foreach ($project['extra'] as $value) {
      $extra_item = array();
      $extra_item['attributes'] = new Attribute();
      $extra_item['label'] = $value['label'];
      $extra_item['data'] = drupal_placeholder($value['data']);
      $variables['extras'][] = $extra_item;
    }
  }

  if (!empty($project['base_themes'])) {
    asort($project['base_themes']);
    $base_themes = array();
    foreach ($project['base_themes'] as $base_key => $base_theme) {
      switch ($includes_status[$base_key]) {
        case UPDATE_NOT_SECURE:
          $base_status_label = t('Security update required!');
          break;
        case UPDATE_REVOKED:
          $base_status_label = t('Revoked!');
          break;
        case UPDATE_NOT_SUPPORTED:
          $base_status_label = t('Not supported!');
          break;
        default:
          $base_status_label = '';
      }

      if ($base_status_label) {
        $base_themes[] = t('%base_theme (!base_label)', array(
          '%base_theme' => $base_theme,
          '!base_label' => $base_status_label,
        ));
      }
      else {
        $base_themes[] = drupal_placeholder($base_theme);
      }
    }
    $variables['base_themes'] = $base_themes;
  }

  if (!empty($project['sub_themes'])) {
    sort($project['sub_themes']);
    $variables['sub_themes'] = $project['sub_themes'];
  }

  // Set the project status details.
  $status_label = NULL;
  switch ($project['status']) {
    case UPDATE_NOT_SECURE:
      $status_label = t('Security update required!');
      break;
    case UPDATE_REVOKED:
      $status_label = t('Revoked!');
      break;
    case UPDATE_NOT_SUPPORTED:
      $status_label = t('Not supported!');
      break;
    case UPDATE_NOT_CURRENT:
      $status_label = t('Update available');
      break;
    case UPDATE_CURRENT:
      $status_label = t('Up to date');
      break;
  }
  $variables['status']['label'] = $status_label;
  $variables['status']['attributes'] = new Attribute();
  $variables['status']['reason'] = (isset($project['reason'])) ? $project['reason'] : NULL;

  switch ($project['status']) {
    case UPDATE_CURRENT:
      $uri = 'core/misc/icons/73b355/check.svg';
      $text = t('Ok');
      break;
    case UPDATE_UNKNOWN:
    case UPDATE_FETCH_PENDING:
    case UPDATE_NOT_FETCHED:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Warning');
      break;
    case UPDATE_NOT_SECURE:
    case UPDATE_REVOKED:
    case UPDATE_NOT_SUPPORTED:
      $uri = 'core/misc/icons/ea2800/error.svg';
      $text = t('Error');
      break;
    case UPDATE_NOT_CHECKED:
    case UPDATE_NOT_CURRENT:
    default:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Warning');
      break;
  }

  $variables['status']['icon'] = array(
    '#theme' => 'image',
    '#width' => 18,
    '#height' => 18,
    '#uri' => $uri,
    '#alt' => $text,
    '#title' => $text,
  );
}
