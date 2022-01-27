<?php

namespace Drupal\Component\Version;

/**
 * A value object representing a Drupal version constraint.
 */
class Constraint {

  /**
   * The constraint represented as a string. For example '>=8.x-5.x'.
   *
   * @var string
   */
  protected $constraint;

  /**
   * A list of associative arrays representing the constraint.
   *
   * Each containing the keys:
   *  - 'op': can be one of: '=', '==', '!=', '<>', '<', '<=', '>', or '>='.
   *  - 'version': A complete version, e.g. '4.5-beta3'.
   *
   * @var array[]
   */
  protected $constraintArray = [];

  /**
   * Constraint constructor.
   *
   * @param string $constraint
   *   The constraint string to create the object from. For example, '>8.x-1.1'.
   * @param string $core_compatibility
   *   Core compatibility declared for the current version of Drupal core.
   *   Normally this is set to \Drupal::CORE_COMPATIBILITY by the caller.
   */
  public function __construct($constraint, $core_compatibility) {
    $this->constraint = $constraint;
    $this->parseConstraint($constraint, $core_compatibility);
  }

  /**
   * Gets the constraint as a string.
   *
   * Can be used in the UI for reporting incompatibilities.
   *
   * @return string
   *   The constraint as a string.
   */
  public function __toString() {
    return $this->constraint;
  }

  /**
   * Determines if the provided version is satisfied by this constraint.
   *
   * @param string $version
   *   The version to check, for example '4.2'.
   *
   * @return bool
   *   TRUE if the provided version is satisfied by this constraint, FALSE if
   *   not.
   */
  public function isCompatible($version) {
    foreach ($this->constraintArray as $constraint) {
      if (!version_compare($version, $constraint['version'], $constraint['op'])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Parses a constraint string.
   *
   * @param string $constraint_string
   *   The constraint string to parse.
   * @param string $core_compatibility
   *   Core compatibility declared for the current version of Drupal core.
   *   Normally this is set to \Drupal::CORE_COMPATIBILITY by the caller.
   */
  private function parseConstraint($constraint_string, $core_compatibility) {
    // We use named sub-patterns and support every op that version_compare
    // supports. Also, op is optional and defaults to equals.
    $p_op = '(?<operation>!=|==|=|<|<=|>|>=|<>)?';
    // Core version is always optional: 8.x-2.x and 2.x is treated the same.
    $p_core = '(?:' . preg_quote($core_compatibility) . '-)?';
    $p_major = '(?<major>\d+)';
    // By setting the minor version to x, branches can be matched.
    $p_minor = '(?<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
    foreach (explode(',', $constraint_string) as $constraint) {
      if (preg_match("/^\s*$p_op\s*$p_core$p_major\.$p_minor/", $constraint, $matches)) {
        $op = !empty($matches['operation']) ? $matches['operation'] : '=';
        if ($matches['minor'] == 'x') {
          // Drupal considers "2.x" to mean any version that begins with
          // "2" (e.g. 2.0, 2.9 are all "2.x"). PHP's version_compare(),
          // on the other hand, treats "x" as a string; so to
          // version_compare(), "2.x" is considered less than 2.0. This
          // means that >=2.x and <2.x are handled by version_compare()
          // as we need, but > and <= are not.
          if ($op == '>' || $op == '<=') {
            $matches['major']++;
          }
          // Equivalence can be checked by adding two restrictions.
          if ($op == '=' || $op == '==') {
            $this->constraintArray[] = ['op' => '<', 'version' => ($matches['major'] + 1) . '.x'];
            $op = '>=';
          }
        }
        $this->constraintArray[] = ['op' => $op, 'version' => $matches['major'] . '.' . $matches['minor']];
      }
    }
  }

}
