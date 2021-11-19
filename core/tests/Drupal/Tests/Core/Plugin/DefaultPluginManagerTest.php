<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the DefaultPluginManager.
 *
 * @group Plugin
 *
 * @coversDefaultClass \Drupal\Core\Plugin\DefaultPluginManager
 */
class DefaultPluginManagerTest extends UnitTestCase {

  /**
   * The expected plugin definitions.
   *
   * @var array
   */
  protected $expectedDefinitions;

  /**
   * The namespaces to look for plugin definitions.
   *
   * @var \Traversable
   */
  protected $namespaces;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->expectedDefinitions = [
      'apple' => [
        'id' => 'apple',
        'label' => 'Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple',
      ],
      'banana' => [
        'id' => 'banana',
        'label' => 'Banana',
        'color' => 'yellow',
        'uses' => [
          'bread' => 'Banana bread',
          'loaf' => [
            'singular' => '@count loaf',
            'plural' => '@count loaves',
          ],
        ],
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
      ],
    ];

    $this->namespaces = new \ArrayObject();
    $this->namespaces['Drupal\plugin_test'] = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
  }

  /**
   * Tests the plugin manager with a plugin that extends a non-installed class.
   */
  public function testDefaultPluginManagerWithPluginExtendingNonInstalledClass() {
    $definitions = [];
    $definitions['extending_non_installed_class'] = [
      'id' => 'extending_non_installed_class',
      'label' => 'A plugin whose class is extending from a non-installed module class',
      'color' => 'pink',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\ExtendingNonInstalledClass',
      'provider' => 'plugin_test',
    ];

    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $plugin_manager = new TestPluginManager($this->namespaces, $definitions, $module_handler, 'test_alter_hook', '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->getDefinition('plugin_test', FALSE);
    $this->assertTrue(TRUE, 'No PHP fatal error occurred when retrieving the definitions of a module with plugins that depend on a non-installed module class should not cause a PHP fatal.');
  }

  /**
   * Tests the plugin manager with a disabled module.
   */
  public function testDefaultPluginManagerWithDisabledModule() {
    $definitions = $this->expectedDefinitions;
    $definitions['cherry'] = [
      'id' => 'cherry',
      'label' => 'Cherry',
      'color' => 'red',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
      'provider' => 'disabled_module',
    ];

    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->once())
      ->method('moduleExists')
      ->with('disabled_module')
      ->will($this->returnValue(FALSE));

    $plugin_manager = new TestPluginManager($this->namespaces, $definitions, $module_handler, 'test_alter_hook', '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    $this->assertEmpty($plugin_manager->getDefinition('cherry', FALSE), 'Plugin information of a disabled module is not available');
  }

  /**
   * Tests the plugin manager and object plugin definitions.
   */
  public function testDefaultPluginManagerWithObjects() {
    $definitions = $this->expectedDefinitions;
    $definitions['cherry'] = (object) [
      'id' => 'cherry',
      'label' => 'Cherry',
      'color' => 'red',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
      'provider' => 'disabled_module',
    ];

    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->once())
      ->method('moduleExists')
      ->with('disabled_module')
      ->will($this->returnValue(FALSE));

    $plugin_manager = new TestPluginManager($this->namespaces, $definitions, $module_handler, 'test_alter_hook', '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    $this->assertEmpty($plugin_manager->getDefinition('cherry', FALSE), 'Plugin information is available');
  }

  /**
   * Tests the plugin manager behavior for a missing plugin ID.
   */
  public function testGetDefinitionPluginNotFoundException() {
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions);

    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "missing" plugin does not exist. Valid plugin IDs for Drupal\Tests\Core\Plugin\TestPluginManager are: apple, banana');
    $plugin_manager->getDefinition('missing');
  }

  /**
   * Tests the plugin manager with no cache and altering.
   */
  public function testDefaultPluginManager() {
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $this->assertEquals($this->expectedDefinitions, $plugin_manager->getDefinitions());
    $this->assertEquals($this->expectedDefinitions['banana'], $plugin_manager->getDefinition('banana'));
  }

  /**
   * Tests the plugin manager with no cache and altering.
   */
  public function testDefaultPluginManagerWithAlter() {
    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandler')
      ->disableOriginalConstructor()
      ->getMock();

    // Configure the stub.
    $alter_hook_name = $this->randomMachineName();
    $module_handler->expects($this->once())
      ->method('alter')
      ->with($this->equalTo($alter_hook_name), $this->equalTo($this->expectedDefinitions));

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler, $alter_hook_name, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    $this->assertEquals($this->expectedDefinitions, $plugin_manager->getDefinitions());
    $this->assertEquals($this->expectedDefinitions['banana'], $plugin_manager->getDefinition('banana'));
  }

  /**
   * Tests the plugin manager with caching and altering.
   */
  public function testDefaultPluginManagerWithEmptyCache() {
    $cid = $this->randomMachineName();
    $cache_backend = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_backend
      ->expects($this->once())
      ->method('get')
      ->with($cid)
      ->will($this->returnValue(FALSE));
    $cache_backend
      ->expects($this->once())
      ->method('set')
      ->with($cid, $this->expectedDefinitions);

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->setCacheBackend($cache_backend, $cid);

    $this->assertEquals($this->expectedDefinitions, $plugin_manager->getDefinitions());
    $this->assertEquals($this->expectedDefinitions['banana'], $plugin_manager->getDefinition('banana'));
  }

  /**
   * Tests the plugin manager with caching and altering.
   */
  public function testDefaultPluginManagerWithFilledCache() {
    $cid = $this->randomMachineName();
    $cache_backend = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_backend
      ->expects($this->once())
      ->method('get')
      ->with($cid)
      ->will($this->returnValue((object) ['data' => $this->expectedDefinitions]));
    $cache_backend
      ->expects($this->never())
      ->method('set');

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->setCacheBackend($cache_backend, $cid);

    $this->assertEquals($this->expectedDefinitions, $plugin_manager->getDefinitions());
  }

  /**
   * Tests the plugin manager with caching disabled.
   */
  public function testDefaultPluginManagerNoCache() {
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    $cid = $this->randomMachineName();
    $cache_backend = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_backend
      ->expects($this->never())
      ->method('get');
    $cache_backend
      ->expects($this->never())
      ->method('set');
    $plugin_manager->setCacheBackend($cache_backend, $cid);

    $plugin_manager->useCaches(FALSE);

    $this->assertEquals($this->expectedDefinitions, $plugin_manager->getDefinitions());
    $this->assertEquals($this->expectedDefinitions['banana'], $plugin_manager->getDefinition('banana'));
  }

  /**
   * Tests the plugin manager cache clear with tags.
   */
  public function testCacheClearWithTags() {
    $cid = $this->randomMachineName();
    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $cache_tags_invalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $cache_tags_invalidator
      ->expects($this->once())
      ->method('invalidateTags')
      ->with(['tag']);
    $cache_backend
      ->expects($this->never())
      ->method('deleteMultiple');

    $this->getContainerWithCacheTagsInvalidator($cache_tags_invalidator);

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->setCacheBackend($cache_backend, $cid, ['tag']);

    $plugin_manager->clearCachedDefinitions();
  }

  /**
   * Tests plugins with the proper interface.
   *
   * @covers ::createInstance
   */
  public function testCreateInstanceWithJustValidInterfaces() {
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    foreach ($this->expectedDefinitions as $plugin_id => $definition) {
      $this->assertNotNull($plugin_manager->createInstance($plugin_id));
    }
  }

  /**
   * Tests plugins without the proper interface.
   *
   * @covers ::createInstance
   */
  public function testCreateInstanceWithInvalidInterfaces() {
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->with('plugin_test')
      ->willReturn(TRUE);

    $this->expectedDefinitions['kale'] = [
      'id' => 'kale',
      'label' => 'Kale',
      'color' => 'green',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale',
      'provider' => 'plugin_test',
    ];
    $this->expectedDefinitions['apple']['provider'] = 'plugin_test';
    $this->expectedDefinitions['banana']['provider'] = 'plugin_test';

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $this->expectException(PluginException::class);
    $this->expectExceptionMessage('Plugin "kale" (Drupal\plugin_test\Plugin\plugin_test\fruit\Kale) must implement interface \Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->createInstance('kale');
  }

  /**
   * Tests plugins without a required interface.
   *
   * @covers ::getDefinitions
   */
  public function testGetDefinitionsWithoutRequiredInterface() {
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->with('plugin_test')
      ->willReturn(FALSE);

    $this->expectedDefinitions['kale'] = [
      'id' => 'kale',
      'label' => 'Kale',
      'color' => 'green',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale',
      'provider' => 'plugin_test',
    ];
    $this->expectedDefinitions['apple']['provider'] = 'plugin_test';
    $this->expectedDefinitions['banana']['provider'] = 'plugin_test';

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler, NULL);
    $this->assertIsArray($plugin_manager->getDefinitions());
  }

  /**
   * @covers ::getCacheContexts
   */
  public function testGetCacheContexts() {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);
    $cache_contexts = $plugin_manager->getCacheContexts();
    $this->assertIsArray($cache_contexts);
    array_map(function ($cache_context) {
      $this->assertIsString($cache_context);
    }, $cache_contexts);
  }

  /**
   * @covers ::getCacheTags
   */
  public function testGetCacheTags() {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);
    $cache_tags = $plugin_manager->getCacheTags();
    $this->assertIsArray($cache_tags);
    array_map(function ($cache_tag) {
      $this->assertIsString($cache_tag);
    }, $cache_tags);
  }

  /**
   * @covers ::getCacheMaxAge
   */
  public function testGetCacheMaxAge() {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);
    $cache_max_age = $plugin_manager->getCacheMaxAge();
    $this->assertIsInt($cache_max_age);
  }

  /**
   * @covers ::findDefinitions
   * @covers ::extractProviderFromDefinition
   */
  public function testProviderExists() {
    $definitions = [];
    $definitions['array_based_found'] = ['provider' => 'module_found'];
    $definitions['array_based_missing'] = ['provider' => 'module_missing'];
    $definitions['stdclass_based_found'] = (object) ['provider' => 'module_found'];
    $definitions['stdclass_based_missing'] = (object) ['provider' => 'module_missing'];
    $definitions['classed_object_found'] = new ObjectDefinition(['provider' => 'module_found']);
    $definitions['classed_object_missing'] = new ObjectDefinition(['provider' => 'module_missing']);

    $expected = [];
    $expected['array_based_found'] = $definitions['array_based_found'];
    $expected['stdclass_based_found'] = $definitions['stdclass_based_found'];
    $expected['classed_object_found'] = $definitions['classed_object_found'];

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->moduleExists('module_found')->willReturn(TRUE)->shouldBeCalled();
    $module_handler->moduleExists('module_missing')->willReturn(FALSE)->shouldBeCalled();
    $plugin_manager = new TestPluginManager($this->namespaces, $definitions, $module_handler->reveal());
    $result = $plugin_manager->getDefinitions();
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::processDefinition
   * @dataProvider providerTestProcessDefinition
   */
  public function testProcessDefinition($definition, $expected) {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManagerWithDefaults($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);

    $plugin_manager->processDefinition($definition, 'the_plugin_id');
    $this->assertEquals($expected, $definition);
  }

  public function providerTestProcessDefinition() {
    $data = [];

    $data['merge'][] = [
      'foo' => [
        'bar' => [
          'asdf',
        ],
      ],
    ];
    $data['merge'][] = [
      'foo' => [
        'bar' => [
          'baz',
          'asdf',
        ],
      ],
    ];

    $object_definition = (object) [
      'foo' => [
        'bar' => [
          'asdf',
        ],
      ],
    ];
    $data['object_definition'] = [$object_definition, clone $object_definition];

    $data['no_form'][] = ['class' => TestPluginForm::class];
    $data['no_form'][] = [
      'class' => TestPluginForm::class,
      'foo' => ['bar' => ['baz']],
    ];

    $data['default_form'][] = ['class' => TestPluginForm::class, 'forms' => ['configure' => 'stdClass']];
    $data['default_form'][] = [
      'class' => TestPluginForm::class,
      'forms' => ['configure' => 'stdClass'],
      'foo' => ['bar' => ['baz']],
    ];

    $data['class_with_slashes'][] = [
      'class' => '\Drupal\Tests\Core\Plugin\TestPluginForm',
    ];
    $data['class_with_slashes'][] = [
      'class' => 'Drupal\Tests\Core\Plugin\TestPluginForm',
      'foo' => ['bar' => ['baz']],
    ];

    $data['object_with_class_with_slashes'][] = (new PluginDefinition())->setClass('\Drupal\Tests\Core\Plugin\TestPluginForm');
    $data['object_with_class_with_slashes'][] = (new PluginDefinition())->setClass('Drupal\Tests\Core\Plugin\TestPluginForm');
    return $data;
  }

}

class TestPluginManagerWithDefaults extends TestPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'foo' => [
      'bar' => [
        'baz',
      ],
    ],
  ];

}

class TestPluginForm implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
class ObjectDefinition extends PluginDefinition {

  /**
   * ObjectDefinition constructor.
   *
   * @param array $definition
   *   An associative array defining the plugin.
   */
  public function __construct(array $definition) {
    // This class does not exist but plugin definitions must provide a class.
    $this->class = 'PluginObject';
    foreach ($definition as $property => $value) {
      $this->{$property} = $value;
    }
  }

}
