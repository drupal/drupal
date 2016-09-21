<?php

namespace Drupal\Tests\Core\Plugin;

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
  protected function setUp() {
    $this->expectedDefinitions = array(
      'apple' => array(
        'id' => 'apple',
        'label' => 'Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple',
      ),
      'banana' => array(
        'id' => 'banana',
        'label' => 'Banana',
        'color' => 'yellow',
        'uses' => array(
          'bread' => 'Banana bread',
          'loaf' => array(
            'singular' => '@count loaf',
            'plural' => '@count loaves',
          ),
        ),
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
      ),
    );

    $this->namespaces = new \ArrayObject();
    $this->namespaces['Drupal\plugin_test'] = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
  }

  /**
   * Tests the plugin manager with a plugin that extends a non-installed class.
   */
  public function testDefaultPluginManagerWithPluginExtendingNonInstalledClass() {
    $definitions = array();
    $definitions['extending_non_installed_class'] = array(
      'id' => 'extending_non_installed_class',
      'label' => 'A plugin whose class is extending from a non-installed module class',
      'color' => 'pink',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\ExtendingNonInstalledClass',
      'provider' => 'plugin_test',
    );

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $plugin_manager = new TestPluginManager($this->namespaces, $definitions, $module_handler, 'test_alter_hook', '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->getDefinition('plugin_test', FALSE);
    $this->assertTrue(TRUE, 'No PHP fatal error occurred when retrieving the definitions of a module with plugins that depend on a non-installed module class should not cause a PHP fatal.');
  }

  /**
   * Tests the plugin manager with a disabled module.
   */
  public function testDefaultPluginManagerWithDisabledModule() {
    $definitions = $this->expectedDefinitions;
    $definitions['cherry'] = array(
      'id' => 'cherry',
      'label' => 'Cherry',
      'color' => 'red',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
      'provider' => 'disabled_module',
    );

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

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
    $definitions['cherry'] = (object) array(
      'id' => 'cherry',
      'label' => 'Cherry',
      'color' => 'red',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
      'provider' => 'disabled_module',
    );

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->once())
      ->method('moduleExists')
      ->with('disabled_module')
      ->will($this->returnValue(FALSE));

    $plugin_manager = new TestPluginManager($this->namespaces, $definitions, $module_handler, 'test_alter_hook', '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    $this->assertEmpty($plugin_manager->getDefinition('cherry', FALSE), 'Plugin information is available');
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
      ->will($this->returnValue((object) array('data' => $this->expectedDefinitions)));
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
    $cache_backend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $cache_tags_invalidator = $this->getMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $cache_tags_invalidator
      ->expects($this->once())
      ->method('invalidateTags')
      ->with(array('tag'));
    $cache_backend
      ->expects($this->never())
      ->method('deleteMultiple');

    $this->getContainerWithCacheTagsInvalidator($cache_tags_invalidator);

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->setCacheBackend($cache_backend, $cid, array('tag'));

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
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   * @expectedExceptionMessage Plugin "kale" (Drupal\plugin_test\Plugin\plugin_test\fruit\Kale) must implement interface \Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface
   */
  public function testCreateInstanceWithInvalidInterfaces() {
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->with('plugin_test')
      ->willReturn(TRUE);

    $this->expectedDefinitions['kale'] = array(
      'id' => 'kale',
      'label' => 'Kale',
      'color' => 'green',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale',
      'provider' => 'plugin_test',
    );
    $this->expectedDefinitions['apple']['provider'] = 'plugin_test';
    $this->expectedDefinitions['banana']['provider'] = 'plugin_test';

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->createInstance('kale');
  }

  /**
   * Tests plugins without a required interface.
   *
   * @covers ::getDefinitions
   */
  public function testGetDefinitionsWithoutRequiredInterface() {
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->with('plugin_test')
      ->willReturn(FALSE);

    $this->expectedDefinitions['kale'] = array(
      'id' => 'kale',
      'label' => 'Kale',
      'color' => 'green',
      'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale',
      'provider' => 'plugin_test',
    );
    $this->expectedDefinitions['apple']['provider'] = 'plugin_test';
    $this->expectedDefinitions['banana']['provider'] = 'plugin_test';

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler, NULL);
    $this->assertInternalType('array', $plugin_manager->getDefinitions());
  }

  /**
   * @covers ::getCacheContexts
   */
  public function testGetCacheContexts() {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);
    $cache_contexts = $plugin_manager->getCacheContexts();
    $this->assertInternalType('array', $cache_contexts);
    array_map(function ($cache_context) {
      $this->assertInternalType('string', $cache_context);
    }, $cache_contexts);
  }

  /**
   * @covers ::getCacheTags
   */
  public function testGetCacheTags() {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);
    $cache_tags = $plugin_manager->getCacheTags();
    $this->assertInternalType('array', $cache_tags);
    array_map(function ($cache_tag) {
      $this->assertInternalType('string', $cache_tag);
    }, $cache_tags);
  }

  /**
   * @covers ::getCacheMaxAge
   */
  public function testGetCacheMaxAge() {
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, $module_handler->reveal(), NULL);
    $cache_max_age = $plugin_manager->getCacheMaxAge();
    $this->assertInternalType('int', $cache_max_age);
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
