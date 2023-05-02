<?php

namespace Drupal\editor\Ajax;

use Drupal\Core\Ajax\BaseCommand;

@trigger_error('The ' . __NAMESPACE__ . '\GetUntransformedTextCommand is deprecated in drupal:9.5.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3271653', E_USER_DEPRECATED);

/**
 * Rerenders a formatted text field without any transformation filters.
 *
 * @deprecated in drupal:9.5.0 and is removed from drupal:10.0.0.
 * There is no replacement.
 *
 * @see http://www.drupal.org/node/3271653
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
