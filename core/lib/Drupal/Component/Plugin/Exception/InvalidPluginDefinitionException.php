<?php

namespace Drupal\Component\Plugin\Exception;

/**
 * Defines a class for invalid plugin definition exceptions.
 */
class InvalidPluginDefinitionException extends PluginException {

  /**
   * The plugin ID of the mapper.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Constructs a InvalidPluginDefinitionException.
   *
   * @param string $plugin_id
   *   The plugin ID of the mapper.
   *
   * @see \Exception for the remaining parameters.
   */
  public function __construct($plugin_id, $message = '', $code = 0, \Exception $previous = NULL) {
    $this->pluginId = $plugin_id;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Gets the plugin ID of the mapper that raised the exception.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

}
