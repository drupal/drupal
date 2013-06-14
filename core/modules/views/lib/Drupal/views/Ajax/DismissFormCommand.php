<?php

/**
 * @file
 * Contains \Drupal\views\Ajax\DismissFormCommand.
 */

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for closing the views form modal.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.viewsDismissForm.
 */
class DismissFormCommand implements CommandInterface {

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'viewsDismissForm',
    );
  }

}
