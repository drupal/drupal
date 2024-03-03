<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for the entity changed timestamp.
 */
#[Constraint(
  id: 'EntityUntranslatableFields',
  label: new TranslatableMarkup('Entity untranslatable fields', [], ['context' => 'Validation']),
  type: ['entity']
)]
class EntityUntranslatableFieldsConstraint extends SymfonyConstraint {

  public $defaultRevisionMessage = 'Non-translatable fields can only be changed when updating the current revision.';
  public $defaultTranslationMessage = 'Non-translatable fields can only be changed when updating the original language.';

}
