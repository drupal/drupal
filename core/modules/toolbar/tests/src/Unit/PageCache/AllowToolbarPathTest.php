<?php

namespace Drupal\Tests\toolbar\Unit\PageCache;

use Drupal\toolbar\PageCache\AllowToolbarPath;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\toolbar\PageCache\AllowToolbarPath
 * @group toolbar
 */
class AllowToolbarPathTest extends UnitTestCase {

  /**
   * The toolbar path policy under test.
   *
   * @var \Drupal\toolbar\PageCache\AllowToolbarPath
   */
  protected $policy;

  protected function setUp(): void {
    $this->policy = new AllowToolbarPath();
  }

  /**
   * Asserts that caching is allowed if the request goes to toolbar subtree.
   *
   * @dataProvider providerTestAllowToolbarPath
   * @covers ::check
   */
  public function testAllowToolbarPath($expected_result, $path) {
    $request = Request::create($path);
    $result = $this->policy->check($request);
    $this->assertSame($expected_result, $result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerTestAllowToolbarPath() {
    return [
      [NULL, '/'],
      [NULL, '/other-path?q=/toolbar/subtrees/'],
      [NULL, '/toolbar/subtrees/'],
      [NULL, '/toolbar/subtrees/some-hash/langcode/additional-stuff'],
      [RequestPolicyInterface::ALLOW, '/de/toolbar/subtrees/abcd'],
      [RequestPolicyInterface::ALLOW, '/en-us/toolbar/subtrees/xyz'],
      [RequestPolicyInterface::ALLOW, '/en-us/toolbar/subtrees/xyz/de'],
      [RequestPolicyInterface::ALLOW, '/a/b/c/toolbar/subtrees/xyz/de'],
      [RequestPolicyInterface::ALLOW, '/toolbar/subtrees/some-hash'],
      [RequestPolicyInterface::ALLOW, '/toolbar/subtrees/some-hash/en'],
    ];
  }

}
