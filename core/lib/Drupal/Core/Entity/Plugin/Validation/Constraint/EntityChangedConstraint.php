<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
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

  public $message = 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.';

}
