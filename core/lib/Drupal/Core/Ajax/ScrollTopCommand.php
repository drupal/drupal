<?php

namespace Drupal\Core\Ajax;

/**
 * Provides an AJAX command for scrolling to the top of an element.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.scrollTop.
 */
class ScrollTopCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a \Drupal\Core\Ajax\ScrollTopCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'scrollTop',
      'selector' => $this->selector,
    ];
  }

}
