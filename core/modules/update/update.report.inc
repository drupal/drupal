<?php

/**
 * @file
 * Code required only when rendering the available updates report.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;

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

  $variables['last_checked'] = [
    '#theme' => 'update_last_check',
    '#last' => $last,
    // Attach the library to a variable that gets printed always.
    '#attached' => [
      'library' => [
        'update/drupal.update.admin',
      ],
    ],
  ];

  // For no project update data, populate no data message.
  if (empty($data)) {
    $variables['no_updates_message'] = _update_no_data();
  }

  $rows = [];

  foreach ($data as $project) {
    $project_status = [
      '#theme' => 'update_project_status',
      '#project' => $project,
    ];

    // Build project rows.
    if (!isset($rows[$project['project_type']])) {
      $rows[$project['project_type']] = [
        '#type' => 'table',
        '#attributes' => ['class' => ['update']],
      ];
    }
    $row_key = !empty($project['title']) ? mb_strtolower($project['title']) : mb_strtolower($project['name']);

    // Add the project status row and details.
    $rows[$project['project_type']][$row_key]['status'] = $project_status;

    // Add project status class attribute to the table row.
    switch ($project['status']) {
      case UpdateManagerInterface::CURRENT:
        $rows[$project['project_type']][$row_key]['#attributes'] = ['class' => ['color-success']];
        break;
      case UpdateFetcherInterface::UNKNOWN:
      case UpdateFetcherInterface::FETCH_PENDING:
      case UpdateFetcherInterface::NOT_FETCHED:
      case UpdateManagerInterface::NOT_SECURE:
      case UpdateManagerInterface::REVOKED:
      case UpdateManagerInterface::NOT_SUPPORTED:
        $rows[$project['project_type']][$row_key]['#attributes'] = ['class' => ['color-error']];
        break;
      case UpdateFetcherInterface::NOT_CHECKED:
      case UpdateManagerInterface::NOT_CURRENT:
      default:
        $rows[$project['project_type']][$row_key]['#attributes'] = ['class' => ['color-warning']];
        break;
    }
  }

  $project_types = [
    'core' => t('Drupal core'),
    'module' => t('Modules'),
    'theme' => t('Themes'),
    'module-disabled' => t('Uninstalled modules'),
    'theme-disabled' => t('Uninstalled themes'),
  ];

  $variables['project_types'] = [];
  foreach ($project_types as $type_name => $type_label) {
    if (!empty($rows[$type_name])) {
      ksort($rows[$type_name]);
      $variables['project_types'][] = [
        'label' => $type_label,
        'table' => $rows[$type_name],
      ];
    }
  }
}

/**
 * Prepares variables for update version templates.
 *
 * Default template: update-version.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - version: An array of information about the release version.
 */
function template_preprocess_update_version(array &$variables) {
  $version = $variables['version'];
  if (empty($version['core_compatibility_message'])) {
    return;
  }
  $core_compatible = !empty($version['core_compatible']);
  $variables['core_compatibility_details'] = [
    '#type' => 'details',
    '#title' => $core_compatible ? t('Compatible') : t('Not compatible'),
    '#open' => !$core_compatible,
    'message' => [
      '#markup' => $version['core_compatibility_message'],
    ],
    '#attributes' => [
      'class' => [
        $core_compatible ? 'compatible' : 'not-compatible',
      ],
    ],
  ];
}

/**
 * Prepares variables for update project status templates.
 *
 * Default template: update-project-status.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - project: An array of information about the project.
 */
function template_preprocess_update_project_status(&$variables) {
  // Storing by reference because we are sorting the project values.
  $project = &$variables['project'];

  // Set the project title and URL.
  $variables['title'] = (isset($project['title'])) ? $project['title'] : $project['name'];
  $variables['url'] = (isset($project['link'])) ? Url::fromUri($project['link'])->toString() : NULL;

  $variables['install_type'] = $project['install_type'];
  if ($project['install_type'] == 'dev' && !empty($project['datestamp'])) {
    $variables['datestamp'] = \Drupal::service('date.formatter')->format($project['datestamp'], 'custom', 'Y-M-d');
  }

  $variables['existing_version'] = $project['existing_version'];

  $versions_inner = [];
  $security_class = [];
  $version_class = [];
  if (isset($project['recommended'])) {
    if ($project['status'] != UpdateManagerInterface::CURRENT || $project['existing_version'] !== $project['recommended']) {

      // First, figure out what to recommend.
      // If there's only 1 security update and it has the same version we're
      // recommending, give it the same CSS class as if it was recommended,
      // but don't print out a separate "Recommended" line for this project.
      if (!empty($project['security updates'])
        && count($project['security updates']) == 1
        && $project['security updates'][0]['version'] === $project['recommended']
      ) {
        $security_class[] = 'project-update__version--recommended';
        $security_class[] = 'project-update__version---strong';
      }
      else {
        $version_class[] = 'project-update__version--recommended';
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
          $version_class[] = 'project-update__version--recommended-strong';
        }
        $versions_inner[] = [
          '#theme' => 'update_version',
          '#version' => $project['releases'][$project['recommended']],
          '#title' => t('Recommended version:'),
          '#attributes' => ['class' => $version_class],
        ];
      }

      // Now, print any security updates.
      if (!empty($project['security updates'])) {
        $security_class[] = 'version-security';
        foreach ($project['security updates'] as $security_update) {
          $versions_inner[] = [
            '#theme' => 'update_version',
            '#version' => $security_update,
            '#title' => t('Security update:'),
            '#attributes' => ['class' => $security_class],
          ];
        }
      }
    }

    if ($project['recommended'] !== $project['latest_version']) {
      $versions_inner[] = [
        '#theme' => 'update_version',
        '#version' => $project['releases'][$project['latest_version']],
        '#title' => t('Latest version:'),
        '#attributes' => ['class' => ['version-latest']],
      ];
    }
    if ($project['install_type'] == 'dev'
      && $project['status'] != UpdateManagerInterface::CURRENT
      && isset($project['dev_version'])
      && $project['recommended'] !== $project['dev_version']) {
      $versions_inner[] = [
        '#theme' => 'update_version',
        '#version' => $project['releases'][$project['dev_version']],
        '#title' => t('Development version:'),
        '#attributes' => ['class' => ['version-latest']],
      ];
    }
  }

  if (isset($project['also'])) {
    foreach ($project['also'] as $also) {
      $versions_inner[] = [
        '#theme' => 'update_version',
        '#version' => $project['releases'][$also],
        '#title' => t('Also available:'),
        '#attributes' => ['class' => ['version-also-available']],
      ];
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

  $variables['extras'] = [];
  if (!empty($project['extra'])) {
    foreach ($project['extra'] as $value) {
      $extra_item = [];
      $extra_item['attributes'] = new Attribute();
      $extra_item['label'] = $value['label'];
      $extra_item['data'] = [
        '#prefix' => '<em>',
        '#markup' => $value['data'],
        '#suffix' => '</em>',
      ];
      $variables['extras'][] = $extra_item;
    }
  }

  // Set the project status details.
  $status_label = NULL;
  switch ($project['status']) {
    case UpdateManagerInterface::NOT_SECURE:
      $status_label = t('Security update required!');
      break;
    case UpdateManagerInterface::REVOKED:
      $status_label = t('Revoked!');
      break;
    case UpdateManagerInterface::NOT_SUPPORTED:
      $status_label = t('Not supported!');
      break;
    case UpdateManagerInterface::NOT_CURRENT:
      $status_label = t('Update available');
      break;
    case UpdateManagerInterface::CURRENT:
      $status_label = t('Up to date');
      break;
  }
  $variables['status']['label'] = $status_label;
  $variables['status']['attributes'] = new Attribute();
  $variables['status']['reason'] = (isset($project['reason'])) ? $project['reason'] : NULL;

  switch ($project['status']) {
    case UpdateManagerInterface::CURRENT:
      $uri = 'core/misc/icons/73b355/check.svg';
      $text = t('Ok');
      break;
    case UpdateFetcherInterface::UNKNOWN:
    case UpdateFetcherInterface::FETCH_PENDING:
    case UpdateFetcherInterface::NOT_FETCHED:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Warning');
      break;
    case UpdateManagerInterface::NOT_SECURE:
    case UpdateManagerInterface::REVOKED:
    case UpdateManagerInterface::NOT_SUPPORTED:
      $uri = 'core/misc/icons/e32700/error.svg';
      $text = t('Error');
      break;
    case UpdateFetcherInterface::NOT_CHECKED:
    case UpdateManagerInterface::NOT_CURRENT:
    default:
      $uri = 'core/misc/icons/e29700/warning.svg';
      $text = t('Warning');
      break;
  }

  $variables['status']['icon'] = [
    '#theme' => 'image',
    '#width' => 18,
    '#height' => 18,
    '#uri' => $uri,
    '#alt' => $text,
    '#title' => $text,
  ];
}
