<?php

/**
 * @file
 * Contains \Drupal\views\Ajax\ShowButtonsCommand.
 */

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for showing the save and cancel buttons.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.viewsShowButtons.
 */
class ShowButtonsCommand implements CommandInterface {

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'viewsShowButtons',
    );
  }

}
