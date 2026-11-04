<?php

namespace Drupal\Core\Ajax;

/**
 * Generic AJAX command for inserting content.
 *
 * This command instructs the client to insert the given render array or HTML
 * using whichever jQuery DOM manipulation method has been specified in the
 * #ajax['method'] variable of the element that triggered the request.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.insert()
 * defined in misc/ajax.js.
 *
 * @ingroup ajax
 */
class InsertCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * Constructs an InsertCommand object.
   *
   * @param string|null $selector
   *   A CSS selector.
   * @param string|array $content
   *   The content that will be inserted in the matched element(s), either a
   *   render array or an HTML string.
   * @param array $settings
   *   An array of JavaScript settings to be passed to any attached behaviors.
   */
  public function __construct(
    protected $selector,
    protected $content,
    protected ?array $settings = NULL,
  ) {
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'insert',
      'method' => NULL,
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
