<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Path;

use Drupal\Core\Path\PathMatcher;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Path\PathMatcher
 * @group Path
 */
class PathMatcherTest extends UnitTestCase {

  /**
   * The path matcher under test.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a stub config factory with all config settings that will be
    // checked during this test.
    $config_factory_stub = $this->getConfigFactoryStub(
      [
        'system.site' => [
          'page.front' => '/dummy',
        ],
      ]
    );
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->pathMatcher = new PathMatcher($config_factory_stub, $route_match);
  }

  /**
   * Tests that standard paths works with multiple patterns.
   *
   * @dataProvider getMatchPathData
   */
  public function testMatchPath($patterns, $paths) {
    foreach ($paths as $path => $expected_result) {
      $actual_result = $this->pathMatcher->matchPath($path, $patterns);
      $this->assertEquals($actual_result, $expected_result, "Tried matching the path '$path' to the pattern '$patterns'.");
    }
  }

  /**
   * Provides test path data.
   *
   * @return array
   *   A nested array of pattern arrays and path arrays.
   */
  public function getMatchPathData() {
    return [
      [
        // Single absolute paths.
        '/example/1',
        [
          '/example/1' => TRUE,
          '/example/2' => FALSE,
          '/test' => FALSE,
        ],
      ],
      [
        // Single paths with wildcards.
        '/example/*',
        [
          '/example/1' => TRUE,
          '/example/2' => TRUE,
          '/example/3/edit' => TRUE,
          '/example/' => TRUE,
          '/example' => FALSE,
          '/test' => FALSE,
        ],
      ],
      [
        // Single paths with multiple wildcards.
        '/node/*/revisions/*',
        [
          '/node/1/revisions/3' => TRUE,
          '/node/345/revisions/test' => TRUE,
          '/node/23/edit' => FALSE,
          '/test' => FALSE,
        ],
      ],
      [
        // Single paths with '<front>'.
        "<front>",
        [
          '/dummy' => TRUE,
          "/dummy/" => FALSE,
          "/dummy/edit" => FALSE,
          '/node' => FALSE,
          '' => FALSE,
        ],
      ],
      [
        // Paths with both '<front>' and wildcards (should not work).
        "<front>/*",
        [
          '/dummy' => FALSE,
          '/dummy/' => FALSE,
          '/dummy/edit' => FALSE,
          '/node/12' => FALSE,
          '/' => FALSE,
        ],
      ],
      [
        // Multiple paths with the \n delimiter.
        "/node/*\n/node/*/edit",
        [
          '/node/1' => TRUE,
          '/node/view' => TRUE,
          '/node/32/edit' => TRUE,
          '/node/delete/edit' => TRUE,
          '/node/50/delete' => TRUE,
          '/test/example' => FALSE,
        ],
      ],
      [
        // Multiple paths with the \r delimiter.
        "/user/*\r/example/*",
        [
          '/user/1' => TRUE,
          '/example/1' => TRUE,
          '/user/1/example/1' => TRUE,
          '/user/example' => TRUE,
          '/test/example' => FALSE,
          '/user' => FALSE,
          '/example' => FALSE,
        ],
      ],
      [
        // Multiple paths with the \r\n delimiter.
        "/test\r\n<front>",
        [
          '/test' => TRUE,
          '/dummy' => TRUE,
          '/example' => FALSE,
        ],
      ],
      [
        // Test existing regular expressions (should be escaped).
        '[^/]+?/[0-9]',
        [
          '/test/1' => FALSE,
          '[^/]+?/[0-9]' => TRUE,
        ],
      ],
    ];
  }

}
