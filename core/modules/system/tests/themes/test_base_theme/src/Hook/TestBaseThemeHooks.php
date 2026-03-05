<?php

declare(strict_types=1);

namespace Drupal\test_base_theme\Hook;

use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for test_base_theme.
 */
class TestBaseThemeHooks {

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    // We append the function name to the title for test to check for.
    $view->setTitle($view->getTitle() . ":" . 'test_base_theme_views_pre_render');
  }

  /**
   * Implements hook_views_post_render().
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, &$output, CachePluginBase $cache): void {
    // We append the function name to the title for test to check for.
    $view->setTitle($view->getTitle() . ":" . 'test_base_theme_views_post_render');
  }

  /**
   * Implements hook_preprocess_HOOK() for theme_test_template_test templates.
   */
  #[Hook('preprocess_theme_test_template_test')]
  public function preprocessThemeTestTemplateTest(&$variables): void {
  }

  /**
   * Implements hook_preprocess_HOOK() for theme_test_function_suggestions theme functions.
   */
  #[Hook('preprocess_theme_test_function_suggestions')]
  public function preprocessThemeTestFunctionSuggestions(&$variables): void {
  }

}
