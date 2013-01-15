<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\EditorManager.
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
 * The "Form" Create.js PropertyEditor widget must always be available.
 */
class EditorManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('edit', 'editor');
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'edit_editor');
    $this->discovery = new CacheDecorator($this->discovery, 'edit:editor');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // @todo Remove this check once http://drupal.org/node/1780396 is resolved.
    if (!module_exists($definition['module'])) {
      $definition = NULL;
      return;
    }
  }

}
