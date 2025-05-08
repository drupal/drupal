<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Unpack;

use Composer\Semver\VersionParser;
use Drupal\Composer\Plugin\RecipeUnpack\SemVer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Composer\Plugin\RecipeUnpack\SemVer
 *
 * @group Unpack
 */
class SemVerTest extends TestCase {

  /**
   * @testWith ["^6.1", "^6.3", "^6.3"]
   *           ["*", "^6.3", "^6.3"]
   *           ["^6@dev", "^6.3", "^6.3"]
   *
   * @covers ::minimizeConstraints
   */
  public function testMinimizeConstraints(string $constraint_a, string $constraint_b, string $expected): void {
    $version_parser = new VersionParser();
    $this->assertSame($expected, SemVer::minimizeConstraints($version_parser, $constraint_a, $constraint_b));
    $this->assertSame($expected, SemVer::minimizeConstraints($version_parser, $constraint_b, $constraint_a));
  }

  /**
   * @testWith ["^6.1 || ^4.0", "^6.3 || ^7.4", ">=6.3.0.0-dev, <7.0.0.0-dev"]
   *
   * @covers ::minimizeConstraints
   */
  public function testMinimizeConstraintsWhichAreNotSubsets(string $constraint_a, string $constraint_b, string $expected): void {
    $this->assertSame($expected, SemVer::minimizeConstraints(new VersionParser(), $constraint_a, $constraint_b));
  }

  /**
   * @testWith ["^6.1", "^5.1", ">=6.3.0.0-dev, <7.0.0.0-dev"]
   *
   * @covers ::minimizeConstraints
   */
  public function testMinimizeConstraintsWhichDoNotIntersect(string $constraint_a, string $constraint_b, string $expected): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The constraints "^6.1" and "^5.1" do not intersect and cannot be minimized.');
    $this->assertSame($expected, SemVer::minimizeConstraints(new VersionParser(), $constraint_a, $constraint_b));
  }

}
