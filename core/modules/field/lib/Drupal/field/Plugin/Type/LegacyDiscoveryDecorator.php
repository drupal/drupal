<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\LegacyDiscoveryDecorator.
 */

namespace Drupal\field\Plugin\Type;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Plugin\Discovery\HookDiscovery;

/**
 * Custom decorator to add legacy plugins.
 *
 * Legacy plugins are discovered through
 * Drupal\Core\Plugin\Discovery\HookDiscovery, and handled by a legacy class.
 */
abstract class LegacyDiscoveryDecorator implements DiscoveryInterface {

  /**
   * The name of the hook for Drupal\Core\Plugin\Discovery\HookDiscovery.
   *
   * @var string
   */
  protected $hook;

  /**
   * The decorated discovery object.
   *
   * @var Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Creates a Drupal\field\Plugin\Discovery\LegacyDiscoveryDecorator object.
   *
   * @param Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
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
    $definitions = $this->getDefinitions();
    return isset($definitions[$plugin_id]) ? $definitions[$plugin_id] : NULL;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();

    $legacy_discovery = new HookDiscovery($this->hook);
    if ($legacy_definitions = $legacy_discovery->getDefinitions()) {
      foreach ($legacy_definitions as $plugin_id => $definition) {
        $this->processDefinition($definition);

        if (isset($definition['behaviors']['default value'])) {
          $definition['default_value'] = $definition['behaviors']['default value'];
          unset($definition['behaviors']['default value']);
        }

        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }

  /**
   * Massages a legacy plugin definition.
   *
   * @var array $definition
   *   A plugin definition, as discovered by
   *   Drupal\Core\Plugin\Discovery\HookDiscovery.
   *
   * @return array
   *   The massaged plugin definition.
   */
  abstract public function processDefinition(array &$definition);

}
