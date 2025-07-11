<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_data.
 */
class ViewsTestDataThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for views table templates.
   */
  #[Hook('preprocess_views_view_table')]
  public function preprocessViewsViewTable(&$variables): void {
    if ($variables['view']->storage->id() == 'test_view_render') {
      $views_render_test = \Drupal::state()->get('views_render.test');
      $views_render_test++;
      \Drupal::state()->set('views_render.test', $views_render_test);
    }
  }

}
