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
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'offcanvas',
        ],
        '#attached' => [
          'library' => [
            'outside_in/drupal.outside_in',
          ],
        ],
      ],
      'offcanvas_link_2' => [
        '#title' => 'Click Me 2!',
        '#type' => 'link',
        '#url' => Url::fromRoute('offcanvas_test.thing2'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'offcanvas',
        ],
        '#attached' => [
          'library' => [
            'outside_in/drupal.outside_in',
          ],
        ],
      ],
      'other_dialog_links' => [
        '#title' => 'Display more links!',
        '#type' => 'link',
        '#url' => Url::fromRoute('offcanvas_test.dialog_links'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'offcanvas',
        ],
        '#attached' => [
          'library' => [
            'outside_in/drupal.outside_in',
          ],
        ],
      ],
    ];
  }

  /**
   * Displays dialogs links to be displayed inside the offcanvas tray.
   *
   * This links are used to test opening a modal and another offcanvas link from
   * inside the offcanvas tray.
   *
   * @todo Update tests to check these links work in the offcanvas tray.
   *       https://www.drupal.org/node/2790073
   *
   * @return array
   *   Render array with links.
   */
  public function otherDialogLinks() {
    return [
      '#theme' => 'links',
      '#links' => [
        'modal_link' => [
          'title' => 'Open modal!',
          'url' => Url::fromRoute('offcanvas_test.thing2'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ],
        'offcanvas_link' => [
          'title' => 'Offcanvas link!',
          'url' => Url::fromRoute('offcanvas_test.thing2'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'dialog',
            'data-dialog-renderer' => 'offcanvas',
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'outside_in/drupal.outside_in',
        ],
      ],
    ];
  }

}
