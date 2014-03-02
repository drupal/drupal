<?php

/**
 * @file
 * Contains Drupal\plugin_test\Plugin\DefaultsTestPluginManager.
 */

namespace Drupal\plugin_test\Plugin;

use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a plugin manager used by Plugin API unit tests.
 */
class DefaultsTestPluginManager extends DefaultPluginManager {

 /**
  * Constructs a new DefaultsTestPluginManager instance.
  *
  * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
  *   The module handler.
  */
  public function __construct(ModuleHandlerInterface $module_handler) {
    // Create the object that can be used to return definitions for all the
    // plugins available for this type. Most real plugin managers use a richer
    // discovery implementation, but StaticDiscovery lets us add some simple
    // mock plugins for unit testing.
    $this->discovery = new StaticDiscovery();
    $this->factory = new DefaultFactory($this);
    $this->moduleHandler = $module_handler;

    // Specify default values.
    $this->defaults = array(
      'metadata' => array(
        'default' => TRUE,
      ),
    );

    // Add a plugin with a custom value.
    $this->discovery->setDefinition('test_block1', array(
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockTestBlock',
      'metadata' => array(
        'custom' => TRUE,
      ),
    ));
    // Add a plugin that overrides the default value.
    $this->discovery->setDefinition('test_block2', array(
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockTestBlock',
      'metadata' => array(
        'custom' => TRUE,
        'default' => FALSE,
      ),
    ));
  }

}
