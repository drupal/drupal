<?php

declare(strict_types=1);

namespace Drupal\image_field_property_constraint_validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Provides a Contains Llamas constraint.
 */
#[Constraint(
  id: 'AltTextContainsLlamas',
  label: new TranslatableMarkup('Contains Llamas', options: ['context' => 'Validation'])
)]
final class AltTextContainsLlamasConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $message = 'Alternative text must contain some llamas.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
