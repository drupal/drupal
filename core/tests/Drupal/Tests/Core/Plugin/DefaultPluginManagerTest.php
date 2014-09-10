<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\DefaultPluginManagerTest.
 */

namespace Drupal\Tests\Core\Plugin;

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
        ),
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
      ),
    );

    $this->namespaces = new \ArrayObject();
    $this->namespaces['Drupal\plugin_test'] = DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/src';
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
   * Tests the plugin manager cache clear with tags.
   */
  public function testCacheClearWithTags() {
    $cid = $this->randomMachineName();
    $cache_backend = $this->getMockBuilder('Drupal\Core\Cache\MemoryBackend')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_backend
      ->expects($this->once())
      ->method('deleteTags')
      ->with(array('tag' => TRUE));
    $cache_backend
      ->expects($this->never())
      ->method('deleteMultiple');

    $this->getContainerWithCacheBins($cache_backend);

    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
    $plugin_manager->setCacheBackend($cache_backend, $cid, array('tag' => TRUE));

    $plugin_manager->clearCachedDefinitions();
  }

  /**
   * Tests plugins with the proper interface.
   *
   * @covers ::createInstance
   * @covers ::enforcePluginInterface
   */
  public function testCreateInstanceWithJustValidInterfaces() {
    $plugin_manager = new TestPluginManager($this->namespaces, $this->expectedDefinitions, NULL, NULL, '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    foreach ($this->expectedDefinitions as $plugin_id => $definition) {
      $plugin_manager->createInstance($plugin_id);
    }
  }

  /**
   * Tests plugins without the proper interface.
   *
   * @covers ::createInstance
   * @covers ::enforcePluginInterface
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   * @expectedExceptionMessage Plugin "kale" (Drupal\plugin_test\Plugin\plugin_test\fruit\Kale) in plugin_test should implement interface \Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface
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
   * @covers ::enforcePluginInterface
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
    $plugin_manager->getDefinitions();
  }

}
