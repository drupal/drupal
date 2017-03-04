<?php

namespace Drupal\Core\Ajax;

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
 *
 * @ingroup ajax
 */
class PrependCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'insert',
      'method' => 'prepend',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
