<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Defines an access validation constraint for links.
 */
#[Constraint(
  id: 'LinkAccess',
  label: new TranslatableMarkup('Link URI can be accessed by the user.', [], ['context' => 'Validation'])
)]
class LinkAccessConstraint extends SymfonyConstraint {

  public function __construct(
    public string $message = "The URL '@uri' is inaccessible.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct(groups: $groups, payload: $payload);
  }

}
