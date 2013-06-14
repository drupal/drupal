<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\PrependCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\InsertCommand;

/**
 * AJAX command for calling the jQuery insert() method.
 *
 * The 'insert/prepend' command instructs the client to use jQuery's prepend()
 * method to prepend the given HTML content to the inside each element matched
 * by the given selector.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @see http://docs.jquery.com/Manipulation/prepend#content
 */
class PrependCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'insert',
      'method' => 'prepend',
      'selector' => $this->selector,
      'data' => $this->html,
      'settings' => $this->settings,
    );
  }

}
