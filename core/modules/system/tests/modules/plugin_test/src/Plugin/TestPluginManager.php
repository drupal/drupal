<?php

namespace Drupal\plugin_test\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Defines a plugin manager used by Plugin API unit tests.
 */
class TestPluginManager extends PluginManagerBase {
  public function __construct() {

    // Create the object that can be used to return definitions for all the
    // plugins available for this type. Most real plugin managers use a richer
    // discovery implementation, but StaticDiscovery lets us add some simple
    // mock plugins for unit testing.
    $this->discovery = new StaticDiscovery();

    // A simple plugin: a mock user login block.
    $this->discovery->setDefinition('user_login', [
      'label' => 'User login',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock',
    ]);

    // In addition to finding all of the plugins available for a type, a plugin
    // type must also be able to create instances of that plugin. For example, a
    // specific instance of a "User login" block, configured with a custom
    // title. To handle plugin instantiation, plugin managers can use one of the
    // factory classes included with the plugin system, or create their own.
    // DefaultFactory is a simple, general purpose factory suitable for
    // many kinds of plugin types. Factories need access to the plugin
    // definitions (e.g., since that's where the plugin's class is specified),
    // so we provide it the discovery object.
    $this->factory = new DefaultFactory($this->discovery);
  }

}
