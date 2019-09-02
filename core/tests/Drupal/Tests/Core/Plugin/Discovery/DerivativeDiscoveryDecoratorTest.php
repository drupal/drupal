<?php

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Definition\DerivablePluginDefinitionInterface;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Exception\InvalidDeriverException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the derivative discovery decorator.
 *
 * @coversDefaultClass \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator
 *
 * @group Plugin
 */
class DerivativeDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * The mock main discovery object.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $discoveryMain;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->discoveryMain = $discovery_main = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
  }

  /**
   * Tests the getDerivativeFetcher method.
   *
   * @see \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator::getDerivativeFetcher()
   */
  public function testGetDerivativeFetcher() {
    $definitions = [];
    $definitions['non_container_aware_discovery'] = [
      'id' => 'non_container_aware_discovery',
      'deriver' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
    ];

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $definitions = $discovery->getDefinitions();

    // Ensure that both test derivatives got added.
    $this->assertEquals(2, count($definitions));
    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_0']['id']);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery', $definitions['non_container_aware_discovery:test_discovery_0']['deriver']);

    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_1']['id']);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery', $definitions['non_container_aware_discovery:test_discovery_1']['deriver']);
  }

  /**
   * Tests the getDerivativeFetcher method with objects instead of arrays.
   */
  public function testGetDerivativeFetcherWithAnnotationObjects() {
    $definitions = [];
    $definitions['non_container_aware_discovery'] = (object) [
      'id' => 'non_container_aware_discovery',
      'deriver' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject',
    ];

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $definitions = $discovery->getDefinitions();

    // Ensure that both test derivatives got added.
    $this->assertEquals(2, count($definitions));
    $this->assertInstanceOf('\stdClass', $definitions['non_container_aware_discovery:test_discovery_0']);
    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_0']->id);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject', $definitions['non_container_aware_discovery:test_discovery_0']->deriver);

    $this->assertInstanceOf('\stdClass', $definitions['non_container_aware_discovery:test_discovery_1']);
    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_1']->id);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject', $definitions['non_container_aware_discovery:test_discovery_1']->deriver);
  }

  /**
   * Tests getDeriverClass with classed objects instead of arrays.
   *
   * @covers ::getDeriverClass
   */
  public function testGetDeriverClassWithClassedDefinitions() {
    $definitions = [];
    $definition = $this->prophesize(DerivablePluginDefinitionInterface::class);
    $definition->id()->willReturn('non_container_aware_discovery');
    $definition->getDeriver()->willReturn(TestDerivativeDiscoveryWithObject::class);
    $definitions['non_container_aware_discovery'] = $definition->reveal();

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $definitions = $discovery->getDefinitions();

    // Ensure that both test derivatives got added.
    $this->assertContainsOnlyInstancesOf(DerivablePluginDefinitionInterface::class, $definitions);
    $this->assertEquals(['non_container_aware_discovery:test_discovery_0', 'non_container_aware_discovery:test_discovery_1'], array_keys($definitions));
  }

  /**
   * @covers ::getDeriverClass
   */
  public function testGetDeriverClassWithInvalidClassedDefinitions() {
    $definition = $this->prophesize(DerivablePluginDefinitionInterface::class);
    $definition->id()->willReturn('non_existent_discovery');
    $definition->getDeriver()->willReturn('\Drupal\system\Tests\Plugin\NonExistentDeriver');

    $definitions['non_existent_discovery'] = $definition->reveal();

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);

    $this->expectException(InvalidDeriverException::class);
    $this->expectExceptionMessage('Plugin (non_existent_discovery) deriver "\Drupal\system\Tests\Plugin\NonExistentDeriver" does not exist.');
    $discovery->getDefinitions();
  }

  /**
   * Tests the getDerivativeFetcher method with a non-existent class.
   *
   * @see \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator::getDeriver().\
   */
  public function testNonExistentDerivativeFetcher() {
    $definitions = [];
    // Do this with a class that doesn't exist.
    $definitions['non_existent_discovery'] = [
      'id' => 'non_existent_discovery',
      'deriver' => '\Drupal\system\Tests\Plugin\NonExistentDeriver',
    ];
    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $this->expectException(InvalidDeriverException::class);
    $this->expectExceptionMessage('Plugin (non_existent_discovery) deriver "\Drupal\system\Tests\Plugin\NonExistentDeriver" does not exist.');
    $discovery->getDefinitions();
  }

  /**
   * Tests the getDerivativeFetcher method with an invalid class.
   *
   * @see \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator::getDeriver().\
   */
  public function testInvalidDerivativeFetcher() {
    $definitions = [];
    // Do this with a class that doesn't implement the interface.
    $definitions['invalid_discovery'] = [
      'id' => 'invalid_discovery',
      'deriver' => '\Drupal\KernelTests\Core\Plugin\DerivativeTest',
    ];
    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $this->expectException(InvalidDeriverException::class);
    $this->expectExceptionMessage('Plugin (invalid_discovery) deriver "\Drupal\KernelTests\Core\Plugin\DerivativeTest" must implement \Drupal\Component\Plugin\Derivative\DeriverInterface.');
    $discovery->getDefinitions();
  }

  /**
   * Tests derivative definitions when a definition already exists.
   */
  public function testExistingDerivative() {
    $definitions = [];
    $definitions['non_container_aware_discovery'] = [
      'id' => 'non_container_aware_discovery',
      'deriver' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
      'string' => 'string',
      'empty_string' => 'not_empty',
      'array' => ['one', 'two'],
      'empty_array' => ['three'],
      'null_value' => TRUE,
    ];
    // This will clash with a derivative id.
    // @see \Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery
    $definitions['non_container_aware_discovery:test_discovery_1'] = [
      'id' => 'non_container_aware_discovery:test_discovery_1',
      'string' => 'string',
      'empty_string' => '',
      'array' => ['one', 'two'],
      'empty_array' => [],
      'null_value' => NULL,
    ];

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $returned_definitions = $discovery->getDefinitions();

    // If the definition was merged, there should only be two.
    $this->assertCount(2, $returned_definitions);

    $expected = $definitions['non_container_aware_discovery'];
    $expected['id'] = 'non_container_aware_discovery:test_discovery_1';
    $this->assertArrayEquals($expected, $returned_definitions['non_container_aware_discovery:test_discovery_1']);
  }

  /**
   * Tests a single definition when a derivative already exists.
   */
  public function testSingleExistingDerivative() {
    $base_definition = [
      'id' => 'non_container_aware_discovery',
      'deriver' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
      'string' => 'string',
      'empty_string' => 'not_empty',
      'array' => ['one', 'two'],
      'empty_array' => ['three'],
      'null_value' => TRUE,
    ];
    // This will clash with a derivative id.
    // @see \Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery
    $derivative_definition = [
      'id' => 'non_container_aware_discovery:test_discovery_1',
      'string' => 'string',
      'empty_string' => '',
      'array' => ['one', 'two'],
      'empty_array' => [],
      'null_value' => NULL,
    ];

    $this->discoveryMain->expects($this->at(0))
      ->method('getDefinition')
      ->with('non_container_aware_discovery:test_discovery_1')
      ->will($this->returnValue($derivative_definition));
    $this->discoveryMain->expects($this->at(1))
      ->method('getDefinition')
      ->with('non_container_aware_discovery')
      ->will($this->returnValue($base_definition));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);

    $expected = $base_definition;
    $expected['id'] = 'non_container_aware_discovery:test_discovery_1';
    $this->assertArrayEquals($expected, $discovery->getDefinition('non_container_aware_discovery:test_discovery_1'));
  }

}
