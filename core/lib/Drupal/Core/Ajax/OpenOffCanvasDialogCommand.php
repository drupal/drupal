<?php

namespace Drupal\Core\Ajax;

/**
 * Defines an AJAX command to open content in a dialog in an off-canvas tray.
 *
 * @ingroup ajax
 */
class OpenOffCanvasDialogCommand extends OpenDialogCommand {

  /**
   * The dialog width to use if none is provided.
   */
  const DEFAULT_DIALOG_WIDTH = 300;

  /**
   * Constructs an OpenOffCanvasDialogCommand object.
   *
   * The off-canvas dialog differs from the normal modal provided by
   * OpenDialogCommand in that an off-canvas has built in positioning and
   * behaviors. Drupal provides a built-in off-canvas dialog for this purpose,
   * so the selector is hard-coded in the call to the parent constructor.
   *
   * @param string|\Stringable|null $title
   *   The title of the dialog.
   * @param string|array $content
   *   The content that will be placed in the dialog, either a render array
   *   or an HTML string.
   * @param array $dialog_options
   *   (optional) Settings to be passed to the dialog implementation. Any
   *   jQuery UI option can be used. See http://api.jqueryui.com/dialog.
   * @param array|null $settings
   *   (optional) Custom settings that will be passed to the Drupal behaviors
   *   on the content of the dialog. If left empty, the settings will be
   *   populated automatically from the current request.
   * @param string $position
   *   (optional) The position to render the off-canvas dialog.
   */
  public function __construct(string|\Stringable|null $title, $content, array $dialog_options = [], $settings = NULL, $position = 'side') {
    $dialog_class = FALSE;
    if (isset($dialog_options['classes']['ui-dialog'])) {
      $dialog_class = $dialog_options['classes']['ui-dialog'];
    }
    elseif (isset($dialog_options['dialogClass'])) {
      @trigger_error('Passing $dialog_options[\'dialogClass\'] to OpenOffCanvasDialogCommand::__construct() is deprecated in drupal:10.3.0 and will be removed in drupal:12.0.0. Use $dialog_options[\'classes\'] instead. See https://www.drupal.org/node/3440844', E_USER_DEPRECATED);
      $dialog_class = $dialog_options['dialogClass'];
      unset($dialog_options['dialogClass']);
    }
    if ($dialog_class) {
      $dialog_options['classes']['ui-dialog'] = $dialog_class . ' ' . "ui-dialog-off-canvas ui-dialog-position-$position";
    }
    else {
      $dialog_options['classes']['ui-dialog'] = "ui-dialog-off-canvas ui-dialog-position-$position";
    }
    parent::__construct('#drupal-off-canvas', $title, $content, $dialog_options, $settings);
    $this->dialogOptions['modal'] = FALSE;
    $this->dialogOptions['autoResize'] = FALSE;
    $this->dialogOptions['resizable'] = 'w';
    $this->dialogOptions['draggable'] = FALSE;
    $this->dialogOptions['drupalAutoButtons'] = FALSE;
    $this->dialogOptions['drupalOffCanvasPosition'] = $position;

    // Add CSS class to #drupal-off-canvas element. This enables developers to
    // select previous versions of off-canvas styles by using custom selector:
    // #drupal-off-canvas:not(.drupal-off-canvas-reset).
    $this->dialogOptions['classes']['ui-dialog-content'] = 'drupal-off-canvas-reset';
    // If no width option is provided then use the default width to avoid the
    // dialog staying at the width of the previous instance when opened
    // more than once, with different widths, on a single page.
    if (!isset($this->dialogOptions['width'])) {
      $this->dialogOptions['width'] = static::DEFAULT_DIALOG_WIDTH;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['effect'] = 'fade';
    $build['speed'] = 1000;
    return $build;
  }

}
