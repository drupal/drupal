<?php

namespace Drupal\Core\Ajax;

use Drupal\Component\Render\PlainTextOutput;

/**
 * Defines an AJAX command to open certain content in a dialog.
 *
 * @ingroup ajax
 */
class OpenDialogCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  use CommandWithAttachedAssetsTrait;

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
   * The content for the dialog.
   *
   * Either a render array or an HTML string.
   *
   * @var string|array
   */
  protected $content;

  /**
   * Stores dialog-specific options passed directly to jQuery UI dialogs.
   *
   * Any jQuery UI option can be used.
   *
   * @var array
   *
   * @see http://api.jqueryui.com/dialog.
   */
  protected $dialogOptions;

  /**
   * Custom settings passed to Drupal behaviors on the content of the dialog.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs an OpenDialogCommand object.
   *
   * @param string $selector
   *   The selector of the dialog.
   * @param string|\Stringable|null $title
   *   The title of the dialog.
   * @param string|array $content
   *   The content that will be placed in the dialog, either a render array
   *   or an HTML string.
   * @param array $dialog_options
   *   (optional) Options to be passed to the dialog implementation. Any
   *   jQuery UI option can be used. See http://api.jqueryui.com/dialog.
   * @param array|null $settings
   *   (optional) Custom settings that will be passed to the Drupal behaviors
   *   on the content of the dialog. If left empty, the settings will be
   *   populated automatically from the current request.
   */
  public function __construct($selector, string|\Stringable|null $title, $content, array $dialog_options = [], $settings = NULL) {
    $title = PlainTextOutput::renderFromHtml($title);

    $dialog_options += ['title' => $title];
    if (isset($dialog_options['dialogClass'])) {
      @trigger_error('Passing $dialog_options[\'dialogClass\'] to OpenDialogCommand::__construct() is deprecated in drupal:10.3.0 and will be removed in drupal:12.0.0. Use $dialog_options[\'classes\'] instead. See https://www.drupal.org/node/3440844', E_USER_DEPRECATED);
      $dialog_options['classes']['ui-dialog'] = $dialog_options['dialogClass'];
      unset($dialog_options['dialogClass']);
    }

    $this->selector = $selector;
    $this->content = $content;
    $this->dialogOptions = $dialog_options;
    $this->settings = $settings;
  }

  /**
   * Returns the dialog options.
   *
   * @return array
   *   An array of the dialog-specific options passed directly to jQuery UI
   *   dialogs.
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
    $this->setDialogOption('title', $title);
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    // For consistency ensure the modal option is set to TRUE or FALSE.
    $this->dialogOptions['modal'] = isset($this->dialogOptions['modal']) && $this->dialogOptions['modal'];
    return [
      'command' => 'openDialog',
      'selector' => $this->selector,
      'settings' => $this->settings,
      'data' => $this->getRenderedContent(),
      'dialogOptions' => $this->dialogOptions,
    ];
  }

}
