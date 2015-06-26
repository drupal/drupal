<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\AddCssCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An AJAX command for adding css to the page via ajax.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.add_css()
 * defined in misc/ajax.js.
 *
 * @see misc/ajax.js
 *
 * @ingroup ajax
 */
class AddCssCommand implements CommandInterface {

  /**
   * A string that contains the styles to be added to the page.
   *
   * It should include the wrapping style tag.
   *
   * @var string
   */
  protected $styles;

  /**
   * Constructs an AddCssCommand.
   *
   * @param string $styles
   *   A string that contains the styles to be added to the page, including the
   *   wrapping <style> tag.
   */
  public function __construct($styles) {
    $this->styles = $styles;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'add_css',
      'data' => $this->styles,
    );
  }

}
