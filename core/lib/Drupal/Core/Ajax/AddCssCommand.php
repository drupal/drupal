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
   * @var string[][]
   */
  protected $styles;

  /**
   * Constructs an AddCssCommand.
   *
   * @param string[][] $styles
   *   Arrays containing attributes of the stylesheets to be added to the page.
   *   i.e. `['href' => 'someURL']` becomes `<link href="someURL">`.
   */
  public function __construct(array $styles) {
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
