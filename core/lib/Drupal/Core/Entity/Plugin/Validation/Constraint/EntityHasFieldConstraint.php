<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value is an entity that has a specific field.
 */
#[Constraint(
  id: 'EntityHasField',
  label: new TranslatableMarkup('Entity has field', [], ['context' => 'Validation']),
  type: ['entity']
)]
class EntityHasFieldConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The entity must have the %field_name field.';

  /**
   * The violation message for non-fieldable entities.
   *
   * @var string
   */
  public $notFieldableMessage = 'The entity does not support fields.';

  /**
   * The field name option.
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $field_name;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'field_name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return (array) $this->getDefaultOption();
  }

}
