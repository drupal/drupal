<?php

namespace Drupal\Core\Ajax;

/**
 * AJAX command for a javascript alert box.
 *
 * @ingroup ajax
 */
class AlertCommand implements CommandInterface {

  /**
   * The text to be displayed in the alert box.
   *
   * @var string
   */
  protected $text;

  /**
   * Constructs an AlertCommand object.
   *
   * @param string $text
   *   The text to be displayed in the alert box.
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'alert',
      'text' => $this->text,
    ];
  }

}
