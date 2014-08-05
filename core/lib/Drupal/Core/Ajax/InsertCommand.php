<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\InsertCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Generic AJAX command for inserting content.
 *
 * This command instructs the client to insert the given HTML using whichever
 * jQuery DOM manipulation method has been specified in the #ajax['method']
 * variable of the element that triggered the request.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @ingroup ajax
 */
class InsertCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * The HTML content that will replace the matched element(s).
   *
   * @var string
   */
  protected $html;

  /**
   * A settings array to be passed to any any attached JavaScript behavior.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs an InsertCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   * @param string $html
   *   String of HTML that will replace the matched element(s).
   * @param array $settings
   *   An array of JavaScript settings to be passed to any attached behaviors.
   */
  public function __construct($selector, $html, array $settings = NULL) {
    $this->selector = $selector;
    $this->html = $html;
    $this->settings = $settings;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return array(
      'command' => 'insert',
      'method' => NULL,
      'selector' => $this->selector,
      'data' => $this->html,
      'settings' => $this->settings,
    );
  }

}
