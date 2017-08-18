<?php

namespace Drupal\dialog_renderer_test\Controller;

use Drupal\Core\Url;

/**
 * Test controller display modal links and content.
 */
class TestController {

  /**
   * Return modal content.
   *
   * @return array
   *   Render array for display in modal.
   */
  public function modalContent() {
    return [
      '#type' => 'markup',
      '#markup' => 'Look at me in a modal!',
    ];
  }

  /**
   * Displays test links that will open in the modal dialog.
   *
   * @return array
   *   Render array with links.
   */
  public function linksDisplay() {
    return [
      'normal_modal' => [
        '#title' => 'Normal Modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
        ],
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ],
      'wide_modal' => [
        '#title' => 'Wide Modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-renderer' => 'wide',
        ],
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ],
      'extra_wide_modal' => [
        '#title' => 'Extra Wide Modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-renderer' => 'extra_wide',
        ],
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ],
    ];
  }

}
