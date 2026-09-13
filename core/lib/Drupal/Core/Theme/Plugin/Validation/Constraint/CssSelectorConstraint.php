<?php

declare(strict_types = 1);

namespace Drupal\Core\Theme\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if the string is a correct CSS selector.
 */
#[Constraint(
  id: 'CssSelector',
  label: new TranslatableMarkup('CSS selector', [], ['context' => 'Validation']),
)]
class CssSelectorConstraint extends SymfonyConstraint {

  public function __construct(
    public string $message = 'The CSS selector @scope is invalid.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct(groups: $groups, payload: $payload);
  }

}
