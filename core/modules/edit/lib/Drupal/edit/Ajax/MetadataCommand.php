<?php

/**
 * @file
 * Contains \Drupal\edit\Ajax\MetadataCommand.
 */

namespace Drupal\edit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for passing fields metadata to Edit's JavaScript app.
 */
class MetadataCommand extends BaseCommand {

  /**
   * Constructs a MetadataCommand object.
   *
   * @param string $metadata
   *   The metadata to pass on to the client side.
   */
  public function __construct($metadata) {
    parent::__construct('editMetadata', $metadata);
  }

}
