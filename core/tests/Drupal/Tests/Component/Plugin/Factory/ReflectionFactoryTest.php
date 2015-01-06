<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Plugin\Factory\ReflectionFactoryTest.
 *
 * Also contains Argument* classes used as data for testing.
 */

namespace Drupal\Tests\Component\Plugin\Factory;

use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Tests\UnitTestCase;

/**
 * @group Plugin
 * @coversDefaultClass Drupal\Component\Plugin\Factory\ReflectionFactory
 */
class ReflectionFactoryTest extends UnitTestCase {

  /**
   * Data provider for testGetInstanceArguments.
   *
   * The classes used here are defined at the bottom of this file.
   *
   * @return array
   *   - Expected output.
   *   - Class to reflect for input to getInstanceArguments().
   *   - $plugin_id parameter to getInstanceArguments().
   *   - $plugin_definition parameter to getInstanceArguments().
   *   - $configuration parameter to getInstanceArguments().
   */
  public function providerGetInstanceArguments() {
    return [
      [
        ['arguments_plugin_id'],
        'Drupal\Tests\Component\Plugin\Factory\ArgumentsPluginId',
        'arguments_plugin_id',
        ['arguments_plugin_id' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsPluginId']],
        [],
      ],
      [
        [[], ['arguments_many' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsMany']], 'arguments_many', 'default_value', 'what_default'],
        'Drupal\Tests\Component\Plugin\Factory\ArgumentsMany',
        'arguments_many',
        ['arguments_many' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsMany']],
        [],
      ],
      [
        // Config array key exists and is set.
        ['thing'],
        'Drupal\Tests\Component\Plugin\Factory\ArgumentsConfigArrayKey',
        'arguments_config_array_key',
        ['arguments_config_array_key' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsConfigArrayKey']],
        ['config_name' => 'thing'],
      ],
      [
        // Config array key exists and is not set.
        [NULL],
        'Drupal\Tests\Component\Plugin\Factory\ArgumentsConfigArrayKey',
        'arguments_config_array_key',
        ['arguments_config_array_key' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsConfigArrayKey']],
        ['config_name' => NULL],
      ],
      [
        // Touch the else clause at the end of the method.
        [NULL, NULL, NULL, NULL],
        'Drupal\Tests\Component\Plugin\Factory\ArgumentsAllNull',
        'arguments_all_null',
        ['arguments_all_null' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsAllNull']],
        [],
      ],
      [
        // A plugin with no constructor.
        [NULL, NULL, NULL, NULL],
        'Drupal\Tests\Component\Plugin\Factory\ArgumentsNoConstructor',
        'arguments_no_constructor',
        ['arguments_no_constructor' => ['class' => 'Drupal\Tests\Component\Plugin\Factory\ArgumentsNoConstructor']],
        [],
      ],
    ];
  }

  /**
   * @covers ::createInstance
   * @dataProvider providerGetInstanceArguments
   */
  public function testCreateInstance($expected, $reflector_name, $plugin_id, $plugin_definition, $configuration) {
    // Create a mock DiscoveryInterface which can return our plugin definition.
    $mock_discovery = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\DiscoveryInterface')
      ->setMethods(array('getDefinition', 'getDefinitions', 'hasDefinition'))
      ->getMock();
    $mock_discovery->expects($this->never())->method('getDefinitions');
    $mock_discovery->expects($this->never())->method('hasDefinition');
    $mock_discovery->expects($this->once())
      ->method('getDefinition')
      ->willReturn($plugin_definition);

    // Create a stub ReflectionFactory object. We use StubReflectionFactory
    // because createInstance() has a dependency on a static method.
    // StubReflectionFactory overrides this static method.
    $reflection_factory = new StubReflectionFactory($mock_discovery);

    // Finally test that createInstance() returns an object of the class we
    // want.
    $this->assertInstanceOf($reflector_name, $reflection_factory->createInstance($plugin_id));
  }

  /**
   * @covers ::getInstanceArguments
   * @dataProvider providerGetInstanceArguments
   */
  public function testGetInstanceArguments($expected, $reflector_name, $plugin_id, $plugin_definition, $configuration) {
    $reflection_factory = $this->getMockBuilder('Drupal\Component\Plugin\Factory\ReflectionFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $get_instance_arguments_ref = new \ReflectionMethod($reflection_factory, 'getInstanceArguments');
    $get_instance_arguments_ref->setAccessible(TRUE);

    // Special case for plugin class without a constructor.
    // getInstanceArguments() throws an exception if there's no constructor.
    // This is not a documented behavior of getInstanceArguments(), but allows
    // us to use one data set for this test method as well as
    // testCreateInstance().
    if ($plugin_id == 'arguments_no_constructor') {
      $this->setExpectedException('\ReflectionException');
    }

    // Finally invoke getInstanceArguments() on our mocked factory.
    $ref = new \ReflectionClass($reflector_name);
    $result = $get_instance_arguments_ref->invoke(
      $reflection_factory, $ref, $plugin_id, $plugin_definition, $configuration);
    $this->assertEquals($expected, $result);
  }

}

/**
 * Override ReflectionFactory because ::createInstance() calls a static method.
 *
 * We have to override getPluginClass so that we can stub out its return value.
 */
class StubReflectionFactory extends ReflectionFactory {

  /**
   * {@inheritdoc}
   */
  public static function getPluginClass($plugin_id, $plugin_definition = NULL, $required_interface = NULL) {
    // Return the class name from the plugin definition.
    return $plugin_definition[$plugin_id]['class'];
  }
}

/**
 * A stub class used by testGetInstanceArguments().
 *
 * @see providerGetInstanceArguments()
 */
class ArgumentsPluginId {

  public function __construct($plugin_id) {
    // No-op.
  }

}

/**
 * A stub class used by testGetInstanceArguments().
 *
 * @see providerGetInstanceArguments()
 */
class ArgumentsMany {

  public function __construct(
  $configuration, $plugin_definition, $plugin_id, $foo = 'default_value', $what_am_i_doing_here = 'what_default'
  ) {
    // No-op.
  }

}

/**
 * A stub class used by testGetInstanceArguments().
 *
 * @see providerGetInstanceArguments()
 */
class ArgumentsConfigArrayKey {

  public function __construct($config_name) {
    // No-op.
  }

}

/**
 * A stub class used by testGetInstanceArguments().
 *
 * @see providerGetInstanceArguments()
 */
class ArgumentsAllNull {

  public function __construct($charismatic, $demure, $delightful, $electrostatic) {
    // No-op.
  }

}

/**
 * A stub class used by testGetInstanceArguments().
 *
 * @see providerGetInstanceArguments()
 */
class ArgumentsNoConstructor {

}
