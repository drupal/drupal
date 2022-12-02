<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RegexConstraint;

/**
 * Checks that the value is a valid extension name.
 *
 * @Constraint(
 *   id = "ExtensionName",
 *   label = @Translation("Valid extension name", context = "Validation")
 * )
 */
class ExtensionNameConstraint extends RegexConstraint {

  /**
   * Constructs an ExtensionNameConstraint object.
   *
   * @param string|array|null $pattern
   *   The regular expression to test for.
   * @param mixed ...$arguments
   *   Arguments to pass to the parent constructor.
   */
  public function __construct(string|array|null $pattern, ...$arguments) {
    // Always use the regular expression that ExtensionDiscovery uses to find
    // valid extensions.
    $pattern = ExtensionDiscovery::PHP_FUNCTION_PATTERN;
    parent::__construct($pattern, ...$arguments);
  }

}
