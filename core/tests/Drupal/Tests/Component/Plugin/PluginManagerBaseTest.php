<?php

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Plugin\PluginManagerBase;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\PluginManagerBase
 * @group Plugin
 */
class PluginManagerBaseTest extends TestCase {

  /**
   * A callback method for mocking FactoryInterface objects.
   */
  public function createInstanceCallback() {
    $args = func_get_args();
    $plugin_id = $args[0];
    $configuration = $args[1];
    if ('invalid' == $plugin_id) {
      throw new PluginNotFoundException($plugin_id);
    }
    return [
      'plugin_id' => $plugin_id,
      'configuration' => $configuration,
    ];
  }

  /**
   * Generates a mocked FactoryInterface object with known properties.
   */
  public function getMockFactoryInterface($expects_count) {
    $mock_factory = $this->getMockBuilder('Drupal\Component\Plugin\Factory\FactoryInterface')
      ->setMethods(['createInstance'])
      ->getMockForAbstractClass();
    $mock_factory->expects($this->exactly($expects_count))
      ->method('createInstance')
      ->willReturnCallback([$this, 'createInstanceCallback']);
    return $mock_factory;
  }

  /**
   * Tests createInstance() with no fallback methods.
   *
   * @covers ::createInstance
   */
  public function testCreateInstance() {
    $manager = $this->getMockBuilder('Drupal\Component\Plugin\PluginManagerBase')
      ->getMockForAbstractClass();
    // PluginManagerBase::createInstance() looks for a factory object and then
    // calls createInstance() on it. So we have to mock a factory object.
    $factory_ref = new \ReflectionProperty($manager, 'factory');
    $factory_ref->setAccessible(TRUE);
    $factory_ref->setValue($manager, $this->getMockFactoryInterface(1));

    // Finally the test.
    $configuration_array = ['config' => 'something'];
    $result = $manager->createInstance('valid', $configuration_array);
    $this->assertEquals('valid', $result['plugin_id']);
    $this->assertEquals($configuration_array, $result['configuration']);
  }

  /**
   * Tests createInstance() with a fallback method.
   *
   * @covers ::createInstance
   */
  public function testCreateInstanceFallback() {
    // We use our special stub class which extends PluginManagerBase and also
    // implements FallbackPluginManagerInterface.
    $manager = new StubFallbackPluginManager();
    // Put our stubbed factory on the base object.
    $factory_ref = new \ReflectionProperty($manager, 'factory');
    $factory_ref->setAccessible(TRUE);

    // Set up the configuration array.
    $configuration_array = ['config' => 'something'];

    // Test with fallback interface and valid plugin_id.
    $factory_ref->setValue($manager, $this->getMockFactoryInterface(1));
    $no_fallback_result = $manager->createInstance('valid', $configuration_array);
    $this->assertEquals('valid', $no_fallback_result['plugin_id']);
    $this->assertEquals($configuration_array, $no_fallback_result['configuration']);

    // Test with fallback interface and invalid plugin_id.
    $factory_ref->setValue($manager, $this->getMockFactoryInterface(2));
    $fallback_result = $manager->createInstance('invalid', $configuration_array);
    $this->assertEquals('invalid_fallback', $fallback_result['plugin_id']);
    $this->assertEquals($configuration_array, $fallback_result['configuration']);
  }

  /**
   * @covers ::getInstance
   */
  public function testGetInstance() {
    $options = [
      'foo' => 'F00',
      'bar' => 'bAr',
    ];
    $instance = new \stdClass();
    $mapper = $this->prophesize(MapperInterface::class);
    $mapper->getInstance($options)
      ->shouldBeCalledTimes(1)
      ->willReturn($instance);
    $manager = new StubPluginManagerBaseWithMapper($mapper->reveal());
    $this->assertEquals($instance, $manager->getInstance($options));
  }

  /**
   * @covers ::getInstance
   */
  public function testGetInstanceWithoutMapperShouldThrowException() {
    $options = [
      'foo' => 'F00',
      'bar' => 'bAr',
    ];
    /** @var \Drupal\Component\Plugin\PluginManagerBase $manager */
    $manager = $this->getMockBuilder(PluginManagerBase::class)
      ->getMockForAbstractClass();
    // Set the expected exception thrown by ::getInstance.
    if (method_exists($this, 'expectException')) {
      $this->expectException(\BadMethodCallException::class);
      $this->expectExceptionMessage(sprintf('%s does not support this method unless %s::$mapper is set.', get_class($manager), get_class($manager)));
    }
    else {
      $this->setExpectedException(\BadMethodCallException::class, sprintf('%s does not support this method unless %s::$mapper is set.', get_class($manager), get_class($manager)));
    }
    $manager->getInstance($options);
  }

}
