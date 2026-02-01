<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\Count;

/**
 * Count constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 */
#[Constraint(
  id: 'Count',
  label: new TranslatableMarkup('Count', [], ['context' => 'Validation']),
  type: ['list']
)]
class CountConstraint extends Count {

  #[HasNamedArguments]
  public function __construct(
    int|array|null $exactly = NULL,
    ?int $min = NULL,
    ?int $max = NULL,
    ?int $divisibleBy = NULL,
    ?string $exactMessage = 'This collection should contain exactly %limit element.|This collection should contain exactly %limit elements.',
    ?string $minMessage = 'This collection should contain %limit element or more.|This collection should contain %limit elements or more.',
    ?string $maxMessage = 'This collection should contain %limit element or less.|This collection should contain %limit elements or less.',
    ?string $divisibleByMessage = NULL,
    ?array $groups = NULL,
    mixed $payload = NULL,
    ?array $options = NULL,
  ) {
    parent::__construct($exactly, $min, $max, $divisibleBy, $exactMessage, $minMessage, $maxMessage, $divisibleByMessage, $groups, $payload, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return '\Symfony\Component\Validator\Constraints\CountValidator';
  }

}
