<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 */
#[Constraint(
  id: 'ValidReference',
  label: new TranslatableMarkup('Entity Reference valid reference', [], ['context' => 'Validation'])
)]
class ValidReferenceConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'This entity (%type: %id) cannot be referenced.',
    public $nonExistingMessage = 'The referenced entity (%type: %id) does not exist.',
    public $invalidAutocreateMessage = 'This entity (%type: %label) cannot be referenced.',
    public $nullMessage = 'This value should not be null.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
