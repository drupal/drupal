<?php

/**
 * @file
 * Contains Drupal\Tests\Core\ParamConverter\ParamConverterManagerTest.
 */

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\Core\ParamConverter\ParamConverterManager
 * @group ParamConverter
 */
class ParamConverterManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\ParamConverter\ParamConverterManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->manager = new ParamConverterManager();
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   *
   * @dataProvider providerTestGetConverter
   *
   * @covers ::getConverter
   */
  public function testGetConverter($name, $class) {
    $converter = $this->getMockBuilder('Drupal\Core\ParamConverter\ParamConverterInterface')
      ->setMockClassName($class)
      ->getMock();

    $this->manager->addConverter($converter, $name);

    $this->assertInstanceOf($class, $this->manager->getConverter($name));
    // Assert that a second call to getConverter() does not use the container.
    $this->assertInstanceOf($class, $this->manager->getConverter($name));
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   *
   * @covers ::getConverter
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
      array('name' => 'strawberry'),
      array('name' => 'raspberry'),
      array('name' => 'pear'),
      array('name' => 'peach'),
      array('name' => 'pineapple'),
      array('name' => 'banana'),
      array('name' => 'apple'),
    );

    $converters[0]['sorted'] = array(
      'strawberry', 'raspberry', 'pear', 'peach',
      'pineapple', 'banana', 'apple'
    );

    $converters[1]['unsorted'] = array(
      array('name' => 'giraffe'),
      array('name' => 'zebra'),
      array('name' => 'eagle'),
      array('name' => 'ape'),
      array('name' => 'cat'),
      array('name' => 'puppy'),
      array('name' => 'llama'),
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
      array('ape', 'ApeConverterClass'),
      array('cat', 'CatConverterClass'),
      array('puppy', 'PuppyConverterClass'),
      array('llama', 'LlamaConverterClass'),
      array('giraffe', 'GiraffeConverterClass'),
      array('zebra', 'ZebraConverterClass'),
      array('eagle', 'EagleConverterClass'),
    );
  }

  /**
   * @covers ::setRouteParameterConverters
   *
   * @dataProvider providerTestSetRouteParameterConverters
   */
  public function testSetRouteParameterConverters($path, $parameters = NULL, $expected = NULL) {
    $converter = $this->getMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('applies')
      ->with($this->anything(), 'id', $this->anything())
      ->will($this->returnValue(TRUE));
    $this->manager->addConverter($converter, 'applied');

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
   * @covers ::convert
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
      ->with(1, $this->isType('array'), 'id', $this->isType('array'))
      ->will($this->returnValue('something_better!'));
    $this->manager->addConverter($converter, 'test_convert');

    $result = $this->manager->convert($defaults);

    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertNoConverting() {
    $route = new Route('/test');
    $defaults = array(
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
    );

    $expected = $defaults;

    $result = $this->manager->convert($defaults);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert
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
      ->with(1, $this->isType('array'), 'id', $this->isType('array'))
      ->will($this->returnValue(NULL));
    $this->manager->addConverter($converter, 'test_convert');

    $this->manager->convert($defaults);
  }

}
