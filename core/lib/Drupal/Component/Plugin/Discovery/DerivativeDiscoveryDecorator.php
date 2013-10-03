<?php

/**
 * @file
 * Definition of Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator.
 */

namespace Drupal\Component\Plugin\Discovery;

/**
 * Base class providing the tools for a plugin discovery to be derivative aware.
 *
 * Provides a decorator that allows the use of plugin derivatives for normal
 * implementations DiscoveryInterface.
 */
class DerivativeDiscoveryDecorator implements DiscoveryInterface {

  protected $derivativeFetchers = array();
  protected $decorated;

  /**
   * Creates a Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator
   * object.
   *
   * @param DiscoveryInterface $discovery
   *   The parent object implementing DiscoveryInterface that is being
   *   decorated.
   */
  public function __construct(DiscoveryInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinition().
   */
  public function getDefinition($plugin_id) {
    list($base_plugin_id, $derivative_id) = $this->decodePluginId($plugin_id);

    $plugin_definition = $this->decorated->getDefinition($base_plugin_id);
    if (isset($plugin_definition)) {
      $derivative_fetcher = $this->getDerivativeFetcher($base_plugin_id, $plugin_definition);
      if ($derivative_fetcher) {
        $plugin_definition = $derivative_fetcher->getDerivativeDefinition($derivative_id, $plugin_definition);
      }
    }

    return $plugin_definition;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $plugin_definitions = $this->decorated->getDefinitions();
    return $this->getDerivatives($plugin_definitions);
  }

  /**
   * Adds derivatives to a list of plugin definitions.
   *
   * This should be called by the class extending this in
   * DiscoveryInterface::getDefinitions().
   */
  protected function getDerivatives(array $base_plugin_definitions) {
    $plugin_definitions = array();
    foreach ($base_plugin_definitions as $base_plugin_id => $plugin_definition) {
      $derivative_fetcher = $this->getDerivativeFetcher($base_plugin_id, $plugin_definition);
      if ($derivative_fetcher) {
        $derivative_definitions = $derivative_fetcher->getDerivativeDefinitions($plugin_definition);
        foreach ($derivative_definitions as $derivative_id => $derivative_definition) {
          $plugin_id = $this->encodePluginId($base_plugin_id, $derivative_id);
          $plugin_definitions[$plugin_id] = $derivative_definition;
        }
      }
      else {
        $plugin_definitions[$base_plugin_id] = $plugin_definition;
      }
    }

    return $plugin_definitions;
  }

  /**
   * Decodes derivative id and plugin id from a string.
   *
   * @param string $plugin_id
   *   Plugin identifier that may point to a derivative plugin.
   *
   * @return array
   *   An array with the base plugin id as the first index and the derivative id
   *   as the second. If there is no derivative id it will be null.
   */
  protected function decodePluginId($plugin_id) {
    // Try and split the passed plugin definition into a plugin and a
    // derivative id. We don't need to check for !== FALSE because a leading
    // colon would break the derivative system and doesn't makes sense.
    if (strpos($plugin_id, ':')) {
      return explode(':', $plugin_id, 2);
    }

    return array($plugin_id, NULL);
  }

  /**
   * Encodes plugin and derivative id's into a string.
   *
   * @param string $base_plugin_id
   *   The base plugin identifier.
   * @param string $derivative_id
   *   The derivative identifier.
   *
   * @return string
   *   A uniquely encoded combination of the $base_plugin_id and $derivative_id.
   */
  protected function encodePluginId($base_plugin_id, $derivative_id) {
    if ($derivative_id) {
      return "$base_plugin_id:$derivative_id";
    }

    // By returning the unmerged plugin_id, we are able to support derivative
    // plugins that support fetching the base definitions.
    return $base_plugin_id;
  }

  /**
   * Finds a Drupal\Component\Plugin\Discovery\DerivativeInterface.
   *
   * This Drupal\Component\Plugin\Discovery\DerivativeInterface can fetch
   * derivatives for the plugin.
   *
   * @param string $base_plugin_id
   *   The base plugin id of the plugin.
   * @param array $base_definition
   *   The base plugin definition to build derivatives.
   *
   * @return \Drupal\Component\Plugin\Discovery\DerivativeInterface|null
   *   A DerivativeInterface or null if none exists for the plugin.
   */
  protected function getDerivativeFetcher($base_plugin_id, array $base_definition) {
    if (!isset($this->derivativeFetchers[$base_plugin_id])) {
      $this->derivativeFetchers[$base_plugin_id] = FALSE;
      if (isset($base_definition['derivative'])) {
        $class = $base_definition['derivative'];
        $this->derivativeFetchers[$base_plugin_id] = new $class($base_plugin_id);
      }
    }
    return $this->derivativeFetchers[$base_plugin_id] ?: NULL;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }
}
