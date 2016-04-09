<?php

namespace Drupal\Core\Ajax;

/**
 * Defines an AJAX command that closes the currently visible modal dialog.
 *
 * @ingroup ajax
 */
class CloseModalDialogCommand extends CloseDialogCommand {

  /**
   * Constructs a CloseModalDialogCommand object.
   *
   * @param bool $persist
   *   (optional) Whether to persist the dialog in the DOM or not.
   */
  public function __construct($persist = FALSE) {
    $this->selector = '#drupal-modal';
    $this->persist = $persist;
  }

}
