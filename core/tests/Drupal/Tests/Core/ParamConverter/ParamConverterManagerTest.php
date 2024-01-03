<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterManager;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\RouteObjectInterface;
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
  protected function setUp(): void {
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
   */
  public function testGetConverterException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->manager->getConverter('undefined.converter');
  }

  /**
   * Provide data for parameter converter manager tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   providerTestResolvers::testAddConverter().
   *
   * @see ParamConverterManagerTest::testAddConverter()
   */
  public function providerTestAddConverter() {
    $converters[0]['unsorted'] = [
      ['name' => 'strawberry'],
      ['name' => 'raspberry'],
      ['name' => 'pear'],
      ['name' => 'peach'],
      ['name' => 'pineapple'],
      ['name' => 'banana'],
      ['name' => 'apple'],
    ];

    $converters[0]['sorted'] = [
      'strawberry', 'raspberry', 'pear', 'peach',
      'pineapple', 'banana', 'apple',
    ];

    $converters[1]['unsorted'] = [
      ['name' => 'giraffe'],
      ['name' => 'zebra'],
      ['name' => 'eagle'],
      ['name' => 'ape'],
      ['name' => 'cat'],
      ['name' => 'puppy'],
      ['name' => 'llama'],
    ];

    $converters[1]['sorted'] = [
      'giraffe', 'zebra', 'eagle', 'ape',
      'cat', 'puppy', 'llama',
    ];

    return $converters;
  }

  /**
   * Provide data for parameter converter manager tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   providerTestResolvers::testGetConverter().
   *
   * @see ParamConverterManagerTest::testGetConverter()
   */
  public function providerTestGetConverter() {
    return [
      ['ape', 'ApeConverterClass'],
      ['cat', 'CatConverterClass'],
      ['puppy', 'PuppyConverterClass'],
      ['llama', 'LlamaConverterClass'],
      ['giraffe', 'GiraffeConverterClass'],
      ['zebra', 'ZebraConverterClass'],
      ['eagle', 'EagleConverterClass'],
    ];
  }

  /**
   * @covers ::setRouteParameterConverters
   *
   * @dataProvider providerTestSetRouteParameterConverters
   */
  public function testSetRouteParameterConverters($path, $parameters = NULL, $expected = NULL) {
    $converter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('applies')
      ->with($this->anything(), 'id', $this->anything())
      ->willReturn(TRUE);
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
    return [
      ['/test'],
      ['/test/{id}', ['id' => []], 'applied'],
      ['/test/{id}', ['id' => ['converter' => 'predefined']], 'predefined'],
    ];
  }

  /**
   * @covers ::convert
   */
  public function testConvert() {
    $route = new Route('/test/{id}/{literal}/{null}');
    $parameters = [
      'id' => [
        'converter' => 'test_convert',
      ],
      'literal' => [],
      'null' => [],
    ];
    $route->setOption('parameters', $parameters);

    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
      'id' => 1,
      'literal' => 'this is a literal',
      'null' => NULL,
    ];

    $expected = $defaults;
    $expected['id'] = 'something_better!';

    $converter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('convert')
      ->with(1, $this->isType('array'), 'id', $this->isType('array'))
      ->willReturn('something_better!');
    $this->manager->addConverter($converter, 'test_convert');

    $result = $this->manager->convert($defaults);

    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertNoConverting() {
    $route = new Route('/test');
    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
    ];

    $expected = $defaults;

    $result = $this->manager->convert($defaults);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertMissingParam() {
    $route = new Route('/test/{id}');
    $parameters = [
      'id' => [
        'converter' => 'test_convert',
      ],
    ];
    $route->setOption('parameters', $parameters);

    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
      'id' => 1,
    ];

    $converter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('convert')
      ->with(1, $this->isType('array'), 'id', $this->isType('array'))
      ->willReturn(NULL);
    $this->manager->addConverter($converter, 'test_convert');

    $this->expectException(ParamNotConvertedException::class);
    $this->expectExceptionMessage('The "id" parameter was not converted for the path "/test/{id}" (route name: "test_route")');
    $this->manager->convert($defaults);
  }

}
