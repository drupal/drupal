<?php

namespace Drupal\editor\Ajax;

use Drupal\Core\Ajax\BaseCommand;

/**
 * AJAX command to rerender a formatted text field without any transformation
 * filters.
 */
class GetUntransformedTextCommand extends BaseCommand {

  /**
   * Constructs a GetUntransformedTextCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('editorGetUntransformedText', $data);
  }

}
