<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Type\PluginUIManager.
 */

namespace Drupal\system\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Manages discovery and instantiation of Plugin UI plugins.
 *
 * @todo This class needs @see references and/or more documentation.
 */
class PluginUIManager extends PluginManagerBase {

  /**
   * Constructs a \Drupal\system\Plugin\Type\PluginUIManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Plugin/PluginUI', $namespaces);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'plugin_ui');
    $this->discovery = new CacheDecorator($this->discovery, 'plugin_ui');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    $definition += array(
      'default_task' => TRUE,
      'task_title' => t('View'),
      'task_suffix' => 'view',
      'access_callback' => 'user_access',
    );
  }

}
