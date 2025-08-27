<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\Unit\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\toolbar\PageCache\AllowToolbarPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Drupal\toolbar\PageCache\AllowToolbarPath.
 */
#[CoversClass(AllowToolbarPath::class)]
#[Group('toolbar')]
class AllowToolbarPathTest extends UnitTestCase {

  /**
   * The toolbar path policy under test.
   *
   * @var \Drupal\toolbar\PageCache\AllowToolbarPath
   */
  protected $policy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->policy = new AllowToolbarPath();
  }

  /**
   * Asserts that caching is allowed if the request goes to toolbar subtree.
   *
   * @legacy-covers ::check
   */
  #[DataProvider('providerTestAllowToolbarPath')]
  public function testAllowToolbarPath($expected_result, $path): void {
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
  public static function providerTestAllowToolbarPath() {
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
