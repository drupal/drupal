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
   * An array containing attributes of the scripts to be added to the page.
   *
   * @var string[]
   */
  protected $scripts;

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value will default to 'body'.
   *
   * @var string
   */
  protected $selector;

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
  public function __construct(array $scripts, string $selector = 'body') {
    $this->scripts = $scripts;
    $this->selector = $selector;
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
