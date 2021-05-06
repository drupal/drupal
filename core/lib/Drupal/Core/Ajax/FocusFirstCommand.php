<?php

namespace Drupal\Core\Ajax;

/**
 * AJAX command for focusing an element.
 *
 * This command is provided a selector then does the following:
 * - The first element matching the provided selector will become the container
 *   where the search for tabbable elements is conducted.
 * - If one or more tabbable elements are found within the container, the first
 *   of those will receive focus.
 * - If no tabbable elements are found within the container, but the container
 *   itself is focusable, then the container will receive focus.
 * - If the container is not focusable and contains no tabbable elements, the
 *   triggering element will remain focused.
 *
 * @see Drupal.AjaxCommands.focusFirst
 *
 * @ingroup ajax
 */
class FocusFirstCommand implements CommandInterface {

  /**
   * The selector of the container with tabbable elements.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs an FocusFirstCommand object.
   *
   * @param string $selector
   *   The selector of the container with tabbable elements.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'focusFirst',
      'selector' => $this->selector,
    ];
  }

}
