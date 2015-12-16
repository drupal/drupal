<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Discovery\InfoHookDecorator.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Allows info hook implementations to enhance discovered plugin definitions.
 */
class InfoHookDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * The name of the info hook that will be implemented by this discovery instance.
   *
   * @var string
   */
  protected $hook;

  /**
   * Constructs a InfoHookDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The object implementing DiscoveryInterface that is being decorated.
   * @param string $hook
   *   The name of the info hook to be invoked by this discovery instance.
   */
  public function __construct(DiscoveryInterface $decorated, $hook) {
    $this->decorated = $decorated;
    $this->hook = $hook;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();
    foreach (\Drupal::moduleHandler()->getImplementations($this->hook) as $module) {
      $function = $module . '_' . $this->hook;
      $function($definitions);
    }
    return $definitions;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }

}
