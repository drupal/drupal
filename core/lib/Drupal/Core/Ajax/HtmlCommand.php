<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\HtmlCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\InsertCommand;

/**
 * AJAX command for calling the jQuery html() method.
 *
 * The 'insert/html' command instructs the client to use jQuery's html() method
 * to set the HTML content of each element matched by the given selector while
 * leaving the outer tags intact.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @see http://docs.jquery.com/Attributes/html#val
 *
 * @ingroup ajax
 */
class HtmlCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'insert',
      'method' => 'html',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    );
  }

}
