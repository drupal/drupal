<?php

namespace Drupal\plugin_test\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Defines a plugin manager used by Plugin API derivative unit tests.
 */
class MockBlockManager extends PluginManagerBase {
  public function __construct() {

    // Create the object that can be used to return definitions for all the
    // plugins available for this type. Most real plugin managers use a richer
    // discovery implementation, but StaticDiscovery lets us add some simple
    // mock plugins for unit testing.
    $this->discovery = new StaticDiscovery();

    // Derivative plugins are plugins that are derived from a base plugin
    // definition and some site configuration (examples below). To allow for
    // such plugins, we add the DerivativeDiscoveryDecorator to our discovery
    // object.
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);

    // The plugin definitions that follow are based on work that is in progress
    // for the Drupal 8 Blocks and Layouts initiative
    // (http://groups.drupal.org/node/213563). As stated above, we set
    // definitions here, because this is for unit testing. Real plugin managers
    // use a discovery implementation that allows for any module to add new
    // plugins to the system.

    // A simple plugin: the user login block.
    $this->discovery->setDefinition('user_login', [
      'id' => 'user_login',
      'label' => t('User login'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock',
    ]);

    // A plugin that requires derivatives: the menu block plugin. We do not want
    // a generic "Menu" block showing up in the Block administration UI.
    // Instead, we want a block for each menu, but the number of menus in the
    // system and each one's title is user configurable. The
    // MockMenuBlockDeriver class ensures that only derivatives, and not the
    // base plugin, are available to the system.
    $this->discovery->setDefinition('menu', [
      'id' => 'menu',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
      'deriver' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlockDeriver',
    ]);
    // A plugin defining itself as a derivative.
    $this->discovery->setDefinition('menu:foo', [
      'id' => 'menu',
      'label' => t('Base label'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
    ]);

    // A block plugin that can optionally be derived: the layout block plugin.
    // A layout is a special kind of block into which other blocks can be
    // placed. We want both a generic "Layout" block available in the Block
    // administration UI as well as additional user-created custom layouts. The
    // MockLayoutBlockDeriver class ensures that both the base plugin and the
    // derivatives are available to the system.
    $this->discovery->setDefinition('layout', [
      'id' => 'layout',
      'label' => t('Layout'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlock',
      'deriver' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlockDeriver',
    ]);

    // A block plugin that requires context to function. This block requires a
    // user object in order to return the user name from the getTitle() method.
    $this->discovery->setDefinition('user_name', [
      'id' => 'user_name',
      'label' => t('User name'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserNameBlock',
      'context' => [
        'user' => $this->createContextDefinition('entity:user', t('User')),
      ],
    ]);

    // An optional context version of the previous block plugin.
    $this->discovery->setDefinition('user_name_optional', [
      'id' => 'user_name_optional',
      'label' => t('User name optional'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserNameBlock',
      'context' => [
        'user' => $this->createContextDefinition('entity:user', t('User'), FALSE),
      ],
    ]);

    // A block plugin that requires a typed data string context to function.
    $this->discovery->setDefinition('string_context', [
      'id' => 'string_context',
      'label' => t('String typed data'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\TypedDataStringBlock',
    ]);

    // A complex context plugin that requires both a user and node for context.
    $this->discovery->setDefinition('complex_context', [
      'id' => 'complex_context',
      'label' => t('Complex context'),
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockComplexContextBlock',
      'context' => [
        'user' => $this->createContextDefinition('entity:user', t('User')),
        'node' => $this->createContextDefinition('entity:node', t('Node')),
      ],
    ]);

    // In addition to finding all of the plugins available for a type, a plugin
    // type must also be able to create instances of that plugin. For example, a
    // specific instance of a "Main menu" menu block, configured to show just
    // the top-level of links. To handle plugin instantiation, plugin managers
    // can use one of the factory classes included with the plugin system, or
    // create their own. ReflectionFactory is a general purpose, flexible
    // factory suitable for many kinds of plugin types. Factories need access to
    // the plugin definitions (e.g., since that's where the plugin's class is
    // specified), so we provide it the discovery object.
    $this->factory = new ReflectionFactory($this->discovery);
  }

  /**
   * Creates a new context definition with a label that is cast to string.
   *
   * @param string $data_type
   *   The required data type.
   * @param mixed string|null $label
   *   The label of this context definition for the UI.
   * @param bool $required
   *   Whether the context definition is required.
   *
   * @return \Drupal\Core\Plugin\Context\ContextDefinition
   */
  protected function createContextDefinition($data_type, $label, $required = TRUE) {
    // We cast the label to string for testing purposes only, as it may be
    // a TranslatableMarkup and we will do assertEqual() checks on arrays that
    // include ContextDefinition objects, and var_export() has problems
    // printing TranslatableMarkup objects.
    return new ContextDefinition($data_type, (string) $label, $required);
  }

}
