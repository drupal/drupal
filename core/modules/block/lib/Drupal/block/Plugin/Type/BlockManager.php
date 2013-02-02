<?php

/**
 * Contains \Drupal\block\Plugin\Type\BlockManager.
 */

namespace Drupal\block\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\block\Plugin\Core\Entity\Block;

/**
 * Manages discovery and instantiation of block plugins.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\block\BlockInterface
 */
class BlockManager extends PluginManagerBase {

  /**
   * Constructs a new \Drupal\block\Plugin\Type\BlockManager object.
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('block', 'block');
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'block');
    $this->discovery = new CacheDecorator($this->discovery, 'block_plugins:' . language(LANGUAGE_TYPE_INTERFACE)->langcode, 'cache_block', CacheBackendInterface::CACHE_PERMANENT, array('block'));
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array(), Block $entity = NULl) {
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $this->discovery);
    return new $plugin_class($configuration, $plugin_id, $this->discovery, $entity);
  }

}
