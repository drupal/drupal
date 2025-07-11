<?php

namespace Drupal\system\Hook;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for system.
 */
class SystemThemeHooks {

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
