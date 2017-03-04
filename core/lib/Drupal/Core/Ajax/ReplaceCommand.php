<?php

namespace Drupal\Core\Ajax;

/**
 * AJAX command for calling the jQuery replace() method.
 *
 * The 'insert/replaceWith' command instructs the client to use jQuery's
 * replaceWith() method to replace each element matched by the given selector
 * with the given HTML.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * See
 * @link http://docs.jquery.com/Manipulation/replaceWith#content jQuery replaceWith command @endlink
 *
 * @ingroup ajax
 */
class ReplaceCommand extends InsertCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'insert',
      'method' => 'replaceWith',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
