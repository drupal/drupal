<?php

declare(strict_types=1);

namespace Drupal\ajax_forms_test\Controller;

use Drupal\Core\Url;

/**
 * Test class to create dialog form link.
 */
class DialogFormLink {

  /**
   * Builds an associative array representing a link that opens a dialog.
   *
   * @return array
   *   An associative array of link to a form to be opened.
   */
  public function makeDialogFormLink() {
    return [
      'dialog' => [
        '#type' => 'link',
        '#title' => 'Open form in dialog',
        '#url' => Url::fromRoute('ajax_forms_test.get_form'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
        ],
      ],
      'off_canvas' => [
        '#type' => 'link',
        '#title' => 'Open form in off canvas dialog',
        '#url' => Url::fromRoute('ajax_forms_test.get_form'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
        ],
      ],
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
    ];
  }

}
