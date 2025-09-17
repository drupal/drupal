<?php

namespace Drupal\system\Hook;

use Drupal\Core\Theme\ThemeCommonElements;
use Drupal\system\Theme\SystemAdminThemePreprocess;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for system.
 */
class SystemThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    $themeCommonElements = ThemeCommonElements::commonElements();

    $systemTheme = [
      // Normally theme suggestion templates are only picked up when they are in
      // themes. We explicitly define theme suggestions here so that the block
      // templates in core/modules/system/templates are picked up.
      'block__system_branding_block' => [
        'render element' => 'elements',
        'base hook' => 'block',
      ],
      'block__system_messages_block' => [
        'base hook' => 'block',
      ],
      'block__system_menu_block' => [
        'render element' => 'elements',
        'base hook' => 'block',
      ],
      'system_themes_page' => [
        'variables' => [
          'theme_groups' => [],
          'theme_group_titles' => [],
        ],
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessSystemThemesPage',
      ],
      'system_config_form' => [
        'render element' => 'form',
      ],
      'confirm_form' => [
        'render element' => 'form',
      ],
      'system_modules_details' => [
        'render element' => 'form',
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessSystemModulesDetails',
      ],
      'system_modules_uninstall' => [
        'render element' => 'form',
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessSystemModulesUninstall',
      ],
      'status_report_page' => [
        'variables' => [
          'counters' => [],
          'general_info' => [],
          'requirements' => NULL,
        ],
      ],
      'status_report' => [
        'variables' => [
          'grouped_requirements' => NULL,
          'requirements' => NULL,
        ],
      ],
      'status_report_counter' => [
        'variables' => [
          'amount' => NULL,
          'text' => NULL,
          'severity' => NULL,
        ],
      ],
      'status_report_general_info' => [
        'variables' => [
          'drupal' => [],
          'cron' => [],
          'database_system' => [],
          'database_system_version' => [],
          'php' => [],
          'php_memory_limit' => [],
          'webserver' => [],
        ],
      ],
      'admin_page' => [
        'variables' => [
          'blocks' => NULL,
        ],
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessAdminPage',
      ],
      'admin_block' => [
        'variables' => [
          'block' => NULL,
          'attributes' => [],
        ],
      ],
      'admin_block_content' => [
        'variables' => [
          'content' => NULL,
        ],
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessAdminBlockContent',
      ],
      'system_admin_index' => [
        'variables' => [
          'menu_items' => NULL,
        ],
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessSystemAdminIndex',
      ],
      'entity_add_list' => [
        'variables' => [
          'bundles' => [],
          'add_bundle_message' => NULL,
        ],
        'template' => 'entity-add-list',
        'initial preprocess' => static::class . ':preprocessEntityAddList',
      ],
      'system_security_advisories_fetch_error_message' => [
        'variables' => [
          'error_message' => [],
        ],
        'initial preprocess' => SystemAdminThemePreprocess::class . ':preprocessSystemSecurityAdvisoriesFetchErrorMessage',
      ],
      'entity_page_title' => [
        'variables' => [
          'attributes' => [],
          'title' => NULL,
          'entity' => NULL,
          'view_mode' => NULL,
        ],
      ],
    ];

    return array_merge($themeCommonElements, $systemTheme);
  }

  /**
   * Prepares variables for the list of available bundles.
   *
   * Default template: entity-add-list.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - bundles: An array of bundles with the label, description, add_link
   *     keys.
   *   - add_bundle_message: The message shown when there are no bundles. Only
   *     available if the entity type uses bundle entities.
   */
  public function preprocessEntityAddList(array &$variables): void {
    foreach ($variables['bundles'] as $bundle_name => $bundle_info) {
      $variables['bundles'][$bundle_name]['description'] = [
        '#markup' => $bundle_info['description'],
      ];
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_html')]
  public function themeSuggestionsHtml(array $variables): array {
    $path_args = explode('/', trim(\Drupal::service('path.current')->getPath(), '/'));
    return theme_get_suggestions($path_args, 'html');
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_page')]
  public function themeSuggestionsPage(array $variables): array {
    $path_args = explode('/', trim(\Drupal::service('path.current')->getPath(), '/'));
    $suggestions = theme_get_suggestions($path_args, 'page');
    $supported_http_error_codes = [
      401,
      403,
      404,
    ];
    $exception = \Drupal::requestStack()->getCurrentRequest()->attributes->get('exception');
    if ($exception instanceof HttpExceptionInterface && in_array($exception->getStatusCode(), $supported_http_error_codes, TRUE)) {
      $suggestions[] = 'page__4xx';
      $suggestions[] = 'page__' . $exception->getStatusCode();
    }
    return $suggestions;
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_maintenance_page')]
  public function themeSuggestionsMaintenancePage(array $variables): array {
    $suggestions = [];
    // Dead databases will show error messages so supplying this template will
    // allow themers to override the page and the content completely.
    $offline = defined('MAINTENANCE_MODE');
    try {
      \Drupal::service('path.matcher')->isFrontPage();
    }
    catch (\Exception) {
      // The database is not yet available.
      $offline = TRUE;
    }
    if ($offline) {
      $suggestions[] = 'maintenance_page__offline';
    }
    return $suggestions;
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_region')]
  public function themeSuggestionsRegion(array $variables): array {
    $suggestions = [];
    if (!empty($variables['elements']['#region'])) {
      $suggestions[] = 'region__' . $variables['elements']['#region'];
    }
    return $suggestions;
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_field')]
  public function themeSuggestionsField(array $variables): array {
    $suggestions = [];
    $element = $variables['element'];
    $suggestions[] = 'field__' . $element['#field_type'];
    $suggestions[] = 'field__' . $element['#field_name'];
    $suggestions[] = 'field__' . $element['#entity_type'] . '__' . $element['#bundle'];
    $suggestions[] = 'field__' . $element['#entity_type'] . '__' . $element['#field_name'];
    $suggestions[] = 'field__' . $element['#entity_type'] . '__' . $element['#field_name'] . '__' . $element['#bundle'];
    return $suggestions;
  }

  /**
   * @} End of "defgroup authorize".
   */

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    switch ($variables['base_plugin_id']) {
      case 'system_branding_block':
        $variables['site_logo'] = '';
        if ($variables['content']['site_logo']['#access'] && $variables['content']['site_logo']['#uri']) {
          $variables['site_logo'] = $variables['content']['site_logo']['#uri'];
        }
        $variables['site_name'] = '';
        if ($variables['content']['site_name']['#access'] && $variables['content']['site_name']['#markup']) {
          $variables['site_name'] = $variables['content']['site_name']['#markup'];
        }
        $variables['site_slogan'] = '';
        if ($variables['content']['site_slogan']['#access'] && $variables['content']['site_slogan']['#markup']) {
          $variables['site_slogan'] = [
            '#markup' => $variables['content']['site_slogan']['#markup'],
          ];
        }
        break;
    }
  }

  /**
   * Implements hook_preprocess_toolbar().
   */
  #[Hook('preprocess_toolbar')]
  public function preprocessToolbar(array &$variables, $hook, $info): void {
    // When Claro is the admin theme, Claro overrides the active theme's if that
    // active theme is not Claro. Because of these potential overrides, the
    // toolbar cache should be invalidated any time the default or admin theme
    // changes.
    $variables['#cache']['tags'][] = 'config:system.theme';
    // If Claro is the admin theme but not the active theme, still include
    // Claro's toolbar preprocessing.
    if (_system_is_claro_admin_and_not_active()) {
      require_once DRUPAL_ROOT . '/core/themes/claro/claro.theme';
      claro_preprocess_toolbar($variables, $hook, $info);
    }
  }

}
