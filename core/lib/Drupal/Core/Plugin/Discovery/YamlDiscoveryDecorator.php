<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Enables YAML discovery for plugin definitions.
 *
 * You should normally extend this class to add validation for the values in the
 * YAML data or to restrict use of the class or derivatives keys.
 */
class YamlDiscoveryDecorator extends YamlDiscovery {

  /**
   * The Discovery object being decorated.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Constructs a YamlDiscoveryDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated
   *   The discovery object that is being decorated.
   * @param string $name
   *   The file name suffix to use for discovery; for instance, 'test' will
   *   become 'MODULE.test.yml'.
   * @param array $directories
   *   An array of directories to scan.
   */
  public function __construct(DiscoveryInterface $decorated, $name, array $directories) {
    parent::__construct($name, $directories);

    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return parent::getDefinitions() + $this->decorated->getDefinitions();
  }

  /**
   * Passes through all unknown calls onto the decorated object.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->decorated, $method), $args);
  }

}
