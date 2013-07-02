<?php

/**
 * @file
 * Contains \Drupal\Core\Template\RenderWrapper.
 */

namespace Drupal\Core\Template;

/**
 * A class that wraps functions to call them while printing in a template.
 *
 * To use, one may pass in the function name as a string followed by an array of
 * arguments to the constructor.
 * @code
 * $variables['scripts'] = new RenderWrapper('drupal_get_js', array('footer'));
 * @endcode
 */
class RenderWrapper {

  /**
   * Stores the callback function to be called when rendered.
   *
   * @var callable
   */
  public $callback;

  /**
   * Stores the callback's arguments.
   *
   * @var array
   */
  public $args = array();

  /**
   * Constructs a RenderWrapper object.
   *
   * @param string $callback
   *   The callback function name.
   * @param array $args
   *   The arguments to pass to the callback function.
   */
  public function __construct($callback, array $args = array()) {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('Callback passed to RenderWrapper is not callable.');
    }
    $this->callback = $callback;
    $this->args = $args;
  }

  /**
   * Implements the magic __toString() method.
   */
  public function __toString() {
    return $this->render();
  }

  /**
   * Returns a string provided by the callback function.
   *
   * @return string
   *   The results of the callback function.
   */
  public function render() {
    if (!empty($this->callback) && is_callable($this->callback)) {
      return call_user_func_array($this->callback, $this->args);
    }
  }

}
