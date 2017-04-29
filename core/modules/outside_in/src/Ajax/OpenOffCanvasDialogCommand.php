<?php

namespace Drupal\outside_in\Ajax;

use Drupal\Core\Ajax\OpenDialogCommand;

/**
 * Defines an AJAX command to open content in a dialog in a off-canvas dialog.
 *
 * @ingroup ajax
 */
class OpenOffCanvasDialogCommand extends OpenDialogCommand {

  /**
   * Constructs an OpenOffCanvasDialogCommand object.
   *
   * The off-canvas dialog differs from the normal modal provided by
   * OpenDialogCommand in that a off-canvas has built in positioning and
   * behaviours. Drupal provides a built-in off-canvas dialog for this purpose,
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
   */
  public function __construct($title, $content, array $dialog_options = [], $settings = NULL) {
    parent::__construct('#drupal-off-canvas', $title, $content, $dialog_options, $settings);
    $this->dialogOptions['modal'] = FALSE;
    $this->dialogOptions['autoResize'] = FALSE;
    $this->dialogOptions['resizable'] = 'w';
    $this->dialogOptions['draggable'] = FALSE;
    $this->dialogOptions['drupalAutoButtons'] = FALSE;
    // @todo drupal.ajax.js does not respect drupalAutoButtons properly, pass an
    //   empty set of buttons until https://www.drupal.org/node/2793343 is in.
    $this->dialogOptions['buttons'] = [];
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
