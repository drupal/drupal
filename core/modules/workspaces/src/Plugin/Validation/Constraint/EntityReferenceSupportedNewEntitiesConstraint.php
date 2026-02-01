<?php

namespace Drupal\workspaces\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * The entity reference supported new entities constraint.
 */
#[Constraint(
  id: 'EntityReferenceSupportedNewEntities',
  label: new TranslatableMarkup('Entity Reference Supported New Entities', [], ['context' => 'Validation'])
)]
class EntityReferenceSupportedNewEntitiesConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = '%collection_label can only be created in the default workspace.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
