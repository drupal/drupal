<?php

namespace Drupal\off_canvas_test\Controller;

use Drupal\Component\Serialization\Json;
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
   * Displays test links that will open in off-canvas dialog.
   *
   * @return array
   *   Render array with links.
   */
  public function linksDisplay() {
    return [
      'off_canvas_link_1' => [
        '#title' => 'Click Me 1!',
        '#type' => 'link',
        '#url' => Url::fromRoute('off_canvas_test.thing1'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
        ],
        '#attached' => [
          'library' => [
            'outside_in/drupal.outside_in',
          ],
        ],
      ],
      'off_canvas_link_2' => [
        '#title' => 'Click Me 2!',
        '#type' => 'link',
        '#url' => Url::fromRoute('off_canvas_test.thing2'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
          'data-dialog-options' => Json::encode([
            'width' => 555,
          ]),
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
        '#url' => Url::fromRoute('off_canvas_test.dialog_links'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
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
   * Displays dialogs links to be displayed inside the off-canvas dialog.
   *
   * This links are used to test opening a modal and another off_canvas link from
   * inside the off-canvas dialog.
   *
   * @todo Update tests to check these links work in the off-canvas dialog.
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
          'url' => Url::fromRoute('off_canvas_test.thing2'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ],
        'off_canvas_link' => [
          'title' => 'Off_canvas link!',
          'url' => Url::fromRoute('off_canvas_test.thing2'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'dialog',
            'data-dialog-renderer' => 'off_canvas',
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
