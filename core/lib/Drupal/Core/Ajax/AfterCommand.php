<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\AfterCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\InsertCommand;

/**
 * An AJAX command for calling the jQuery after() method.
 *
 * The 'insert/after' command instructs the client to use jQuery's after()
 * method to insert the given HTML content after each element matched by the
 * given selector.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @see http://docs.jquery.com/Manipulation/after#content
 */
class AfterCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'insert',
      'method' => 'after',
      'selector' => $this->selector,
      'data' => $this->html,
      'settings' => $this->settings,
    );
  }

}
