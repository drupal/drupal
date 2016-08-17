<?php

namespace Drupal\offcanvas_test\Controller;

use Drupal\Core\Url;

/**
 * Test controller for 2 different responses.
 */
class TestController {

  /**
   * Thing1.
   *
   * @return string
   *   Return Hello string.
   */
  public function thing1() {
    return [
      '#type' => 'markup',
      '#markup' => 'Thing 1 says hello',
    ];
  }

  /**
   * Thing2.
   *
   * @return string
   *   Return Hello string.
   */
  public function thing2() {
    return [
      '#type' => 'markup',
      '#markup' => 'Thing 2 says hello',
    ];
  }

  /**
   * Displays test links that will open in offcanvas tray.
   *
   * @return array
   *   Render array with links.
   */
  public function linksDisplay() {
    return [
      'offcanvas_link_1' => [
        '#title' => 'Click Me 1!',
        '#type' => 'link',
        '#url' => Url::fromRoute('offcanvas_test.thing1'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'offcanvas',
        ],
        '#attached' => [
          'library' => [
            'outside_in/drupal.off_canvas',
          ],
        ],
      ],
      'offcanvas_link_2' => [
        '#title' => 'Click Me 2!',
        '#type' => 'link',
        '#url' => Url::fromRoute('offcanvas_test.thing2'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'offcanvas',
        ],
        '#attached' => [
          'library' => [
            'outside_in/drupal.off_canvas',
          ],
        ],
      ],

    ];
  }

}
