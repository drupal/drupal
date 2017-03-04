<?php

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin_test\Plugin\TestPluginManager;
use Drupal\plugin_test\Plugin\MockBlockManager;
use Drupal\plugin_test\Plugin\DefaultsTestPluginManager;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Base class for Plugin API unit tests.
 */
abstract class PluginTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['plugin_test'];

  protected $testPluginManager;
  protected $testPluginExpectedDefinitions;
  protected $mockBlockManager;
  protected $mockBlockExpectedDefinitions;
  protected $defaultsTestPluginManager;
  protected $defaultsTestPluginExpectedDefinitions;

  protected function setUp() {
    parent::setUp();

    // Real modules implementing plugin types may expose a module-specific API
    // for retrieving each type's plugin manager, or make them available in
    // Drupal's dependency injection container, but for unit testing, we get
    // the managers directly.
    // - TestPluginManager is a bare bones manager with no support for
    //   derivatives, and uses DefaultFactory for plugin instantiation.
    // - MockBlockManager is used for testing more advanced functionality such
    //   as derivatives and ReflectionFactory.
    $this->testPluginManager = new TestPluginManager();
    $this->mockBlockManager = new MockBlockManager();
    $module_handler = new ModuleHandler(\Drupal::root(), [], new MemoryBackend(), $this->container->get('event_dispatcher'));
    $this->defaultsTestPluginManager = new DefaultsTestPluginManager($module_handler);

    // The expected plugin definitions within each manager. Several tests assert
    // that these plugins and their definitions are found and returned by the
    // necessary API functions.
    // @see TestPluginManager::_construct().
    // @see MockBlockManager::_construct().
    $this->testPluginExpectedDefinitions = [
      'user_login' => [
        'label' => 'User login',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock',
      ],
    ];
    $this->mockBlockExpectedDefinitions = [
      'user_login' => [
        'id' => 'user_login',
        'label' => 'User login',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock',
      ],
      'menu:main_menu' => [
        'id' => 'menu',
        'label' => 'Main menu',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
      ],
      'menu:navigation' => [
        'id' => 'menu',
        'label' => 'Navigation',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
      ],
      'menu:foo' => [
        'id' => 'menu',
        'label' => 'Base label',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockMenuBlock',
        'setting' => 'default',
      ],
      'layout' => [
        'id' => 'layout',
        'label' => 'Layout',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlock',
      ],
      'layout:foo' => [
        'id' => 'layout',
        'label' => 'Layout Foo',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockLayoutBlock',
      ],
      'user_name' => [
        'id' => 'user_name',
        'label' => 'User name',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserNameBlock',
        'context' => [
          'user' => new ContextDefinition('entity:user', 'User'),
        ],
      ],
      'user_name_optional' => [
        'id' => 'user_name_optional',
        'label' => 'User name optional',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserNameBlock',
        'context' => [
          'user' => new ContextDefinition('entity:user', 'User', FALSE),
        ],
      ],
      'string_context' => [
        'id' => 'string_context',
        'label' => 'String typed data',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\TypedDataStringBlock',
      ],
      'complex_context' => [
        'id' => 'complex_context',
        'label' => 'Complex context',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockComplexContextBlock',
        'context' => [
          'user' => new ContextDefinition('entity:user', 'User'),
          'node' => new ContextDefinition('entity:node', 'Node'),
        ],
      ],
    ];
    $this->defaultsTestPluginExpectedDefinitions = [
      'test_block1' => [
        'metadata' => [
          'default' => TRUE,
          'custom' => TRUE,
        ],
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockTestBlock',
      ],
      'test_block2' => [
        'metadata' => [
          'default' => FALSE,
          'custom' => TRUE,
        ],
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockTestBlock',
      ],
    ];
  }

}
