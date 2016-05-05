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
   * A CSS selector string for elements to which data will be attached.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * The key of the data attached to elements matched by the selector.
   *
   * @var string
   */
  protected $name;

  /**
   * The value of the data to be attached to elements matched by the selector.
   *
   * The data is not limited to strings; it can be any format.
   *
   * @var mixed
   */
  protected $value;

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
  public function __construct($selector, $name, $value) {
    $this->selector = $selector;
    $this->name = $name;
    $this->value = $value;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'data',
      'selector' => $this->selector,
      'name' => $this->name,
      'value' => $this->value,
    );
  }

}
