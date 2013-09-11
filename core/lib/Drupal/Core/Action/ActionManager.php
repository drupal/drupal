<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionManager.
 */

namespace Drupal\Core\Action;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Provides an Action plugin manager.
 *
 * @see \Drupal\Core\Annotation\Operation
 * @see \Drupal\Core\Action\OperationInterface
 */
class ActionManager extends PluginManagerBase {

  /**
   * Constructs a ActionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Plugin/Action', $namespaces, 'Drupal\Core\Annotation\Action');
    $this->discovery = new AlterDecorator($this->discovery, 'action_info');

    $this->factory = new ContainerFactory($this);
  }

  /**
   * Gets the plugin definitions for this entity type.
   *
   * @param string $type
   *   The entity type name.
   *
   * @return array
   *   An array of plugin definitions for this entity type.
   */
  public function getDefinitionsByType($type) {
    return array_filter($this->getDefinitions(), function ($definition) use ($type) {
      return $definition['type'] === $type;
    });
  }

}
