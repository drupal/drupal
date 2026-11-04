<?php

namespace Drupal\Core\Ajax;

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
   * Constructs a RestripeCommand object.
   *
   * @param string $selector
   *   A CSS selector for the table to be restriped.
   */
  public function __construct(
    protected $selector,
  ) {
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'restripe',
      'selector' => $this->selector,
    ];
  }

}
