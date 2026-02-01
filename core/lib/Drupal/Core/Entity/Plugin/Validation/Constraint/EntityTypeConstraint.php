<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value is a valid entity type.
 */
#[Constraint(
  id: 'EntityType',
  label: new TranslatableMarkup('Entity type', [], ['context' => 'Validation']),
  type: ['entity', 'entity_reference']
)]
class EntityTypeConstraint extends SymfonyConstraint {

  /**
   * The entity type option.
   *
   * @var string
   */
  public $type;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $type = NULL,
    public $message = 'The entity must be of type %type.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->type = $type ?? $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'type';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['type'];
  }

}
