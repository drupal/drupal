<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Version;

use Drupal\Component\Version\Constraint;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Version\Constraint
 * @group Version
 */
class ConstraintTest extends TestCase {

  /**
   * @covers ::isCompatible
   * @dataProvider providerIsCompatible
   */
  public function testIsCompatible(Constraint $version_info, $current_version, $result) {
    $this->assertSame($result, $version_info->isCompatible($current_version));
  }

  /**
   * Provider for testIsCompatible.
   */
  public function providerIsCompatible() {
    $tests = [];

    $tests['no-dependencies'] = [new Constraint('', '8.x'), '8.1.x', TRUE];

    // Both no space and multiple spaces are supported between commas and
    // version strings and between operators and version strings.
    $whitespace_variation_constraints = [
      // No whitespace.
      '>=1.0,<=2.0',
      // Whitespace after comma.
      '>=1.0,   <=2.0',
      // Whitespace after operators.
      '>=   1.0,<=   2.0',
      // Whitespace after operators and commas.
      '>=   1.0,  <=   2.0',
    ];
    foreach ($whitespace_variation_constraints as $whitespace_variation_constraint) {
      $tests += $this->createTestsForVersions($whitespace_variation_constraint, ['1.0', '2.0', '1.5'], TRUE, '8.x');
      $tests += $this->createTestsForVersions($whitespace_variation_constraint, ['0.9', '2.1'], FALSE, '8.x');
    }

    // We support both '=' and '==' for the equal operator. If no operator is
    // given then the equal operator is assumed.
    foreach (['', '=', '=='] as $equal_operator) {
      // Both '!=' and '<>' are supported for not equal operator.
      foreach (['!=', '<>'] as $not_equal_operator) {
        // Test that core compatibility works for different core versions.
        foreach (['8.x', '9.x', '10.x'] as $core_compatibility) {
          // Test greater than and less than with an incorrect core
          // compatibility. For example '<8.x-4.x>8.x-1.x' using core
          // compatibility of '9.x'.
          if ($core_compatibility === '8.x') {
            $constraint = "< 9.x-4.x, > 9.x-1.x";
          }
          else {
            $constraint = "< 8.x-4.x, > 8.x-1.x";
          }
          $tests += $this->createTestsForVersions($constraint, ['4.0', '3.9', '2.1', '1.9'], FALSE, $core_compatibility);

          // Stable version. For example "=8.x-1.0".
          $constraint = "{$equal_operator} $core_compatibility-1.0";
          $tests += $this->createTestsForVersions($constraint, ['1.0'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.1', '0.9'], FALSE, $core_compatibility);

          // Alpha version. For example "=8.x-1.1-alpha12".
          $constraint = "{$equal_operator} $core_compatibility-1.1-alpha12";
          $tests += $this->createTestsForVersions($constraint, ['1.1-alpha12'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.1-alpha10', '1.1-beta1'], FALSE, $core_compatibility);

          // Beta version. For example "=8.x-1.1-beta8".
          $constraint = "{$equal_operator} $core_compatibility-1.1-beta8";
          $tests += $this->createTestsForVersions($constraint, ['1.1-beta8'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.1-beta4'], FALSE, $core_compatibility);

          // RC version. For example "=8.x-1.1-rc11".
          $constraint = "{$equal_operator} $core_compatibility-1.1-rc11";
          $tests += $this->createTestsForVersions($constraint, ['1.1-rc11'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.1-rc2'], FALSE, $core_compatibility);

          // Test greater than. For example ">8.x-1.x".
          $constraint = "> $core_compatibility-1.x";
          $tests += $this->createTestsForVersions($constraint, ['2.0'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.1', '0.9'], FALSE, $core_compatibility);

          // Test greater than or equal ">=8.x-1.0".
          $tests += $this->createTestsForVersions(">= $core_compatibility-1.0", ['1.1', '1.0'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions(">= $core_compatibility-1.1", ['1.0'], FALSE, $core_compatibility);

          // Test less than. For examples"<8.x-1.1".
          $constraint = "< $core_compatibility-1.1";
          $tests += $this->createTestsForVersions($constraint, ['1.1'], FALSE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.0'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions("< $core_compatibility-1.0", ['1.1'], FALSE, $core_compatibility);

          // Test less than or equal. For example "<=8.x-1.x".
          $constraint = "<= $core_compatibility-1.x";
          $tests += $this->createTestsForVersions($constraint, ['2.0'], FALSE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['1.9', '1.1', '0.9'], TRUE, $core_compatibility);

          // Test greater than and less than. For example "<8.x-4.x,>8.x-1.x".
          $constraint = "< $core_compatibility-4.x, > $core_compatibility-1.x";
          $tests += $this->createTestsForVersions($constraint, ['4.0', '1.9'], FALSE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['3.9', '2.1'], TRUE, $core_compatibility);

          // Test greater than and less than with no core version in
          // constraint. For example "<4.x,>1.x".
          $constraint = "< 4.x, > 1.x";
          $tests += $this->createTestsForVersions($constraint, ['4.0', '1.9'], FALSE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['3.9', '2.1'], TRUE, $core_compatibility);

          // Test greater than or equals and equals minor version. Both of
          // these conditions will pass. For example "8.x-2.x,>=2.4-alpha2".
          $constraint = "{$equal_operator} $core_compatibility-2.x, >= 2.4-alpha2";
          $tests += $this->createTestsForVersions($constraint, ['2.4-beta3'], TRUE, $core_compatibility);

          // Test greater than or equals and equals exact version. For example
          // "8.x-2.0,>=2.4-alpha2".
          $constraint = "{$equal_operator} $core_compatibility-2.0, >= 2.4-alpha2";
          $tests += $this->createTestsForVersions($constraint, ['2.4-beta3'], FALSE, $core_compatibility);

          // Test unsatisfiable greater than and less than. For example
          // "> 8.x-4.x,<8.x-1.x".
          $constraint = "> $core_compatibility-4.x, < $core_compatibility-1.x";
          $tests += $this->createTestsForVersions($constraint, ['4.0', '3.9', '2.1', '1.9'], FALSE, $core_compatibility);

          // Test 2 equals with 1 that is compatible and 1 that is not. For
          // example "=2.x,=2.4-beta".
          $constraint = "{$equal_operator} {$core_compatibility}2.x, {$equal_operator} 2.4-beta3";
          $tests += $this->createTestsForVersions($constraint, ['2.4-beta3'], FALSE, $core_compatibility);

          // Test unsatisfiable multiple equals. For example
          // "8.x-2.1,8.x-2.3,\"(>1.0,<=3.2,!=3.0)-8.x.2.5".
          $constraint = "{$equal_operator} $core_compatibility-2.1, {$equal_operator} $core_compatibility-2.3,\"(> 1.0, <= 3.2, {$not_equal_operator} 3.0)-8.x.2.5";
          $tests += $this->createTestsForVersions($constraint, ['2.1', '2.2', '2.3'], FALSE, $core_compatibility);

          // Test with a range and multiple exclusions. For example
          // ">1.0,<=3.2,!=3.0,!=1.5,!=2.7".
          $constraint = "> 1.0, <= 3.2, $not_equal_operator 3.0, $not_equal_operator 1.5, $not_equal_operator 2.7";
          $tests += $this->createTestsForVersions($constraint, ['1.1', '3.1', '2.1'], TRUE, $core_compatibility);
          $tests += $this->createTestsForVersions($constraint, ['3.0', '1.5', '2.7', '3.3'], FALSE, $core_compatibility);
        }
      }
    }
    return $tests;
  }

  /**
   * Create testIsCompatible() test cases for constraints and versions.
   *
   * @param string $constraint_string
   *   The constraint string to be used in \Drupal\Component\Version\Constraint
   *   for example ">8.x-2.4".
   * @param string[] $versions
   *   Version strings be passed to
   *   \Drupal\Component\Version\Constraint::isCompatible(), for example
   *   "8.x-2.4". One test case will be returned for every version.
   * @param bool $expected_result
   *   The expect result for all versions.
   * @param string $core_compatibility
   *   (optional) The core compatibility to be used in
   *   \Drupal\Component\Version\Constraint, for example "7.x". Defaults to
   *   "8.x".
   *
   * @return array[]
   *   The test cases to be used with ::testIsCompatible().
   */
  private function createTestsForVersions($constraint_string, array $versions, $expected_result, $core_compatibility = '8.x') {
    $constraint = new Constraint($constraint_string, $core_compatibility);
    $tests = [];
    foreach ($versions as $version) {
      $tests["$core_compatibility::($constraint_string)::$version"] = [$constraint, $version, $expected_result];
    }
    return $tests;
  }

}
