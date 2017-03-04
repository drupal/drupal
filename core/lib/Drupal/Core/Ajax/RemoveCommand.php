<?php

namespace Drupal\Core\Ajax;

/**
 * AJAX command for calling the jQuery remove() method.
 *
 * The 'remove' command instructs the client to use jQuery's remove() method
 * to remove each of elements matched by the given selector, and everything
 * within them.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.remove()
 * defined in misc/ajax.js.
 *
 * @see http://docs.jquery.com/Manipulation/remove#expr
 *
 * @ingroup ajax
 */
class RemoveCommand implements CommandInterface {

  /**
   * The CSS selector for the element(s) to be removed.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a RemoveCommand object.
   *
   * @param string $selector
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'remove',
      'selector' => $this->selector,
    ];
  }

}
