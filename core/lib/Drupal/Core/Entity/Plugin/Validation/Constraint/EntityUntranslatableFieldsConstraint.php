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
  id: 'EntityUntranslatableFields',
  label: new TranslatableMarkup('Entity untranslatable fields', [], ['context' => 'Validation']),
  type: ['entity']
)]
class EntityUntranslatableFieldsConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $defaultRevisionMessage = 'Non-translatable fields can only be changed when updating the current revision.',
    public $defaultTranslationMessage = 'Non-translatable fields can only be changed when updating the original language.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
