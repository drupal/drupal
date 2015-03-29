<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Path\PathMatcherTest
 */

namespace Drupal\Tests\Core\Path;

use Drupal\Component\Utility\SafeMarkup;
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
  protected function setUp() {
    // Create a stub config factory with all config settings that will be
    // checked during this test.
    $config_factory_stub = $this->getConfigFactoryStub(
      array(
        'system.site' => array(
          'page.front' => 'dummy',
        ),
      )
    );
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->pathMatcher = new PathMatcher($config_factory_stub, $route_match);
  }

  /**
   * Test that standard paths works with multiple patterns.
   *
   * @dataProvider getMatchPathData
   */
  public function testMatchPath($patterns, $paths) {
    foreach ($paths as $path => $expected_result) {
      $actual_result = $this->pathMatcher->matchPath($path, $patterns);
      $this->assertEquals($actual_result, $expected_result, SafeMarkup::format('Tried matching the path <code>@path</code> to the pattern <pre>@patterns</pre> - expected @expected, got @actual.', array(
        '@path' => $path,
        '@patterns' => $patterns,
        '@expected' => var_export($expected_result, TRUE),
        '@actual' => var_export($actual_result, TRUE),
      )));
    }
  }

  /**
   * Provides test path data.
   *
   * @return array
   *   A nested array of pattern arrays and path arrays.
   */
  public function getMatchPathData() {
    return array(
      array(
        // Single absolute paths.
        'example/1',
        array(
          'example/1' => TRUE,
          'example/2' => FALSE,
          'test' => FALSE,
        ),
      ),
      array(
        // Single paths with wildcards.
        'example/*',
        array(
          'example/1' => TRUE,
          'example/2' => TRUE,
          'example/3/edit' => TRUE,
          'example/' => TRUE,
          'example' => FALSE,
          'test' => FALSE,
        ),
      ),
      array(
        // Single paths with multiple wildcards.
        'node/*/revisions/*',
        array(
          'node/1/revisions/3' => TRUE,
          'node/345/revisions/test' => TRUE,
          'node/23/edit' => FALSE,
          'test' => FALSE,
        ),
      ),
      array(
        // Single paths with '<front>'.
        "<front>",
        array(
          'dummy' => TRUE,
          "dummy/" => FALSE,
          "dummy/edit" => FALSE,
          'node' => FALSE,
          '' => FALSE,
        ),
      ),
      array(
        // Paths with both '<front>' and wildcards (should not work).
        "<front>/*",
        array(
          'dummy' => FALSE,
          'dummy/' => FALSE,
          'dummy/edit' => FALSE,
          'node/12' => FALSE,
          '' => FALSE,
        ),
      ),
      array(
        // Multiple paths with the \n delimiter.
        "node/*\nnode/*/edit",
        array(
          'node/1' => TRUE,
          'node/view' => TRUE,
          'node/32/edit' => TRUE,
          'node/delete/edit' => TRUE,
          'node/50/delete' => TRUE,
          'test/example' => FALSE,
        ),
      ),
      array(
        // Multiple paths with the \r delimiter.
        "user/*\rexample/*",
        array(
          'user/1' => TRUE,
          'example/1' => TRUE,
          'user/1/example/1' => TRUE,
          'user/example' => TRUE,
          'test/example' => FALSE,
          'user' => FALSE,
          'example' => FALSE,
        ),
      ),
      array(
        // Multiple paths with the \r\n delimiter.
        "test\r\n<front>",
        array(
          'test' => TRUE,
          'dummy' => TRUE,
          'example' => FALSE,
        ),
      ),
      array(
        // Test existing regular expressions (should be escaped).
        '[^/]+?/[0-9]',
        array(
          'test/1' => FALSE,
          '[^/]+?/[0-9]' => TRUE,
        ),
      ),
    );
  }
}
