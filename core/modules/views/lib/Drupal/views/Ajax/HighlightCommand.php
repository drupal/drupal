<?php

/**
 * @file
 * Contains \Drupal\views\Ajax\HighlightCommand.
 */

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for highlighting a certain new piece of html.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.viewsHighlight.
 */
class HighlightCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a \Drupal\views\Ajax\HighlightCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return array(
      'command' => 'viewsHighlight',
      'selector' => $this->selector,
    );
  }

}
