<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command for implementing jQuery's data() method.
 *
 * This instructs the client to attach the name=value pair of data to the
 * selector via jQuery's data cache.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.data() defined
 * in misc/ajax.js.
 *
 * @ingroup ajax
 */
class DataCommand implements CommandInterface {

  /**
   * Constructs a DataCommand object.
   *
   * @param string $selector
   *   A CSS selector for the elements to which the data will be attached.
   * @param string $name
   *   The key of the data to be attached to elements matched by the selector.
   * @param mixed $value
   *   The value of the data to be attached to elements matched by the selector.
   */
  public function __construct(
    protected $selector,
    protected $name,
    protected $value,
  ) {
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'data',
      'selector' => $this->selector,
      'name' => $this->name,
      'value' => $this->value,
    ];
  }

}
