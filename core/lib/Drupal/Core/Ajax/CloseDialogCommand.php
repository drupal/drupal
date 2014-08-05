<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\CloseDialogCommand.
 */

namespace Drupal\Core\Ajax;

/**
 * Defines an AJAX command that closes the current active dialog.
 *
 * @ingroup ajax
 */
class CloseDialogCommand implements CommandInterface {

  /**
   * A CSS selector string of the dialog to close.
   *
   * @var string
   */
  protected $selector;

  /**
   * Whether to persist the dialog in the DOM or not.
   *
   * @var bool
   */
  protected $persist;

  /**
   * Constructs a CloseDialogCommand object.
   *
   * @param string $selector
   *   A CSS selector string of the dialog to close.
   * @param bool $persist
   *   (optional) Whether to persist the dialog in the DOM or not.
   */
  public function __construct($selector = NULL, $persist = FALSE) {
    $this->selector = $selector ? $selector : '#drupal-modal';
    $this->persist = $persist;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'closeDialog',
      'selector' => $this->selector,
      'persist' => $this->persist,
    );
  }
}
