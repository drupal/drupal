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

    $discovery_main = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $discovery_main->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($discovery_main);
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

    $discovery_main = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $discovery_main->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($discovery_main);
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
    $discovery_main = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $discovery_main->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $discovery = new DerivativeDiscoveryDecorator($discovery_main);
    $discovery->getDefinitions();
  }
}
