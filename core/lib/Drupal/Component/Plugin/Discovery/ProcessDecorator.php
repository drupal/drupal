<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Discovery\ProcessDecorator.
 */

namespace Drupal\Component\Plugin\Discovery;

/**
 * Allows custom processing of the discovered definition.
 *
 * Example use cases include adding in default values for a definition, or
 * providing a backwards compatibility layer for renamed definition properties.
 */
class ProcessDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * The processor callback to run on each discovered definition.
   *
   * @var callable
   */
   protected $processCallback;

  /**
   * Constructs a \Drupal\Component\Plugin\Discovery\ProcessDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The discovery object that is being decorated.
   * @param callable $process_callback
   *   The processor callback to run on each discovered definition. The
   *   callback will be called with the following arguments:
   *   - array $definition: the discovered definition, that the callback
   *     should accept by reference and modify in place.
   *   - string $plugin_id: the corresponding plugin_id.
   */
  public function __construct(DiscoveryInterface $decorated, $process_callback) {
    $this->decorated = $decorated;
    $this->processCallback = $process_callback;
  }

  /**
   * Implements \Drupal\Component\Plugin\Discovery\DicoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      call_user_func_array($this->processCallback, array(&$definition, $plugin_id));
    }
    // Allow process callbacks to unset definitions.
    return array_filter($definitions);
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }

}
