<?php

/**
 * @file
 * Contains Drupal\Tests\Core\ParamConverter\ParamConverterManagerTest.
 */

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the typed data resolver manager.
 *
 * @coversDefaultClass \Drupal\Core\ParamConverter\ParamConverterManager
 */
class ParamConverterManagerTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * @var \Drupal\Core\ParamConverter\ParamConverterManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Parameter converter manager',
      'description' => 'Tests the parameter converter manager.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container = $this->getMock('Drupal\Core\DependencyInjection\Container');
    $this->manager = new ParamConverterManager();
    $this->manager->setContainer($this->container);
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::addConverter().
   *
   * @dataProvider providerTestAddConverter
   *
   * @covers ::addConverter()
   * @covers ::getConverterIds()
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
   * @covers ::getConverter()
   */
  public function testGetConverter($name, $priority, $class) {
    $converter = $this->getMockBuilder('Drupal\Core\ParamConverter\ParamConverterInterface')
      ->setMockClassName($class)
      ->getMock();

    $this->manager->addConverter($name, $priority);
    $this->container->expects($this->once())
      ->method('get')
      ->with($name)
      ->will($this->returnValue($converter));

    $this->assertInstanceOf($class, $this->manager->getConverter($name));
    // Assert that a second call to getConverter() does not use the container.
    $this->assertInstanceOf($class, $this->manager->getConverter($name));
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   *
   * @covers ::getConverter()
   *
   * @expectedException \InvalidArgumentException
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
   * @covers ::setRouteParameterConverters()
   *
   * @dataProvider providerTestSetRouteParameterConverters
   */
  public function testSetRouteParameterConverters($path, $parameters = NULL, $expected = NULL) {
    $converter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('applies')
      ->with($this->anything(), 'id', $this->anything())
      ->will($this->returnValue(TRUE));
    $this->manager->addConverter('applied');
    $this->container->expects($this->any())
      ->method('get')
      ->with('applied')
      ->will($this->returnValue($converter));

    $route = new Route($path);
    if ($parameters) {
      $route->setOption('parameters', $parameters);
    }
    $collection = new RouteCollection();
    $collection->add('test_route', $route);

    $this->manager->setRouteParameterConverters($collection);
    foreach ($collection as $route) {
      $result = $route->getOption('parameters');
      if ($expected) {
        $this->assertSame($expected, $result['id']['converter']);
      }
      else {
        $this->assertNull($result);
      }
    }
  }

  /**
   * Provides data for testSetRouteParameterConverters().
   */
  public function providerTestSetRouteParameterConverters() {
    return array(
      array('/test'),
      array('/test/{id}', array('id' => array()), 'applied'),
      array('/test/{id}', array('id' => array('converter' => 'predefined')), 'predefined'),
    );
  }

  /**
   * @covers ::convert()
   */
  public function testConvert() {
    $route = new Route('/test/{id}/{literal}/{null}');
    $parameters = array(
      'id' => array(
        'converter' => 'test_convert',
      ),
      'literal' => array(),
      'null' => array(),
    );
    $route->setOption('parameters', $parameters);

    $defaults = array(
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
      'id' => 1,
      'literal' => 'this is a literal',
      'null' => NULL,
    );

    $expected = $defaults;
    $expected['id'] = 'something_better!';

    $converter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('convert')
      ->with(1, $this->isType('array'), 'id', $this->isType('array'), $this->isInstanceOf('Symfony\Component\HttpFoundation\Request'))
      ->will($this->returnValue('something_better!'));
    $this->manager->addConverter('test_convert');
    $this->container->expects($this->once())
      ->method('get')
      ->with('test_convert')
      ->will($this->returnValue($converter));

    $result = $this->manager->convert($defaults, new Request());

    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert()
   */
  public function testConvertNoConverting() {
    $route = new Route('/test');
    $defaults = array(
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
    );

    $expected = $defaults;

    $result = $this->manager->convert($defaults, new Request());
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert()
   *
   * @expectedException \Drupal\Core\ParamConverter\ParamNotConvertedException
   * @expectedExceptionMessage The "id" parameter was not converted for the path "/test/{id}" (route name: "test_route")
   */
  public function testConvertMissingParam() {
    $route = new Route('/test/{id}');
    $parameters = array(
      'id' => array(
        'converter' => 'test_convert',
      ),
    );
    $route->setOption('parameters', $parameters);

    $defaults = array(
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
      'id' => 1,
    );

    $converter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('convert')
      ->with(1, $this->isType('array'), 'id', $this->isType('array'), $this->isInstanceOf('Symfony\Component\HttpFoundation\Request'))
      ->will($this->returnValue(NULL));
    $this->manager->addConverter('test_convert');
    $this->container->expects($this->once())
      ->method('get')
      ->with('test_convert')
      ->will($this->returnValue($converter));

    $this->manager->convert($defaults, new Request());
  }

}
