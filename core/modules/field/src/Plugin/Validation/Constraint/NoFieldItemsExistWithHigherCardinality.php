<?php

declare(strict_types=1);

namespace Drupal\field\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Checks if field items exist that exceed the allowed cardinality.
 */
#[Constraint(
  id: 'NoFieldItemsExistWithHigherCardinality',
  label: new TranslatableMarkup('No field items exist with higher cardinality', [], ['context' => 'Validation'])
)]
class NoFieldItemsExistWithHigherCardinality extends SymfonyConstraint {

  public function __construct(
    public string $entityType,
    public string $fieldName,
    public string $message = "The field '@field_name' of entity type '@entity_type' has more entries (@max_delta) than the cardinality (@cardinality) allows.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct(groups: $groups, payload: $payload);
  }

}
