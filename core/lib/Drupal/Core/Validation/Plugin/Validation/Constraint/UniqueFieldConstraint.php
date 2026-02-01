<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if an entity field has a unique value.
 */
#[Constraint(
  id: 'UniqueField',
  label: new TranslatableMarkup('Unique field constraint', [], ['context' => 'Validation'])
)]
class UniqueFieldConstraint extends SymfonyConstraint {

  /**
   * This constraint is case-insensitive by default.
   *
   * For example "FOO" and "foo" would be considered as equivalent, and
   * validation of the constraint would fail.
   *
   * @var bool
   */
  public $caseSensitive = FALSE;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?bool $caseSensitive = NULL,
    public $message = 'A @entity_type with @field_name %value already exists.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->caseSensitive = $caseSensitive ?? $this->caseSensitive;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}
