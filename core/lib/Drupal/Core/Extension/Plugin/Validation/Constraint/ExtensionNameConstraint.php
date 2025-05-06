<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RegexConstraint;

/**
 * Checks that the value is a valid extension name.
 */
#[Constraint(
  id: 'ExtensionName',
  label: new TranslatableMarkup('Valid extension name', [], ['context' => 'Validation']),
)]
class ExtensionNameConstraint extends RegexConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public string $message = 'This value is not a valid extension name.';

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
