<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\OpenDialogCommand.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to open certain content in a dialog.
 *
 * @ingroup ajax
 */
class OpenDialogCommand implements CommandInterface {

  /**
   * The selector of the dialog.
   *
   * @var string
   */
  protected $selector;

  /**
   * The title of the dialog.
   *
   * @var string
   */
  protected $title;

  /**
   * HTML content that will placed in the dialog.
   *
   * @var string
   */
  protected $html;

  /**
   * Stores dialog-specific options passed directly to jQuery UI dialogs. Any
   * jQuery UI option can be used. See http://api.jqueryui.com/dialog.
   *
   * @var array
   */
  protected $dialogOptions;

  /**
   * Custom settings that will be passed to the Drupal behaviors on the content
   * of the dialog.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs an OpenDialogCommand object.
   *
   * @param string $selector
   *   The selector of the dialog.
   * @param string $title
   *   The title of the dialog.
   * @param string $html
   *   HTML that will be placed in the dialog.
   * @param array $dialog_options
   *   (optional) Options to be passed to the dialog implementation. Any
   *   jQuery UI option can be used. See http://api.jqueryui.com/dialog.
   * @param array|null $settings
   *   (optional) Custom settings that will be passed to the Drupal behaviors
   *   on the content of the dialog. If left empty, the settings will be
   *   populated automatically from the current request.
   */
  public function __construct($selector, $title, $html, array $dialog_options = array(), $settings = NULL) {
    $dialog_options += array('title' => $title);
    $this->selector = $selector;
    $this->html = $html;
    $this->dialogOptions = $dialog_options;
    $this->settings = $settings;
  }

  /**
   * Returns the dialog options.
   *
   * @return array
   */
  public function getDialogOptions() {
    return $this->dialogOptions;
  }

  /**
   * Sets the dialog options array.
   *
   * @param array $dialog_options
   *   Options to be passed to the dialog implementation. Any jQuery UI option
   *   can be used. See http://api.jqueryui.com/dialog.
   */
  public function setDialogOptions($dialog_options) {
    $this->dialogOptions = $dialog_options;
  }

  /**
   * Sets a single dialog option value.
   *
   * @param string $key
   *   Key of the dialog option. Any jQuery UI option can be used.
   *   See http://api.jqueryui.com/dialog.
   * @param mixed $value
   *   Option to be passed to the dialog implementation.
   */
  public function setDialogOption($key, $value) {
    $this->dialogOptions[$key] = $value;
  }

  /**
   * Sets the dialog title (an alias of setDialogOptions).
   *
   * @param string $title
   *   The new title of the dialog.
   */
  public function setDialogTitle($title) {
    $this->setDialogOptions('title', $title);
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    // For consistency ensure the modal option is set to TRUE or FALSE.
    $this->dialogOptions['modal'] = isset($this->dialogOptions['modal']) && $this->dialogOptions['modal'];
    return array(
      'command' => 'openDialog',
      'selector' => $this->selector,
      'settings' => $this->settings,
      'data' => $this->html,
      'dialogOptions' => $this->dialogOptions,
    );
  }

}
