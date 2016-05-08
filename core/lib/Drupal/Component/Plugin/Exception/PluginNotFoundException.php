<?php

namespace Drupal\Component\Plugin\Exception;

/**
 * Plugin exception class to be thrown when a plugin ID could not be found.
 */
class PluginNotFoundException extends PluginException {

  /**
   * Construct an PluginNotFoundException exception.
   *
   * For the remaining parameters see \Exception.
   *
   * @param string $plugin_id
   *   The plugin ID that was not found.
   *
   * @see \Exception
   */
  public function __construct($plugin_id, $message = '', $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = sprintf("Plugin ID '%s' was not found.", $plugin_id);
    }
    parent::__construct($message, $code, $previous);
  }

}
