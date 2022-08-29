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
   * @param string $title
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
  public function __construct($title, $content, array $dialog_options = [], $settings = NULL, $position = 'side') {
    parent::__construct('#drupal-off-canvas', $title, $content, $dialog_options, $settings);
    $this->dialogOptions['modal'] = FALSE;
    $this->dialogOptions['autoResize'] = FALSE;
    $this->dialogOptions['resizable'] = 'w';
    $this->dialogOptions['draggable'] = FALSE;
    $this->dialogOptions['drupalAutoButtons'] = FALSE;
    $this->dialogOptions['drupalOffCanvasPosition'] = $position;
    // @todo drupal.ajax.js does not respect drupalAutoButtons properly, pass an
    //   empty set of buttons until https://www.drupal.org/node/2793343 is in.
    $this->dialogOptions['buttons'] = [];
    if (empty($dialog_options['dialogClass'])) {
      $this->dialogOptions['dialogClass'] = "ui-dialog-off-canvas ui-dialog-position-$position";
    }
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
