<?php

namespace Drupal\quickedit\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * Indicates a field was saved and passes the rerendered field to Quick Edit.
 */
class FieldFormSavedCommand extends BaseCommand {

  /**
   * The same re-rendered edited field, but in different view modes.
   *
   * @var array
   */
  protected $other_view_modes;

  /**
   * Constructs a FieldFormSavedCommand object.
   *
   * @param string $data
   *   The re-rendered edited field to pass on to the client side.
   * @param array $other_view_modes
   *   The same re-rendered edited field, but in different view modes, for other
   *   instances of the same field on the user's page. Keyed by view mode.
   */
  public function __construct($data, $other_view_modes = []) {
    parent::__construct('quickeditFieldFormSaved', $data);

    $this->other_view_modes = $other_view_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => $this->command,
      'data' => $this->data,
      'other_view_modes' => $this->other_view_modes,
    ];
  }

}
