<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\CloseDialogCommand.
 */

namespace Drupal\Core\Ajax;

/**
 * Defines an AJAX command that closes the current active dialog.
 */
class CloseDialogCommand implements CommandInterface {

  /**
   * A CSS selector string of the dialog to close.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a CloseDialogCommand object.
   *
   * @param string $selector
   *   A CSS selector string of the dialog to close.
   */
  public function __construct($selector = NULL) {
    $this->selector = $selector ? $selector : '#drupal-modal';
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'closeDialog',
      'selector' => $this->selector,
    );
  }
}
