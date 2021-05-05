<?php

namespace Drupal\dialog_renderer_test\Controller;

use Drupal\Component\Serialization\Json;
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
   * Return modal content with link.
   *
   * @return array
   *   Render array for display in modal.
   */
  public function modalContentLink() {
    return [
      '#type' => 'container',
      'text' => [
        '#type' => 'markup',
        '#markup' => 'Look at me in a modal!<br><a href="#">And a link!</a>',
      ],
      'input' => [
        '#type' => 'textfield',
        '#size' => 60,
      ],
    ];
  }

  /**
   * Return modal content with autofocus input.
   *
   * @return array
   *   Render array for display in modal.
   */
  public function modalContentInput() {
    return [
      '#type' => 'container',
      'text' => [
        '#type' => 'markup',
        '#markup' => 'Look at me in a modal!<br><a href="#">And a link!</a>',
      ],
      'input' => [
        '#type' => 'textfield',
        '#size' => 60,
        '#attributes' => [
          'autofocus' => TRUE,
        ],
      ],
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
      'no_close_modal' => [
        '#title' => 'Hidden close button modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'dialogClass' => 'no-close',
          ]),
        ],
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
            'dialog_renderer_test/dialog_test',
          ],
        ],
      ],
      'button_pane_modal' => [
        '#title' => 'Button pane modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'buttons' => [
              [
                'text' => 'OK',
                'click' => '() => {}',
              ],
            ],
          ]),
        ],
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ],
      'content_link_modal' => [
        '#title' => 'Content link modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content_link'),
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
      'auto_focus_modal' => [
        '#title' => 'Auto focus modal!',
        '#type' => 'link',
        '#url' => Url::fromRoute('dialog_renderer_test.modal_content_input'),
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
    ];
  }

}
