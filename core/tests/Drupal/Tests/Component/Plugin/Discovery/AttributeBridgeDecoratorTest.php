<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Discovery;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Discovery\AttributeBridgeDecorator;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator
 * @group Plugin
 */
class AttributeBridgeDecoratorTest extends TestCase {

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions(): void {
    // Normally the attribute classes would be autoloaded.
    include_once __DIR__ . '/../Attribute/Fixtures/CustomPlugin.php';
    include_once __DIR__ . '/../Attribute/Fixtures/Plugins/PluginNamespace/AttributeDiscoveryTest1.php';

    $definitions = [];
    $definitions['object'] = new ObjectDefinition(['id' => 'foo']);
    $definitions['array'] = [
      'id' => 'bar',
      'class' => 'com\example\PluginNamespace\AttributeDiscoveryTest1',
    ];
    $discovery = $this->createMock(DiscoveryInterface::class);
    $discovery->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $decorator = new AttributeBridgeDecorator($discovery, TestAttribute::class);

    $expected = [
      'object' => new ObjectDefinition(['id' => 'foo']),
      'array' => (new ObjectDefinition(['id' => 'bar']))->setClass('com\example\PluginNamespace\AttributeDiscoveryTest1'),
    ];
    $this->assertEquals($expected, $decorator->getDefinitions());
  }

  /**
   * Tests that the decorator of other methods works.
   *
   * @covers ::__call
   */
  public function testOtherMethod(): void {
    // Normally the attribute classes would be autoloaded.
    include_once __DIR__ . '/../Attribute/Fixtures/CustomPlugin.php';
    include_once __DIR__ . '/../Attribute/Fixtures/Plugins/PluginNamespace/AttributeDiscoveryTest1.php';

    $discovery = $this->createMock(ExtendedDiscoveryInterface::class);
    $discovery->expects($this->exactly(2))
      ->method('otherMethod')
      ->willReturnCallback(fn($id) => $id === 'foo');

    $decorator = new AttributeBridgeDecorator($discovery, TestAttribute::class);

    $this->assertTrue($decorator->otherMethod('foo'));
    $this->assertFalse($decorator->otherMethod('bar'));
  }

}

interface ExtendedDiscoveryInterface extends DiscoveryInterface {

  public function otherMethod(string $id): bool;

}

/**
 * {@inheritdoc}
 */
class TestAttribute extends Plugin {

  /**
   * {@inheritdoc}
   */
  public function get(): object {
    return new ObjectDefinition(parent::get());
  }

}

/**
 * {@inheritdoc}
 */
class ObjectDefinition extends PluginDefinition {

  /**
   * ObjectDefinition constructor.
   *
   * @param array $definition
   *   An array of definition values.
   */
  public function __construct(array $definition) {
    foreach ($definition as $property => $value) {
      $this->{$property} = $value;
    }
  }

}
