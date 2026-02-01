<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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
   * The field name option.
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $field_name;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $field_name = NULL,
    public $message = 'The entity must have the %field_name field.',
    public $notFieldableMessage = 'The entity does not support fields.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->field_name = $field_name ?? $this->field_name;
  }

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
