<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Unpack;

use Composer\Semver\VersionParser;
use Drupal\Composer\Plugin\RecipeUnpack\SemVer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Composer\Plugin\RecipeUnpack\SemVer.
 */
#[CoversClass(SemVer::class)]
#[Group('Unpack')]
class SemVerTest extends TestCase {

  /**
   * Tests minimize constraints.
   */
  #[TestWith(["^6.1", "^6.3", "^6.3"])]
  #[TestWith(["*", "^6.3", "^6.3"])]
  #[TestWith(["^6@dev", "^6.3", "^6.3"])]
  public function testMinimizeConstraints(string $constraint_a, string $constraint_b, string $expected): void {
    $version_parser = new VersionParser();
    $this->assertSame($expected, SemVer::minimizeConstraints($version_parser, $constraint_a, $constraint_b));
    $this->assertSame($expected, SemVer::minimizeConstraints($version_parser, $constraint_b, $constraint_a));
  }

  /**
   * Tests minimize constraints which are not subsets.
   */
  #[TestWith(["^6.1 || ^4.0", "^6.3 || ^7.4", ">=6.3.0.0-dev, <7.0.0.0-dev"])]
  public function testMinimizeConstraintsWhichAreNotSubsets(string $constraint_a, string $constraint_b, string $expected): void {
    $this->assertSame($expected, SemVer::minimizeConstraints(new VersionParser(), $constraint_a, $constraint_b));
  }

  /**
   * Tests minimize constraints which do not intersect.
   */
  #[TestWith(["^6.1", "^5.1", ">=6.3.0.0-dev, <7.0.0.0-dev"])]
  public function testMinimizeConstraintsWhichDoNotIntersect(string $constraint_a, string $constraint_b, string $expected): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The constraints "^6.1" and "^5.1" do not intersect and cannot be minimized.');
    $this->assertSame($expected, SemVer::minimizeConstraints(new VersionParser(), $constraint_a, $constraint_b));
  }

}
