<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for views form multiple test routes.
 */
class ViewsTestFormMultipleController extends ControllerBase {

  /**
   * Returns a test page having test_form_multiple view embedded twice.
   */
  public function testPage() {
    $build = [
      'view_arg1' => [
        '#prefix' => '<div class="view-test-form-multiple-1">',
        '#suffix' => '</div>',
        '#type' => 'view',
        '#name' => 'test_form_multiple',
        '#display_id' => 'default',
        '#arguments' => ['arg1'],
        '#embed' => TRUE,
      ],
      'view_arg2' => [
        '#prefix' => '<div class="view-test-form-multiple-2">',
        '#suffix' => '</div>',
        '#type' => 'view',
        '#name' => 'test_form_multiple',
        '#display_id' => 'default',
        '#arguments' => ['arg2'],
        '#embed' => TRUE,
      ],
    ];
    return $build;
  }

}
