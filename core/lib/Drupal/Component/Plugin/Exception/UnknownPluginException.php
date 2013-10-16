<?php
/**
 * @file
 * Contains \Drupal\Component\Plugin\Exception\UnknownPluginException.
 */

namespace Drupal\Component\Plugin\Exception;

use Drupal\Component\Utility\String;

/**
 * Plugin exception class to be thrown when a nonexistent plugin was requested.
 */
class UnknownPluginException extends PluginException {

  /**
   * Construct an UnknownPluginException exception.
   *
   * @param string $instance_id
   *   The invalid instance ID that failed.
   *
   * @see \Exception for remaining parameters.
   */
  public function __construct($instance_id, $message = '', $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = String::format("Unknown plugin ID '@instance'.", array('@instance' => $instance_id));
    }
    parent::__construct($message, $code, $previous);
  }

}
