<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\InsertCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\InsertCommand;

/**
 * An AJAX command for calling the jQuery before() method.
 *
 * The 'insert/before' command instructs the client to use jQuery's before()
 * method to insert the given HTML content before each of elements matched by
 * the given selector.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @see http://docs.jquery.com/Manipulation/before#content
 *
 * @ingroup ajax
 */
class BeforeCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'insert',
      'method' => 'before',
      'selector' => $this->selector,
      'data' => $this->html,
      'settings' => $this->settings,
    );
  }

}
