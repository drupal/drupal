<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\Discovery\DerivativeDiscoveryDecoratorTest.
 */

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Exception\InvalidDerivativeClassException;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the derivative discovery decorator.
 */
class DerivativeDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * The mock main discovery object.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $discoveryMain;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Derivative discovery decorator.',
      'description' => 'Tests the derivative discovery decorator.',
      'group' => 'Plugin',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->discoveryMain = $discovery_main = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
  }

  /**
   * Tests the getDerivativeFetcher method.
   *
   * @see  \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator::getDerivativeFetcher().
   */
  public function testGetDerivativeFetcher() {
    $definitions = array();
    $definitions['non_container_aware_discovery'] = array(
      'id' => 'non_container_aware_discovery',
      'derivative' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
    );

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $definitions = $discovery->getDefinitions();

    // Ensure that both test derivatives got added.
    $this->assertEquals(2, count($definitions));
    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_0']['id']);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery', $definitions['non_container_aware_discovery:test_discovery_0']['derivative']);

    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_1']['id']);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery', $definitions['non_container_aware_discovery:test_discovery_1']['derivative']);
  }

  /**
   * Tests the getDerivativeFetcher method with objects instead of arrays.
   */
  public function testGetDerivativeFetcherWithAnnotationObjects() {
    $definitions = array();
    $definitions['non_container_aware_discovery'] = (object) array(
      'id' => 'non_container_aware_discovery',
      'derivative' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject',
    );

    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $definitions = $discovery->getDefinitions();

    // Ensure that both test derivatives got added.
    $this->assertEquals(2, count($definitions));
    $this->assertInstanceOf('\stdClass', $definitions['non_container_aware_discovery:test_discovery_0']);
    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_0']->id);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject', $definitions['non_container_aware_discovery:test_discovery_0']->derivative);

    $this->assertInstanceOf('\stdClass', $definitions['non_container_aware_discovery:test_discovery_1']);
    $this->assertEquals('non_container_aware_discovery', $definitions['non_container_aware_discovery:test_discovery_1']->id);
    $this->assertEquals('\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscoveryWithObject', $definitions['non_container_aware_discovery:test_discovery_1']->derivative);
  }

  /**
   * Tests the getDerivativeFetcher method with an invalid class.
   *
   * @see \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator::getDerivativeFetcher().\
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidDerivativeClassException
   */
  public function testInvalidDerivativeFetcher() {
    $definitions = array();
    // Do this with a class that doesn't implement the interface.
    $definitions['invalid_discovery'] = array(
      'id' => 'invalid_discovery',
      'derivative' => '\Drupal\system\Tests\Plugin\DerivativeTest',
    );
    $this->discoveryMain->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($this->discoveryMain);
    $discovery->getDefinitions();
  }

  /**
   * Tests derivative definitions when a definition already exists.
   */
  public function testExistingDerivative() {
    $definitions = array();
    $definitions['non_container_aware_discovery'] = array(
      'id' => 'non_container_aware_discovery',
      'derivative' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
      'string' => 'string',
      'empty_string' => 'not_empty',
      'array' => array('one', 'two'),
      'empty_array' => array('three'),
      'null_value' => TRUE,
    );
    // This will clash with a derivative id.
    // @see \Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery
    $definitions['non_container_aware_discovery:test_discovery_1'] = array(
      'id' => 'non_container_aware_discovery:test_discovery_1',
      'string' => 'string',
      'empty_string' => '',
      'array' => array('one', 'two'),
      'empty_array' => array(),
      'null_value' => NULL,
    );

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
    $base_definition = array(
      'id' => 'non_container_aware_discovery',
      'derivative' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
      'string' => 'string',
      'empty_string' => 'not_empty',
      'array' => array('one', 'two'),
      'empty_array' => array('three'),
      'null_value' => TRUE,
    );
    // This will clash with a derivative id.
    // @see \Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery
    $derivative_definition = array(
      'id' => 'non_container_aware_discovery:test_discovery_1',
      'string' => 'string',
      'empty_string' => '',
      'array' => array('one', 'two'),
      'empty_array' => array(),
      'null_value' => NULL,
    );

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
