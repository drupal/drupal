<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\RestripeCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for resetting the striping on a table.
 *
 * The 'restripe' command instructs the client to restripe a table. This is
 * usually used after a table has been modified by a replace or append command.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.restripe()
 * defined in misc/ajax.js.
 *
 * @ingroup ajax
 */
class RestripeCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a RestripeCommand object.
   *
   * @param string $selector
   *   A CSS selector for the table to be restriped.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'restripe',
      'selector' => $this->selector,
    );
  }

}
