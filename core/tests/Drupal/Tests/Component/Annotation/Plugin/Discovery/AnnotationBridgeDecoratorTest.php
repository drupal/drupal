<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Annotation\Plugin\Discovery;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator.
 */
#[CoversClass(AnnotationBridgeDecorator::class)]
#[Group('Plugin')]
class AnnotationBridgeDecoratorTest extends TestCase {

  use ProphecyTrait;

  /**
   * Tests get definitions.
   */
  public function testGetDefinitions(): void {
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

/**
 * {@inheritdoc}
 */
class TestAnnotation extends Plugin {

  /**
   * {@inheritdoc}
   */
  public function get(): ObjectDefinition {
    return new ObjectDefinition($this->definition);
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
