<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RegexConstraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Checks that the value is a valid extension name.
 */
#[Constraint(
  id: 'ExtensionName',
  label: new TranslatableMarkup('Valid extension name', [], ['context' => 'Validation']),
)]
class ExtensionNameConstraint extends RegexConstraint {

  #[HasNamedArguments]
  public function __construct(
    string|array|NULL $pattern,
    ?string $message = 'This value is not a valid extension name.',
    ?string $htmlPattern = NULL,
    ?bool $match = NULL,
    ?callable $normalizer = NULL,
    ?array $groups = NULL,
    mixed $payload = NULL,
    ?array $options = NULL,
  ) {
    // Always use the regular expression that ExtensionDiscovery uses to find
    // valid extensions.
    $pattern = ExtensionDiscovery::PHP_FUNCTION_PATTERN;
    parent::__construct($pattern, $message, $htmlPattern, $match, $normalizer, $groups, $payload, $options);
  }

}
