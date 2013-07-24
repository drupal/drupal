<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditorManager.
 */

namespace Drupal\edit\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Editor manager.
 *
 * The form editor must always be available.
 */
class InPlaceEditorManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $annotation_namespaces = array('Drupal\edit\Annotation' => $namespaces['Drupal\edit']);
    $this->discovery = new AnnotatedClassDiscovery('Plugin/InPlaceEditor', $namespaces, $annotation_namespaces, 'Drupal\edit\Annotation\InPlaceEditor');
    $this->discovery = new AlterDecorator($this->discovery, 'edit_editor');
    $this->discovery = new CacheDecorator($this->discovery, 'edit:editor');
    $this->factory = new DefaultFactory($this->discovery);
  }

}
