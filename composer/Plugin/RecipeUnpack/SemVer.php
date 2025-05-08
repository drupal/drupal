<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Semver\Constraint\MatchNoneConstraint;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Intervals;
use Composer\Semver\VersionParser;

/**
 * Helper class to manipulate semantic versioning constraints.
 *
 * @internal
 */
final class SemVer {

  private function __construct() {}

  /**
   * Minimizes two constraints.
   *
   * Compares two constraints and determines if one is a subset of the other. If
   * this is the case, the constraint that is a subset is returned. For example,
   * if called with '^6.2' and '^6.3' the function will return '^6.3'. If
   * neither constraint is a subset then the constraints are compacted and the
   * intersection is returned. For example, if called with ">=10.3" and
   * "^10.4 || ^11" the function will return ">=10.4.0.0-dev, <12.0.0.0-dev".
   *
   * @param \Composer\Semver\VersionParser $version_parser
   *   A version parser.
   * @param string $constraint_a
   *   A constraint to compact.
   * @param string $constraint_b
   *   A constraint to compact.
   *
   * @return string
   *   The compacted constraint.
   *
   * @throws \LogicException
   *   Thrown when the provided constraints have no intersection.
   */
  public static function minimizeConstraints(VersionParser $version_parser, string $constraint_a, string $constraint_b): string {
    $constraint_object_a = $version_parser->parseConstraints($constraint_a);
    $constraint_object_b = $version_parser->parseConstraints($constraint_b);
    if (Intervals::isSubsetOf($constraint_object_a, $constraint_object_b)) {
      return $constraint_a;
    }
    if (Intervals::isSubsetOf($constraint_object_b, $constraint_object_a)) {
      return $constraint_b;
    }
    $constraint = Intervals::compactConstraint(new MultiConstraint([$constraint_object_a, $constraint_object_b]));
    if ($constraint instanceof MatchNoneConstraint) {
      throw new \LogicException(sprintf('The constraints "%s" and "%s" do not intersect and cannot be minimized.', $constraint_a, $constraint_b));
    }
    return sprintf(
      '%s%s, %s%s',
      $constraint->getLowerBound()->isInclusive() ? '>=' : '>',
      $constraint->getLowerBound()->getVersion(),
      $constraint->getUpperBound()->isInclusive() ? '<=' : '<',
      $constraint->getUpperBound()->getVersion()
    );
  }

}
