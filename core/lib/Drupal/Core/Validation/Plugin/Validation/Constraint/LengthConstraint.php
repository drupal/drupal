<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Length constraint.
 *
 * Overrides the symfony constraint to use Drupal-style replacement patterns.
 *
 * @todo Move this below the TypedData core component.
 */
#[Constraint(
  id: 'Length',
  label: new TranslatableMarkup('Length', [], ['context' => 'Validation']),
  type: ['string']
)]
class LengthConstraint extends Length {

  /**
   * {@inheritdoc}
   */
  #[HasNamedArguments]
  public function __construct(
    int|array|NULL $exactly = NULL,
    ?int $min = NULL,
    ?int $max = NULL,
    ?string $charset = NULL,
    ?callable $normalizer = NULL,
    ?string $countUnit = NULL,
    ?string $exactMessage = 'This value should have exactly %limit character.|This value should have exactly %limit characters.',
    ?string $minMessage = 'This value is too short. It should have %limit character or more.|This value is too short. It should have %limit characters or more.',
    ?string $maxMessage = 'This value is too long. It should have %limit character or less.|This value is too long. It should have %limit characters or less.',
    ?string $charsetMessage = NULL,
    ?array $groups = NULL,
    mixed $payload = NULL,
    ?array $options = NULL,
  ) {
    parent::__construct($exactly, $min, $max, $charset, $normalizer, $countUnit, $exactMessage, $minMessage, $maxMessage, $charsetMessage, $groups, $payload, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return '\Symfony\Component\Validator\Constraints\LengthValidator';
  }

}
