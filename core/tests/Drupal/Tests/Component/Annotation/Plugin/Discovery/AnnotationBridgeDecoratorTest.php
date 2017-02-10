<?php

namespace Drupal\Tests\Component\Annotation\Plugin\Discovery;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator
 * @group Plugin
 */
class AnnotationBridgeDecoratorTest extends UnitTestCase {

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    $definitions = [];
    $definitions['object'] = new ObjectDefinition(['id' => 'foo']);
    $definitions['array'] = ['id' => 'bar'];
    $discovery = $this->prophesize(DiscoveryInterface::class);
    $discovery->getDefinitions()->willReturn($definitions);

    $decorator = new AnnotationBridgeDecorator($discovery->reveal(), TestAnnotation::class);

    $expected = [
      'object' => new ObjectDefinition(['id' => 'foo']),
      'array' => new ObjectDefinition(['id' => 'bar']),
    ];
    $this->assertEquals($expected, $decorator->getDefinitions());
  }

}

class TestAnnotation extends Plugin {

  /**
   * {@inheritdoc}
   */
  public function get() {
    return new ObjectDefinition($this->definition);
  }

}
class ObjectDefinition extends PluginDefinition {

  /**
   * ObjectDefinition constructor.
   *
   * @param array $definition
   */
  public function __construct(array $definition) {
    foreach ($definition as $property => $value) {
      $this->{$property} = $value;
    }
  }

}
