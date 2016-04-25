<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command for calling the jQuery append() method.
 *
 * The 'insert/append' command instructs the client to use jQuery's append()
 * method to append the given HTML content to the inside of each element matched
 * by the given selector.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @see http://docs.jquery.com/Manipulation/append#content
 *
 * @ingroup ajax
 */
class AppendCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'insert',
      'method' => 'append',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    );
  }

}
