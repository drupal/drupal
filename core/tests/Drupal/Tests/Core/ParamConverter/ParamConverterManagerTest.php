<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterManager;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests Drupal\Core\ParamConverter\ParamConverterManager.
 */
#[CoversClass(ParamConverterManager::class)]
#[Group('ParamConverter')]
class ParamConverterManagerTest extends UnitTestCase {

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   */
  #[DataProvider('providerTestGetConverter')]
  public function testGetConverter(string $name, string $class): void {
    $converter = $this->getMockBuilder('Drupal\Core\ParamConverter\ParamConverterInterface')
      ->setMockClassName($class)
      ->getMock();

    $manager = new ParamConverterManager(new ServiceLocator([$name => fn() => $converter]));
    $this->assertInstanceOf($class, $manager->getConverter($name));
  }

  /**
   * Tests \Drupal\Core\ParamConverter\ParamConverterManager::getConverter().
   */
  public function testGetConverterException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $manager = new ParamConverterManager(new ServiceLocator([]));
    $manager->getConverter('undefined.converter');
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
  public static function providerTestGetConverter(): array {
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
   * Tests set route parameter converters.
   */
  #[DataProvider('providerTestSetRouteParameterConverters')]
  public function testSetRouteParameterConverters(string $path, ?array $parameters = NULL, ?string $expected = NULL): void {
    $converter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->any())
      ->method('applies')
      ->with($this->anything(), 'id', $this->anything())
      ->willReturn(TRUE);
    $manager = new ParamConverterManager(new ServiceLocator(['applied' => fn() => $converter]));

    $route = new Route($path);
    if ($parameters) {
      $route->setOption('parameters', $parameters);
    }
    $collection = new RouteCollection();
    $collection->add('test_route', $route);

    $manager->setRouteParameterConverters($collection);
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
  public static function providerTestSetRouteParameterConverters(): array {
    return [
      ['/test'],
      ['/test/{id}', ['id' => []], 'applied'],
      ['/test/{id}', ['id' => ['converter' => 'predefined']], 'predefined'],
    ];
  }

  /**
   * Tests convert.
   */
  public function testConvert(): void {
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
      ->with(1, $this->isArray(), 'id', $this->isArray())
      ->willReturn('something_better!');

    $manager = new ParamConverterManager(new ServiceLocator(['test_convert' => fn() => $converter]));
    $result = $manager->convert($defaults);

    $this->assertEquals($expected, $result);
  }

  /**
   * Tests that converters are lazily instantiated.
   */
  public function testLazyInstantiation(): void {
    $route = new Route('/test/{id}');

    $converter = $this->createMock('Drupal\Core\ParamConverter\ParamConverterInterface');
    $converter->expects($this->exactly(2))
      ->method('convert')
      ->with(1, $this->isArray(), 'id', $this->isArray())
      ->willReturn('converted_value');

    $instantiated = FALSE;
    $manager = new ParamConverterManager(new ServiceLocator([
      'converter1' => fn() => $converter,
      'converter2' => function () use (&$instantiated, $converter) {
        $instantiated = TRUE;
        return $converter;
      },
    ]));

    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
      'id' => 1,
    ];

    $route->setOption('parameters', [
      'id' => [
        'converter' => 'converter1',
      ],
    ]);
    $result = $manager->convert($defaults);
    $this->assertEquals('converted_value', $result['id']);
    $this->assertFalse($instantiated);

    $route->setOption('parameters', [
      'id' => [
        'converter' => 'converter2',
      ],
    ]);
    $manager->convert($defaults);
    $this->assertTrue($instantiated);
  }

  /**
   * Tests convert no converting.
   */
  public function testConvertNoConverting(): void {
    $route = new Route('/test');
    $defaults = [
      RouteObjectInterface::ROUTE_OBJECT => $route,
      RouteObjectInterface::ROUTE_NAME => 'test_route',
    ];

    $expected = $defaults;

    $manager = new ParamConverterManager(new ServiceLocator([]));
    $result = $manager->convert($defaults);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests convert missing param.
   */
  public function testConvertMissingParam(): void {
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
      ->with(1, $this->isArray(), 'id', $this->isArray())
      ->willReturn(NULL);

    $manager = new ParamConverterManager(new ServiceLocator(['test_convert' => fn() => $converter]));

    $this->expectException(ParamNotConvertedException::class);
    $this->expectExceptionMessage('The "id" parameter was not converted for the path "/test/{id}" (route name: "test_route")');
    $manager->convert($defaults);
  }

}
