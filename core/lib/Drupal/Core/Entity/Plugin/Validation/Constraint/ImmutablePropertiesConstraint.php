<?php

declare(strict_types = 1);

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if config entity properties have been changed.
 */
#[Constraint(
  id: 'ImmutableProperties',
  label: new TranslatableMarkup('Properties are unchanged', [], ['context' => 'Validation']),
  type: ['entity']
)]
class ImmutablePropertiesConstraint extends SymfonyConstraint {

  /**
   * The names of the immutable properties.
   *
   * @var string[]
   */
  public array $properties = [];

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?array $properties = NULL,
    public string $message = "The '@name' property cannot be changed.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->properties = $properties ?? $this->properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'properties';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['properties'];
  }

}
