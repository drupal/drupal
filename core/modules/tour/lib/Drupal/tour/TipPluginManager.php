<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginManager.
 */

namespace Drupal\tour;

use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Configurable tour manager.
 */
class TipPluginManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $annotation_namespaces = array('Drupal\tour\Annotation' => $namespaces['Drupal\tour']);
    $this->discovery = new AnnotatedClassDiscovery('tour/tip', $namespaces, $annotation_namespaces, 'Drupal\tour\Annotation\Tip');
    $this->discovery = new AlterDecorator($this->discovery, 'tour_tips_info');
    $this->discovery = new CacheDecorator($this->discovery, 'tour');

    $this->factory = new ContainerFactory($this->discovery);
  }

}
