<?php

/**
 * @file
 * Contains Drupal\Tests\Core\ParamConverter\ParamConverterManagerTest.
 */

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the typed data resolver manager.
 */
class ParamConverterManagerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Parameter converter manager',
      'description' => 'Tests the parameter converter manager.',
      'group' => 'Routing',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->manager = new ParamConverterManager();
    $this->manager->setContainer($this->container);
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::addConverter().
   *
   * @dataProvider providerTestAddConverter
   *
   * @see ParamConverterManagerTest::providerTestAddConverter().
   */
  public function testAddConverter($unsorted, $sorted) {
    foreach ($unsorted as $data) {
      $this->manager->addConverter($data['name'], $data['priority']);
    }

    // Test that ResolverManager::getTypedDataResolvers() returns the resolvers
    // in the expected order.
    foreach ($this->manager->getConverterIds() as $key => $converter) {
      $this->assertEquals($sorted[$key], $converter);
    }
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   *
   * @dataProvider providerTestGetConverter
   *
   * @see ParamConverterManagerTest::providerTestGetConverter().
   */
  public function testGetConverter($name, $priority, $class) {
    $converter = $this->getMockBuilder('Drupal\Core\ParamConverter\ParamConverterInterface')
      ->setMockClassName($class)
      ->getMock();

    $this->manager->addConverter($name, $priority);
    $this->container->set($name, $converter);

    $this->assertInstanceOf($class, $this->manager->getConverter($name));
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   *
   * @expectedException InvalidArgumentException
   */
  public function testGetConverterException() {
    $this->manager->getConverter('undefined.converter');
  }

  /**
   * Provide data for parameter converter manager tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   providerTestResolvers::testAddConverter().
   *
   * @see ParamConverterManagerTest::testAddConverter().
   */
  public function providerTestAddConverter() {
    $converters[0]['unsorted'] = array(
      array('name' => 'raspberry', 'priority' => 10),
      array('name' => 'pear', 'priority' => 5),
      array('name' => 'strawberry', 'priority' => 20),
      array('name' => 'pineapple', 'priority' => 0),
      array('name' => 'banana', 'priority' => -10),
      array('name' => 'apple', 'priority' => -10),
      array('name' => 'peach', 'priority' => 5),
    );

    $converters[0]['sorted'] = array(
      'strawberry', 'raspberry', 'pear', 'peach',
      'pineapple', 'banana', 'apple'
    );

    $converters[1]['unsorted'] = array(
      array('name' => 'ape', 'priority' => 0),
      array('name' => 'cat', 'priority' => -5),
      array('name' => 'puppy', 'priority' => -10),
      array('name' => 'llama', 'priority' => -15),
      array('name' => 'giraffe', 'priority' => 10),
      array('name' => 'zebra', 'priority' => 10),
      array('name' => 'eagle', 'priority' => 5),
    );

    $converters[1]['sorted'] = array(
      'giraffe', 'zebra', 'eagle', 'ape',
      'cat', 'puppy', 'llama'
    );

    return $converters;
  }

  /**
   * Provide data for parameter converter manager tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   providerTestResolvers::testGetConverter().
   *
   * @see ParamConverterManagerTest::testGetConverter().
   */
  public function providerTestGetConverter() {
    return array(
      array('ape', 0, 'ApeConverterClass'),
      array('cat', -5, 'CatConverterClass'),
      array('puppy', -10, 'PuppyConverterClass'),
      array('llama', -15, 'LlamaConverterClass'),
      array('giraffe', 10, 'GiraffeConverterClass'),
      array('zebra', 10, 'ZebraConverterClass'),
      array('eagle', 5, 'EagleConverterClass'),
    );
  }

  /**
   * Tests the enhance method.
   *
   * @see \Drupal\Core\ParamConverter\ParamConverterManager::enhance().
   */
  public function testEnhance() {
    // Create a mock route using a mock parameter converter.
    $converter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $this->manager->addConverter('test_convert');

    $this->container->set('test_convert', $converter);

    $route = new Route('/test/{id}');
    $parameters = array();
    $parameters['id'] = array(
      'converter' => 'test_convert'
    );
    $route->setOption('parameters', $parameters);

    $defaults = array();
    $defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;
    $defaults['id'] = 1;
    $defaults['_entity'] = &$defaults['id'];

    $request = new Request();

    $entity = $this->getMockBuilder('\Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $converter->expects($this->once())
      ->method('convert')
      ->with($this->equalTo(1))
      ->will($this->returnValue($entity));

    $defaults = $this->manager->enhance($defaults, $request);

    // The value of 1 should be upcast to the User object for UID 1.
    $this->assertSame($entity, $defaults['id']);
    // The parameter for the user ID should be stored in the raw variables.
    $this->assertTrue($defaults['_raw_variables']->has('id'));
    // The raw non-upcasted value for the user should be the UID.
    $this->assertEquals(1, $defaults['_raw_variables']->get('id'));
  }

}
