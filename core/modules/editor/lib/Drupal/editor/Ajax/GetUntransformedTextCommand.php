<?php

/**
 * @file
 * Contains \Drupal\editor\Ajax\GetUntransformedTextCommand.
 */

namespace Drupal\editor\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\quickedit\Ajax\BaseCommand;

/**
 * AJAX command to rerender a processed text field without any transformation
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
