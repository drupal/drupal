<?php

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\Discovery\HookDiscovery;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Discovery\HookDiscovery
 * @group Plugin
 */
class HookDiscoveryTest extends UnitTestCase {

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The tested hook discovery.
   *
   * @var \Drupal\Core\Plugin\Discovery\HookDiscovery
   */
  protected $hookDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->hookDiscovery = new HookDiscovery($this->moduleHandler, 'test_plugin');
  }

  /**
   * Tests the getDefinitions() method without any plugins.
   *
   * @see \Drupal\Core\Plugin\Discovery::getDefinitions()
   */
  public function testGetDefinitionsWithoutPlugins() {
    $this->moduleHandler->expects($this->once())
      ->method('getImplementations')
      ->with('test_plugin')
      ->will($this->returnValue([]));

    $this->assertCount(0, $this->hookDiscovery->getDefinitions());
  }

  /**
   * Tests the getDefinitions() method with some plugins.
   *
   * @see \Drupal\Core\Plugin\Discovery::getDefinitions()
   */
  public function testGetDefinitions() {
    $this->moduleHandler->expects($this->once())
      ->method('getImplementations')
      ->with('test_plugin')
      ->will($this->returnValue(['hook_discovery_test', 'hook_discovery_test2']));

    $this->moduleHandler->expects($this->exactly(2))
      ->method('invoke')
      ->willReturnMap([
        ['hook_discovery_test', 'test_plugin', [], $this->hookDiscoveryTestTestPlugin()],
        ['hook_discovery_test2', 'test_plugin', [], $this->hookDiscoveryTest2TestPlugin()],
      ]);

    $definitions = $this->hookDiscovery->getDefinitions();

    $this->assertCount(3, $definitions);
    $this->assertEquals('Drupal\plugin_test\Plugin\plugin_test\fruit\Apple', $definitions['test_id_1']['class']);
    $this->assertEquals('Drupal\plugin_test\Plugin\plugin_test\fruit\Orange', $definitions['test_id_2']['class']);
    $this->assertEquals('Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry', $definitions['test_id_3']['class']);

    // Ensure that the module was set.
    $this->assertEquals('hook_discovery_test', $definitions['test_id_1']['provider']);
    $this->assertEquals('hook_discovery_test', $definitions['test_id_2']['provider']);
    $this->assertEquals('hook_discovery_test2', $definitions['test_id_3']['provider']);
  }

  /**
   * Tests the getDefinition method with some plugins.
   *
   * @see \Drupal\Core\Plugin\Discovery::getDefinition()
   */
  public function testGetDefinition() {
    $this->moduleHandler->expects($this->exactly(4))
      ->method('getImplementations')
      ->with('test_plugin')
      ->will($this->returnValue(['hook_discovery_test', 'hook_discovery_test2']));

    $this->moduleHandler->expects($this->any())
      ->method('invoke')
      ->will($this->returnValueMap([
          ['hook_discovery_test', 'test_plugin', [], $this->hookDiscoveryTestTestPlugin()],
          ['hook_discovery_test2', 'test_plugin', [], $this->hookDiscoveryTest2TestPlugin()],
        ]
      ));

    $this->assertNull($this->hookDiscovery->getDefinition('test_non_existent', FALSE));

    $plugin_definition = $this->hookDiscovery->getDefinition('test_id_1');
    $this->assertEquals('Drupal\plugin_test\Plugin\plugin_test\fruit\Apple', $plugin_definition['class']);
    $this->assertEquals('hook_discovery_test', $plugin_definition['provider']);

    $plugin_definition = $this->hookDiscovery->getDefinition('test_id_2');
    $this->assertEquals('Drupal\plugin_test\Plugin\plugin_test\fruit\Orange', $plugin_definition['class']);
    $this->assertEquals('hook_discovery_test', $plugin_definition['provider']);

    $plugin_definition = $this->hookDiscovery->getDefinition('test_id_3');
    $this->assertEquals('Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry', $plugin_definition['class']);
    $this->assertEquals('hook_discovery_test2', $plugin_definition['provider']);
  }

  /**
   * Tests the getDefinition method with an unknown plugin ID.
   *
   * @see \Drupal\Core\Plugin\Discovery::getDefinition()
   */
  public function testGetDefinitionWithUnknownID() {
    $this->moduleHandler->expects($this->once())
      ->method('getImplementations')
      ->will($this->returnValue([]));

    $this->expectException(PluginNotFoundException::class);
    $this->hookDiscovery->getDefinition('test_non_existent', TRUE);
  }

  protected function hookDiscoveryTestTestPlugin() {
    return [
      'test_id_1' => ['class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple'],
      'test_id_2' => ['class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Orange'],
    ];
  }

  protected function hookDiscoveryTest2TestPlugin() {
    return [
      'test_id_3' => ['class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry'],
    ];
  }

}
