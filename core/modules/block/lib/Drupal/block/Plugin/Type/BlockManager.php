<?php

/**
 * Contains \Drupal\block\Plugin\Type\BlockManager.
 */

namespace Drupal\block\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Mapper\ConfigMapper;

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
    $this->discovery = new CacheDecorator($this->discovery, 'block_plugins:' . language(LANGUAGE_TYPE_INTERFACE)->langcode, 'cache_block');
    $this->factory = new DefaultFactory($this);
    $this->mapper = new ConfigMapper($this);
  }

}
