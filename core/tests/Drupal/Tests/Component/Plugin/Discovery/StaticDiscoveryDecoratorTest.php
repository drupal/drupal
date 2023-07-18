<?php

namespace Drupal\Tests\Component\Plugin\Discovery;

use PHPUnit\Framework\TestCase;

/**
 * @group Plugin
 * @coversDefaultClass \Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator
 */
class StaticDiscoveryDecoratorTest extends TestCase {

  /**
   * Helper method to provide a mocked callback object with expectations.
   *
   * If there should be a registered definition, then we have to place a
   * \Callable in the mock object. The return value of this callback is
   * never used.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mocked object with expectation of registerDefinitionsCallback() being
   *   called once.
   */
  public function getRegisterDefinitionsCallback() {
    $mock_callable = $this->getMockBuilder(StaticDiscoveryDecoratorTestMockInterface::class)
      ->onlyMethods(['registerDefinitionsCallback'])
      ->getMock();
    // Set expectations for the callback method.
    $mock_callable->expects($this->once())
      ->method('registerDefinitionsCallback');
    return $mock_callable;
  }

  /**
   * Data provider for testGetDefinitions().
   *
   * @return array
   *   - Expected plugin definition.
   *   - Whether we require the method to register definitions through a
   *     callback.
   *   - Whether to throw an exception if the definition is invalid.
   *   - A plugin definition.
   *   - Base plugin ID.
   */
  public function providerGetDefinition() {
    return [
      ['is_defined', TRUE, FALSE, ['plugin-definition' => 'is_defined'], 'plugin-definition'],
      // Make sure we don't call the decorated method if we shouldn't.
      ['is_defined', FALSE, FALSE, ['plugin-definition' => 'is_defined'], 'plugin-definition'],
      // Return NULL for bad plugin id.
      [NULL, FALSE, FALSE, ['plugin-definition' => 'is_defined'], 'BAD-plugin-definition'],
      // Generate an exception.
      [NULL, FALSE, TRUE, ['plugin-definition' => 'is_defined'], 'BAD-plugin-definition'],
    ];
  }

  /**
   * @covers ::getDefinition
   * @dataProvider providerGetDefinition
   */
  public function testGetDefinition($expected, $has_register_definitions, $exception_on_invalid, $definitions, $base_plugin_id) {
    // Mock our StaticDiscoveryDecorator.
    $mock_decorator = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator')
      ->disableOriginalConstructor()
      ->addMethods(['registeredDefinitionCallback'])
      ->getMock();

    // Set up the ::$registerDefinitions property.
    $ref_register_definitions = new \ReflectionProperty($mock_decorator, 'registerDefinitions');
    if ($has_register_definitions) {
      // Set the callback object on the mocked decorator.
      $ref_register_definitions->setValue(
        $mock_decorator,
        [$this->getRegisterDefinitionsCallback(), 'registerDefinitionsCallback']
      );
    }
    else {
      // There should be no registerDefinitions callback.
      $ref_register_definitions->setValue($mock_decorator, NULL);
    }

    // Set up ::$definitions to an empty array.
    $ref_definitions = new \ReflectionProperty($mock_decorator, 'definitions');
    $ref_definitions->setValue($mock_decorator, []);

    // Mock a decorated object.
    $mock_decorated = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\DiscoveryInterface')
      ->onlyMethods(['getDefinitions'])
      ->getMockForAbstractClass();
    // Return our definitions from getDefinitions().
    $mock_decorated->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    // Set up ::$decorated to our mocked decorated object.
    $ref_decorated = new \ReflectionProperty($mock_decorator, 'decorated');
    $ref_decorated->setValue($mock_decorator, $mock_decorated);

    if ($exception_on_invalid) {
      $this->expectException('Drupal\Component\Plugin\Exception\PluginNotFoundException');
    }

    // Exercise getDefinition(). It calls parent::getDefinition().
    $this->assertEquals(
      $expected,
      $mock_decorator->getDefinition($base_plugin_id, $exception_on_invalid)
    );
  }

  /**
   * Data provider for testGetDefinitions().
   *
   * @return array
   *   - bool Whether the test mock has a callback.
   *   - array Plugin definitions.
   */
  public function providerGetDefinitions() {
    return [
      [TRUE, ['definition' => 'is_fake']],
      [FALSE, ['definition' => 'array_of_stuff']],
    ];
  }

  /**
   * @covers ::getDefinitions
   * @dataProvider providerGetDefinitions
   */
  public function testGetDefinitions($has_register_definitions, $definitions) {
    // Mock our StaticDiscoveryDecorator.
    $mock_decorator = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator')
      ->disableOriginalConstructor()
      ->addMethods(['registeredDefinitionCallback'])
      ->getMock();

    // Set up the ::$registerDefinitions property.
    $ref_register_definitions = new \ReflectionProperty($mock_decorator, 'registerDefinitions');
    if ($has_register_definitions) {
      // Set the callback object on the mocked decorator.
      $ref_register_definitions->setValue(
        $mock_decorator,
        [$this->getRegisterDefinitionsCallback(), 'registerDefinitionsCallback']
      );
    }
    else {
      // There should be no registerDefinitions callback.
      $ref_register_definitions->setValue($mock_decorator, NULL);
    }

    // Set up ::$definitions to an empty array.
    $ref_definitions = new \ReflectionProperty($mock_decorator, 'definitions');
    $ref_definitions->setValue($mock_decorator, []);

    // Mock a decorated object.
    $mock_decorated = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\DiscoveryInterface')
      ->onlyMethods(['getDefinitions'])
      ->getMockForAbstractClass();
    // Our mocked method will return any arguments sent to it.
    $mock_decorated->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    // Set up ::$decorated to our mocked decorated object.
    $ref_decorated = new \ReflectionProperty($mock_decorator, 'decorated');
    $ref_decorated->setValue($mock_decorator, $mock_decorated);

    // Exercise getDefinitions(). It calls parent::getDefinitions() but in this
    // case there will be no side-effects.
    $this->assertEquals(
      $definitions,
      $mock_decorator->getDefinitions()
    );
  }

  /**
   * Data provider for testCall().
   *
   * @return array
   *   - Method name.
   *   - Array of arguments to pass to the method, with the expectation that our
   *     mocked __call() will return them.
   */
  public function providerCall() {
    return [
      ['complexArguments', ['1', 2.0, 3, ['4' => 'five']]],
      ['noArguments', []],
    ];
  }

  /**
   * @covers ::__call
   * @dataProvider providerCall
   */
  public function testCall($method, $args) {
    // Mock a decorated object.
    $mock_decorated = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\DiscoveryInterface')
      ->addMethods([$method])
      ->getMockForAbstractClass();
    // Our mocked method will return any arguments sent to it.
    $mock_decorated->expects($this->once())
      ->method($method)
      ->willReturnCallback(
        function () {
          return \func_get_args();
        }
      );

    // Create a mock decorator.
    $mock_decorator = $this->getMockBuilder('Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator')
      ->disableOriginalConstructor()
      ->getMock();
    // Poke the decorated object into our decorator.
    $ref_decorated = new \ReflectionProperty($mock_decorator, 'decorated');
    $ref_decorated->setValue($mock_decorator, $mock_decorated);

    // Exercise __call.
    $this->assertEquals(
      $args,
      \call_user_func_array([$mock_decorated, $method], $args)
    );
  }

}

/**
 * Interface used in the mocking process of this test.
 */
interface StaticDiscoveryDecoratorTestMockInterface {

  /**
   * Function used in the mocking process of this test.
   */
  public function registerDefinitionsCallback();

}
