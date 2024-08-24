<?php

declare(strict_types=1);

namespace Drupal\views_test_render_cache\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Provides route responses.
 */
class ViewsTestRenderCacheController extends ControllerBase {

  /**
   * Returns the same block rendered twice with different arguments.
   *
   * @var string $view_id
   *   The view id.
   * @var string $display_id
   *   The display id of display to be rendered twice.
   * @var string $args_1
   *   Comma-separated args to use in the first rendering.
   * @var string $args_2
   *   Comma-separated args to use in the second rendering.
   *
   * @return array
   *   A renderable array.
   */
  public function double(string $view_id, string $display_id, string $args_1, string $args_2) {
    $build = [];
    $view = Views::getView($view_id);
    $build[] = $view->buildRenderable($display_id, explode(",", $args_1));
    $view = Views::getView($view_id);
    $build[] = $view->buildRenderable($display_id, explode(",", $args_2));
    return $build;
  }

}
