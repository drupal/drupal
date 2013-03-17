<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\CloseModalDialogCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\CloseDialogCommand;

/**
 * Defines an AJAX command that closes the currently visible modal dialog.
 */
class CloseModalDialogCommand extends CloseDialogCommand {
  /**
   * Constructs a CloseModalDialogCommand object.
   */
  public function __construct() {
    $this->selector = '#drupal-modal';
  }
}
