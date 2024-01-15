<?php

namespace Drupal\Component\Plugin\Discovery;

use Drupal\Component\Plugin\Definition\DerivablePluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\InvalidDeriverException;

/**
 * Base class providing the tools for a plugin discovery to be derivative aware.
 *
 * Provides a decorator that allows the use of plugin derivatives for normal
 * implementations DiscoveryInterface.
 */
class DerivativeDiscoveryDecorator implements CachedDiscoveryInterface {

  use DiscoveryTrait;

  /**
   * Plugin derivers.
   *
   * @var \Drupal\Component\Plugin\Derivative\DeriverInterface[]
   *   Keys are base plugin IDs.
   */
  protected $derivers = [];

  /**
   * The decorated plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The parent object implementing DiscoveryInterface that is being
   *   decorated.
   */
  public function __construct(DiscoveryInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDeriverException
   *   Thrown if the 'deriver' class specified in the plugin definition
   *   does not implement \Drupal\Component\Plugin\Derivative\DeriverInterface.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    // This check is only for derivative plugins that have explicitly provided
    // an ID. This is not common, and can be expected to fail. Therefore, opt
    // out of the thrown exception, which will be handled when checking the
    // $base_plugin_id.
    $plugin_definition = $this->decorated->getDefinition($plugin_id, FALSE);

    [$base_plugin_id, $derivative_id] = $this->decodePluginId($plugin_id);
    $base_plugin_definition = $this->decorated->getDefinition($base_plugin_id, $exception_on_invalid);
    if ($base_plugin_definition) {
      $deriver = $this->getDeriver($base_plugin_id, $base_plugin_definition);
      if ($deriver) {
        $derivative_plugin_definition = $deriver->getDerivativeDefinition($derivative_id, $base_plugin_definition);
        // If a plugin defined itself as a derivative, merge in possible
        // defaults from the derivative.
        if ($derivative_id && isset($plugin_definition)) {
          $plugin_definition = $this->mergeDerivativeDefinition($plugin_definition, $derivative_plugin_definition);
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidDeriverException
   *   Thrown if the 'deriver' class specified in the plugin definition
   *   does not implement \Drupal\Component\Plugin\Derivative\DeriverInterface.
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
    $plugin_definitions = [];
    foreach ($base_plugin_definitions as $base_plugin_id => $plugin_definition) {
      $deriver = $this->getDeriver($base_plugin_id, $plugin_definition);
      if ($deriver) {
        $derivative_definitions = $deriver->getDerivativeDefinitions($plugin_definition);
        foreach ($derivative_definitions as $derivative_id => $derivative_definition) {
          $plugin_id = $this->encodePluginId($base_plugin_id, $derivative_id);
          // Use this definition as defaults if a plugin already defined
          // itself as this derivative.
          if ($derivative_id && isset($base_plugin_definitions[$plugin_id])) {
            $derivative_definition = $this->mergeDerivativeDefinition($base_plugin_definitions[$plugin_id], $derivative_definition);
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

    return [$plugin_id, NULL];
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
   * Gets a deriver for a base plugin.
   *
   * @param string $base_plugin_id
   *   The base plugin id of the plugin.
   * @param mixed $base_definition
   *   The base plugin definition to build derivatives.
   *
   * @return \Drupal\Component\Plugin\Derivative\DeriverInterface|null
   *   A DerivativeInterface or NULL if none exists for the plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDeriverException
   *   Thrown if the 'deriver' class specified in the plugin definition
   *   does not implement \Drupal\Component\Plugin\Derivative\DeriverInterface.
   */
  protected function getDeriver($base_plugin_id, $base_definition) {
    if (!isset($this->derivers[$base_plugin_id])) {
      $this->derivers[$base_plugin_id] = FALSE;
      $class = $this->getDeriverClass($base_definition);
      if ($class) {
        $this->derivers[$base_plugin_id] = new $class($base_plugin_id);
      }
    }
    return $this->derivers[$base_plugin_id] ?: NULL;
  }

  /**
   * Gets the deriver class name from the base plugin definition.
   *
   * @param array $base_definition
   *   The base plugin definition to build derivatives.
   *
   * @return string|null
   *   The name of a class implementing
   *   \Drupal\Component\Plugin\Derivative\DeriverInterface.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidDeriverException
   *   Thrown if the 'deriver' class specified in the plugin definition
   *   does not implement
   *   \Drupal\Component\Plugin\Derivative\DerivativeInterface.
   */
  protected function getDeriverClass($base_definition) {
    $class = NULL;
    $id = NULL;
    if ($base_definition instanceof DerivablePluginDefinitionInterface) {
      $class = $base_definition->getDeriver();
      $id = $base_definition->id();
    }
    if ((is_array($base_definition) || ($base_definition = (array) $base_definition)) && (isset($base_definition['deriver']))) {
      $class = $base_definition['deriver'];
      $id = $base_definition['id'];
    }
    if ($class) {
      if (!class_exists($class)) {
        throw new InvalidDeriverException(sprintf('Plugin (%s) deriver "%s" does not exist.', $id, $class));
      }
      if (!is_subclass_of($class, '\Drupal\Component\Plugin\Derivative\DeriverInterface')) {
        throw new InvalidDeriverException(sprintf('Plugin (%s) deriver "%s" must implement \Drupal\Component\Plugin\Derivative\DeriverInterface.', $id, $class));
      }
    }
    return $class;
  }

  /**
   * Merges a base and derivative definition, taking into account empty values.
   *
   * @param array $base_plugin_definition
   *   The base plugin definition.
   * @param array $derivative_definition
   *   The derivative plugin definition.
   *
   * @return array
   *   The merged definition.
   */
  protected function mergeDerivativeDefinition($base_plugin_definition, $derivative_definition) {
    // Use this definition as defaults if a plugin already defined itself as
    // this derivative, but filter out empty values first.
    $filtered_base = array_filter($base_plugin_definition);
    $derivative_definition = $filtered_base + ($derivative_definition ?: []);
    // Add back any empty keys that the derivative didn't have.
    return $derivative_definition + $base_plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->derivers = [];
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    if (!$use_caches) {
      $this->clearCachedDefinitions();
    }
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->decorated, $method], $args);
  }

}
