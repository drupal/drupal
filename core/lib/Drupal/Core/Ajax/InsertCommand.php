<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\InsertCommand.
 */

namespace Drupal\Core\Ajax;

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
class InsertCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

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
   * The content for the matched element(s).
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;

  /**
   * A settings array to be passed to any attached JavaScript behavior.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs an InsertCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   * @param string|array $content
   *   The content that will be inserted in the matched element(s), either a
   *   render array or an HTML string.
   * @param array $settings
   *   An array of JavaScript settings to be passed to any attached behaviors.
   */
  public function __construct($selector, $content, array $settings = NULL) {
    $this->selector = $selector;
    $this->content = $content;
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
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    );
  }

}
