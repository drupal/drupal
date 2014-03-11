<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator.
 */

namespace Drupal\Component\Plugin\Discovery;

use Drupal\Component\Plugin\Exception\InvalidDerivativeClassException;

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
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDerivativeClassException
   *   Thrown if the 'derivative' class specified in the plugin definition does
   *   not implement \Drupal\Component\Plugin\Derivative\DerivativeInterface.
   */
  public function getDefinition($plugin_id) {
    $plugin_definition = $this->decorated->getDefinition($plugin_id);
    list($base_plugin_id, $derivative_id) = $this->decodePluginId($plugin_id);
    $base_plugin_definition = $this->decorated->getDefinition($base_plugin_id);
    if ($base_plugin_definition) {
      $derivative_fetcher = $this->getDerivativeFetcher($base_plugin_id, $base_plugin_definition);
      if ($derivative_fetcher) {
        $derivative_plugin_definition = $derivative_fetcher->getDerivativeDefinition($derivative_id, $base_plugin_definition);
        // If a plugin defined itself as a derivative, merge in possible
        // defaults from the derivative.
        if ($derivative_id && isset($plugin_definition)) {
          $plugin_definition += $derivative_plugin_definition ?: array();
        }
        else {
          $plugin_definition = $derivative_plugin_definition;
        }
      }
    }

    return $plugin_definition;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDerivativeClassException
   *   Thrown if the 'derivative' class specified in the plugin definition does
   *   not implement \Drupal\Component\Plugin\Derivative\DerivativeInterface.
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
          // Use this definition as defaults if a plugin already defined
          // itself as this derivative.
          if ($derivative_id && isset($base_plugin_definitions[$plugin_id])) {
            $derivative_definition = $base_plugin_definitions[$plugin_id] + ($derivative_definition ?: array());
          }
          $plugin_definitions[$plugin_id] = $derivative_definition;
        }
      }
      // If a plugin already defined itself as a derivative it might already
      // be merged into the definitions.
      elseif (!isset($plugin_definitions[$base_plugin_id])) {
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
   * @param mixed $base_definition
   *   The base plugin definition to build derivatives.
   *
   * @return \Drupal\Component\Plugin\Derivative\DerivativeInterface|null
   *   A DerivativeInterface or NULL if none exists for the plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDerivativeClassException
   *   Thrown if the 'derivative' class specified in the plugin definition does
   *   not implement \Drupal\Component\Plugin\Derivative\DerivativeInterface.
   */
  protected function getDerivativeFetcher($base_plugin_id, $base_definition) {
    if (!isset($this->derivativeFetchers[$base_plugin_id])) {
      $this->derivativeFetchers[$base_plugin_id] = FALSE;
      $class = $this->getDerivativeClass($base_definition);
      if ($class) {
        $this->derivativeFetchers[$base_plugin_id] = new $class($base_plugin_id);
      }
    }
    return $this->derivativeFetchers[$base_plugin_id] ?: NULL;
  }

  /**
   * Get the derivative class name from the base plugin definition.
   *
   * @param array $base_definition
   *   The base plugin definition to build derivatives.
   *
   * @return string|NULL
   *   The name of a class implementing \Drupal\Component\Plugin\Derivative\DerivativeInterface.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDerivativeClassException
   *   Thrown if the 'derivative' class specified in the plugin definition does
   *   not implement \Drupal\Component\Plugin\Derivative\DerivativeInterface.
   */
  protected function getDerivativeClass($base_definition) {
    $class = NULL;
    if ((is_array($base_definition) || ($base_definition = (array) $base_definition)) && (isset($base_definition['derivative']) && $class = $base_definition['derivative'])) {
      if (!is_subclass_of($class, '\Drupal\Component\Plugin\Derivative\DerivativeInterface')) {
        throw new InvalidDerivativeClassException(sprintf('Plugin (%s) derivative class "%s" has to implement interface \Drupal\Component\Plugin\Derivative\DerivativeInterface', $base_definition['id'], $class));
      }
    }
    return $class;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }
}
