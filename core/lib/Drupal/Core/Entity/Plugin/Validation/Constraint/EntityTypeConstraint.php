<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
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
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The entity must be of type %type.';

  /**
   * The entity type option.
   *
   * @var string
   */
  public $type;

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
