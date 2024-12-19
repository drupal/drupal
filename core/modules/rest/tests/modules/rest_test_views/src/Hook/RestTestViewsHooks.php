<?php

declare(strict_types=1);

namespace Drupal\rest_test_views\Hook;

use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for rest_test_views.
 */
class RestTestViewsHooks {

  /**
   * Implements hook_views_post_execute().
   */
  #[Hook('views_post_execute')]
  public function viewsPostExecute(ViewExecutable $view): void {
    // Attach a custom header to the test_data_export view.
    if ($view->id() === 'test_serializer_display_entity') {
      if ($value = \Drupal::state()->get('rest_test_views_set_header', FALSE)) {
        $view->getResponse()->headers->set('Custom-Header', $value);
      }
    }
  }

}
