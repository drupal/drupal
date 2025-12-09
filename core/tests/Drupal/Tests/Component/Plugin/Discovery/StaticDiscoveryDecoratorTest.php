<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator.
 */
#[CoversClass(StaticDiscoveryDecorator::class)]
#[Group('Plugin')]
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
    $mock_callable = $this->createMock(StaticDiscoveryDecoratorTestMockInterface::class);
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
  public static function providerGetDefinition(): array {
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
   * @legacy-covers ::getDefinition
   */
  #[DataProvider('providerGetDefinition')]
  public function testGetDefinition($expected, $has_register_definitions, $exception_on_invalid, $definitions, $base_plugin_id): void {
    // Mock our StaticDiscoveryDecorator.
    $mock_decorator = $this->getMockBuilder(StaticDiscoveryDecorator::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
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
    $mock_decorated = $this->createMock(DiscoveryInterface::class);
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
  public static function providerGetDefinitions(): array {
    return [
      [TRUE, ['definition' => 'is_fake']],
      [FALSE, ['definition' => 'array_of_stuff']],
    ];
  }

  /**
   * @legacy-covers ::getDefinitions
   */
  #[DataProvider('providerGetDefinitions')]
  public function testGetDefinitions($has_register_definitions, $definitions): void {
    // Mock our StaticDiscoveryDecorator.
    $mock_decorator = $this->getMockBuilder(StaticDiscoveryDecorator::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
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
    $mock_decorated = $this->createMock(DiscoveryInterface::class);
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
  public static function providerCall(): array {
    return [
      ['complexArguments', ['1', 2.0, 3, ['4' => 'five']]],
      ['noArguments', []],
    ];
  }

  /**
   * @legacy-covers ::__call
   */
  #[DataProvider('providerCall')]
  public function testCall($method, $args): void {
    // Mock a decorated object.
    $mock_decorated = $this->getMockBuilder(StaticDiscoveryTestDecoratedClass::class)
      ->onlyMethods([$method])
      ->getMock();
    // Our mocked method will return any arguments sent to it.
    $mock_decorated->expects($this->once())
      ->method($method)
      ->willReturnCallback(
        function () {
          return \func_get_args();
        }
      );

    // Create the decorator.
    $decorator = new StaticDiscoveryDecorator($mock_decorated);

    // Exercise __call on the decorator.
    $this->assertEquals(
      $args,
      \call_user_func_array([$decorator, $method], $args)
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

/**
 * A class extending StaticDiscovery for testing purposes.
 */
class StaticDiscoveryTestDecoratedClass extends StaticDiscovery {

  public function getDefinitions(): array {
    return [];
  }

  public function complexArguments(mixed ...$args): array {
    return $args;
  }

  public function noArguments(): array {
    return [];
  }

}
