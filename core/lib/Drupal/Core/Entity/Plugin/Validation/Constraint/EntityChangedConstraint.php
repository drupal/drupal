<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for the entity changed timestamp.
 */
#[Constraint(
  id: 'EntityChanged',
  label: new TranslatableMarkup('Entity changed', [], ['context' => 'Validation']),
  type: ['entity']
)]
class EntityChangedConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
