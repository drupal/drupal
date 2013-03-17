<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\SetDialogTitleCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\SetDialogOptionCommand;

/**
 * Defines an AJAX command that sets jQuery UI dialog properties.
 */
class SetDialogTitleCommand extends SetDialogOptionCommand {

  /**
   * Constructs a SetDialogTitleCommand object.
   *
   * @param string $selector
   *   The selector of the dialog whose title will be set. If set to an empty
   *   value, the default modal dialog will be selected.
   * @param string $title
   *   The title that will be set on the dialog.
   */
  public function __construct($selector, $title) {
    $this->selector = $selector ? $selector : '#drupal-modal';
    $this->optionName = 'title';
    $this->optionValue = $title;
  }
}
