<?php

declare(strict_types=1);

namespace Drupal\field\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates the structure of field storage index definitions.
 */
#[Constraint(
  id: 'FieldStorageIndexes',
  label: new TranslatableMarkup('Field storage indexes', [], ['context' => 'Validation']),
  type: ['sequence']
)]
class FieldStorageIndexesConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    public string $message = 'The field storage indexes definition is invalid.',
    public string $invalidIndexNameMessage = 'The index key "@index" must be a non-empty string.',
    public string $invalidIndexMessage = 'The index "@index" must be a non-empty array of column definitions.',
    public string $invalidColumnMessage = 'The index "@index" has an invalid column definition.',
    public string $invalidColumnLengthMessage = 'The index "@index" has an invalid length for column "@column".',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct(groups: $groups, payload: $payload);
  }

}
