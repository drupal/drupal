<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command for calling the jQuery css() method.
 *
 * The 'css' command will instruct the client to use the jQuery css() method to
 * apply the CSS arguments to elements matched by the given selector.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.css() defined
 * in misc/ajax.js.
 *
 * @see http://docs.jquery.com/CSS/css#properties
 *
 * @ingroup ajax
 */
class CssCommand implements CommandInterface {

  /**
   * Constructs a CssCommand object.
   *
   * @param string $selector
   *   A CSS selector for elements to which the CSS will be applied.
   * @param array $css
   *   An array of CSS property/value pairs to set.
   */
  public function __construct(
    protected $selector,
    protected array $css = [],
  ) {
  }

  /**
   * Adds a property/value pair to the CSS to be added to this element.
   *
   * @param string $property
   *   The CSS property to be changed.
   * @param string $value
   *   The new value of the CSS property.
   *
   * @return $this
   */
  public function setProperty($property, $value) {
    $this->css[$property] = $value;
    return $this;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'css',
      'selector' => $this->selector,
      'argument' => $this->css,
    ];
  }

}
