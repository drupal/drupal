<?php

declare(strict_types=1);

namespace Drupal\test_subtheme\Hook;

use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for test_subtheme.
 */
class TestSubthemeHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    // We append the function name to the title for test to check for.
    $view->setTitle($view->getTitle() . ":" . 'test_subtheme_views_pre_render');
  }

  /**
   * Implements hook_views_post_render().
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, &$output, CachePluginBase $cache): void {
    // We append the function name to the title for test to check for.
    $view->setTitle($view->getTitle() . ":" . 'test_subtheme_views_post_render');
    if ($view->id() == 'test_page_display') {
      $output['#rows'][0]['#title'] = $this->t('%total_rows items found.', [
        '%total_rows' => $view->total_rows,
      ]);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for theme_test_template_test templates.
   */
  #[Hook('preprocess_theme_test_template_test')]
  public function preprocessThemeTestTemplateTest(&$variables): void {
  }

}
