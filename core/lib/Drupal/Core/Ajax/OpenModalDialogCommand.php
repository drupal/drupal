<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command to open certain content in a dialog in a modal dialog.
 *
 * @ingroup ajax
 */
class OpenModalDialogCommand extends OpenDialogCommand {

  /**
   * Constructs an OpenModalDialog object.
   *
   * The modal dialog differs from the normal modal provided by
   * OpenDialogCommand in that a modal prevents other interactions on the page
   * until the modal has been completed. Drupal provides a built-in modal for
   * this purpose, so no selector needs to be provided.
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
   */
  public function __construct(string|\Stringable|null $title, $content, array $dialog_options = [], $settings = NULL) {
    $dialog_options['modal'] = TRUE;
    parent::__construct('#drupal-modal', $title, $content, $dialog_options, $settings);
  }

}
