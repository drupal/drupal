<?php

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for showing the save and cancel buttons.
 *
 * This command is implemented in
 * Drupal.AjaxCommands.prototype.viewsShowButtons.
 */
class ShowButtonsCommand implements CommandInterface {

  /**
   * Constructs a \Drupal\views\Ajax\ShowButtonsCommand object.
   *
   * @param bool $changed
   *   Whether the view has been changed.
   */
  public function __construct(
    protected $changed,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'viewsShowButtons',
      'changed' => $this->changed,
    ];
  }

}
