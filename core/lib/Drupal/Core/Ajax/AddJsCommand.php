<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command for adding JS to the page via AJAX.
 *
 * This command will make sure all the files are loaded before continuing
 * executing the next AJAX command. This command is implemented by
 * Drupal.AjaxCommands.prototype.add_js() defined in misc/ajax.js.
 *
 * @see misc/ajax.js
 *
 * @ingroup ajax
 */
class AddJsCommand implements CommandInterface {

  /**
   * Constructs an AddJsCommand.
   *
   * @param array $scripts
   *   An array containing the attributes of the 'script' tags to be added to
   *   the page. i.e. `['src' => 'someURL', 'defer' => TRUE]` becomes
   *   `<script src="someURL" defer>`.
   * @param string $selector
   *   A CSS selector of the element where the script tags will be appended.
   */
  public function __construct(
    protected array $scripts,
    protected string $selector = 'body',
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'add_js',
      'selector' => $this->selector,
      'data' => $this->scripts,
    ];
  }

}
