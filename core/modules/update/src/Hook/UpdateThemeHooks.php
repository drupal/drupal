<?php

namespace Drupal\update\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\update\ProjectRelease;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;

/**
 * Theme hooks for update module.
 */
class UpdateThemeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected StateInterface $state,
    protected DateFormatterInterface $dateFormatter,
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
    protected RedirectDestinationInterface $redirectDestination,
  ) {

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'update_last_check' => [
        'initial preprocess' => static::class . ':preprocessUpdateLastCheck',
        'variables' => [
          'last' => 0,
        ],
      ],
      'update_report' => [
        'initial preprocess' => static::class . ':preprocessUpdateReport',
        'variables' => [
          'data' => NULL,
        ],
      ],
      'update_project_status' => [
        'initial preprocess' => static::class . ':preprocessUpdateProjectStatus',
        'variables' => [
          'project' => [],
        ],
      ],
      // We are using template instead of '#type' => 'table' here to keep markup
      // out of preprocess and allow for easier changes to markup.
      'update_version' => [
        'initial preprocess' => static::class . ':preprocessUpdateVersion',
        'variables' => [
          'version' => NULL,
          'title' => NULL,
          'attributes' => [],
        ],
      ],
      'update_fetch_error_message' => [
        'initial preprocess' => static::class . ':preprocessUpdateFetchErrorMessage',
        'render element' => 'element',
        'variables' => [
          'error_message' => [],
        ],
      ],
    ];
  }

  /**
   * Prepares variables for last time update data was checked templates.
   *
   * Default template: update-last-check.html.twig.
   *
   * In addition to properly formatting the given timestamp, this function also
   * provides a "Check manually" link that refreshes the available update and
   * redirects back to the same page.
   *
   * @param array $variables
   *   An associative array containing:
   *   - last: The timestamp when the site last checked for available updates.
   *
   * @see theme_update_report()
   */
  public function preprocessUpdateLastCheck(array &$variables): void {
    $variables['time'] = $this->dateFormatter->formatTimeDiffSince($variables['last']);
    $variables['link'] = Link::fromTextAndUrl($this->t('Check manually'), Url::fromRoute('update.manual_status', [], ['query' => $this->redirectDestination->getAsArray()]))->toString();
  }

  /**
   * Prepares variables for project status report templates.
   *
   * Default template: update-report.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - data: An array of data about each project's status.
   */
  public function preprocessUpdateReport(array &$variables): void {
    $data = isset($variables['data']) && is_array($variables['data']) ? $variables['data'] : [];

    $last = $this->state->get('update.last_check', 0);

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
      'core' => $this->t('Drupal core'),
      'module' => $this->t('Modules'),
      'theme' => $this->t('Themes'),
      'module-uninstalled' => $this->t('Uninstalled modules'),
      'theme-uninstalled' => $this->t('Uninstalled themes'),
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
  public function preprocessUpdateVersion(array &$variables): void {
    $release = ProjectRelease::createFromArray($variables['version']);
    if (!$release->getCoreCompatibilityMessage()) {
      return;
    }
    $core_compatible = $release->isCoreCompatible();
    $variables['core_compatibility_details'] = [
      '#type' => 'details',
      '#title' => $core_compatible ? $this->t('Compatible') : $this->t('Not compatible'),
      '#open' => !$core_compatible,
      'message' => [
        '#markup' => $release->getCoreCompatibilityMessage(),
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
  public function preprocessUpdateProjectStatus(array &$variables): void {
    // Storing by reference because we are sorting the project values.
    $project = &$variables['project'];

    // Set the project title and URL.
    $variables['title'] = (isset($project['title'])) ? $project['title'] : $project['name'];
    $variables['url'] = (isset($project['link'])) ? Url::fromUri($project['link'])->toString() : NULL;

    $variables['install_type'] = $project['install_type'];
    if ($project['install_type'] == 'dev' && !empty($project['datestamp'])) {
      $variables['datestamp'] = $this->dateFormatter->format($project['datestamp'], 'custom', 'Y-M-d');
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
            '#title' => $this->t('Recommended version:'),
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
              '#title' => $this->t('Security update:'),
              '#attributes' => ['class' => $security_class],
            ];
          }
        }
      }

      if ($project['recommended'] !== $project['latest_version']) {
        $versions_inner[] = [
          '#theme' => 'update_version',
          '#version' => $project['releases'][$project['latest_version']],
          '#title' => $this->t('Latest version:'),
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
          '#title' => $this->t('Development version:'),
          '#attributes' => ['class' => ['version-latest']],
        ];
      }
    }

    if (isset($project['also'])) {
      foreach ($project['also'] as $also) {
        $versions_inner[] = [
          '#theme' => 'update_version',
          '#version' => $project['releases'][$also],
          '#title' => $this->t('Also available:'),
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
        $status_label = $this->t('Security update required!');
        break;

      case UpdateManagerInterface::REVOKED:
        $status_label = $this->t('Revoked!');
        break;

      case UpdateManagerInterface::NOT_SUPPORTED:
        $status_label = $this->t('Not supported!');
        break;

      case UpdateManagerInterface::NOT_CURRENT:
        $status_label = $this->t('Update available');
        break;

      case UpdateManagerInterface::CURRENT:
        $status_label = $this->t('Up to date');
        break;
    }
    $variables['status']['label'] = $status_label;
    $variables['status']['attributes'] = new Attribute();
    $variables['status']['reason'] = (isset($project['reason'])) ? $project['reason'] : NULL;

    switch ($project['status']) {
      case UpdateManagerInterface::CURRENT:
        $uri = 'core/misc/icons/73b355/check.svg';
        $text = $this->t('Ok');
        break;

      case UpdateFetcherInterface::UNKNOWN:
      case UpdateFetcherInterface::FETCH_PENDING:
      case UpdateFetcherInterface::NOT_FETCHED:
        $uri = 'core/misc/icons/e29700/warning.svg';
        $text = $this->t('Warning');
        break;

      case UpdateManagerInterface::NOT_SECURE:
      case UpdateManagerInterface::REVOKED:
      case UpdateManagerInterface::NOT_SUPPORTED:
        $uri = 'core/misc/icons/e32700/error.svg';
        $text = $this->t('Error');
        break;

      case UpdateFetcherInterface::NOT_CHECKED:
      case UpdateManagerInterface::NOT_CURRENT:
      default:
        $uri = 'core/misc/icons/e29700/warning.svg';
        $text = $this->t('Warning');
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

  /**
   * Prepares variables for update fetch error message templates.
   *
   * Default template: update-fetch-error-message.html.twig.
   *
   * @param array $variables
   *   An associative array of template variables.
   */
  public function preprocessUpdateFetchErrorMessage(array &$variables): void {
    $variables['error_message'] = [
      'message' => [
        '#markup' => $this->t('Failed to fetch available update data:'),
      ],
      'items' => [
        '#theme' => 'item_list',
        '#items' => [
          'documentation_link' => $this->t('See <a href="@url">PHP OpenSSL requirements</a> in the Drupal.org handbook for possible reasons this could happen and what you can do to resolve them.', ['@url' => 'https://www.drupal.org/node/3170647']),
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('dblog') && $this->currentUser->hasPermission('access site reports')) {
      $options = ['query' => ['type' => ['update']]];
      $dblog_url = Url::fromRoute('dblog.overview', [], $options);
      $variables['error_message']['items']['#items']['dblog'] = $this->t('Check <a href="@url">your local system logs</a> for additional error messages.', ['@url' => $dblog_url->toString()]);
    }
    else {
      $variables['error_message']['items']['#items']['logs'] = $this->t('Check your local system logs for additional error messages.');
    }

  }

}
