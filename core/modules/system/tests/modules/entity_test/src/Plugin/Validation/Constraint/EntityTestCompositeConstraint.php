<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Constraint with multiple fields.
 */
#[Constraint(
  id: 'EntityTestComposite',
  label: new TranslatableMarkup('Constraint with multiple fields.'),
  type: ['entity']
)]
class EntityTestCompositeConstraint extends CompositeConstraintBase {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $message = 'Multiple fields are validated',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['name', 'type'];
  }

}
