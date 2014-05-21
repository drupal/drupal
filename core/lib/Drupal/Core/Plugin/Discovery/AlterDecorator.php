<?php

/**
 * @file
 * Definition of Drupal\Core\Plugin\Discovery\AlterDiscoveryDecorator.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Enables altering of discovered plugin definitions.
 */
class AlterDecorator implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The name of the alter hook that will be implemented by this discovery instance.
   *
   * @var string
   */
  protected $hook;

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Constructs a Drupal\Core\Plugin\Discovery\AlterDecorator object.
   *
   * It uses the DiscoveryInterface object it should decorate.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The object implementing DiscoveryInterface that is being decorated.
   * @param string $hook
   *   The name of the alter hook that will be used by this discovery instance.
   */
  public function __construct(DiscoveryInterface $decorated, $hook) {
    $this->decorated = $decorated;
    $this->hook = $hook;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();
    \Drupal::moduleHandler()->alter($this->hook, $definitions);
    return $definitions;
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }
}
