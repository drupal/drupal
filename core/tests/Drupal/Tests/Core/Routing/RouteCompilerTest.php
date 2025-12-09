<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RouteCompiler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\Routing\RouteCompiler.
 */
#[CoversClass(RouteCompiler::class)]
#[Group('Routing')]
class RouteCompilerTest extends UnitTestCase {

  /**
   * Tests RouteCompiler::getFit().
   *
   * @param string $path
   *   A path whose fit will be calculated in the test.
   * @param int $expected
   *   The expected fit returned by RouteCompiler::getFit()
   */
  #[DataProvider('providerTestGetFit')]
  public function testGetFit($path, $expected): void {
    $route_compiler = new RouteCompiler();
    $result = $route_compiler->getFit($path);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for RouteCompilerTest::testGetFit()
   *
   * @return array
   *   An array of arrays, where each inner array has the path whose fit is to
   *   be calculated as the first value and the expected fit as the second
   *   value.
   */
  public static function providerTestGetFit(): array {
    return [
      ['test', 1],
      ['/estWithLeadingSlash', 1],
      ['testWithTrailingSlash/', 1],
      ['/testWithSlashes/', 1],
      ['test/with/multiple/parts', 15],
      ['test/with/{some}/slugs', 13],
      ['test/very/long/path/that/drupal/7/could/not/have/handled', 2047],
    ];
  }

  /**
   * Confirms that a route compiles properly with the necessary data.
   */
  public function testCompilation(): void {
    $route = new Route('/test/{something}/more');
    $route->setOption('compiler_class', RouteCompiler::class);
    $compiled = $route->compile();

    $this->assertEquals(5 /* That's 101 binary*/, $compiled->getFit(), 'The fit was incorrect.');
    $this->assertEquals('/test/%/more', $compiled->getPatternOutline(), 'The pattern outline was not correct.');
  }

  /**
   * Confirms that a compiled route with default values has the correct outline.
   */
  public function testCompilationDefaultValue(): void {
    // Because "here" has a default value, it should not factor into the outline
    // or the fitness.
    $route = new Route('/test/{something}/more/{here}', [
      'here' => 'there',
    ]);
    $route->setOption('compiler_class', RouteCompiler::class);
    $compiled = $route->compile();

    $this->assertEquals(5  /* That's 101 binary*/, $compiled->getFit(), 'The fit was not correct.');
    $this->assertEquals('/test/%/more', $compiled->getPatternOutline(), 'The pattern outline was not correct.');
  }

}
