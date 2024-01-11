<?php

namespace Drupal\editor\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for saving the contents of an editor dialog.
 *
 * This command is implemented in editor.dialog.js in
 * Drupal.AjaxCommands.prototype.editorDialogSave.
 */
class EditorDialogSave implements CommandInterface {

  /**
   * An array of values that will be passed back to the editor by the dialog.
   *
   * @var array
   */
  protected array $values;

  /**
   * Constructs an EditorDialogSave object.
   *
   * @param array $values
   *   The values that should be passed to the form constructor in Drupal.
   */
  public function __construct(array $values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'editorDialogSave',
      'values' => $this->values,
    ];
  }

}
