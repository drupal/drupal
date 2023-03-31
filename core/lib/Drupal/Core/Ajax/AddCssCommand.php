<?php

namespace Drupal\Core\Ajax;

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
   * Arrays containing attributes of the stylesheets to be added to the page.
   *
   * @var string[][]|string
   */
  protected $styles;

  /**
   * Constructs an AddCssCommand.
   *
   * @param string[][]|string $styles
   *   Arrays containing attributes of the stylesheets to be added to the page.
   *   i.e. `['href' => 'someURL']` becomes `<link href="someURL">`.
   */
  public function __construct($styles) {
    if (is_string($styles)) {
      @trigger_error('The ' . __NAMESPACE__ . '\AddCssCommand with a string argument is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See http://www.drupal.org/node/3154948', E_USER_DEPRECATED);
    }
    $this->styles = $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'add_css',
      'data' => $this->styles,
    ];
  }

}
