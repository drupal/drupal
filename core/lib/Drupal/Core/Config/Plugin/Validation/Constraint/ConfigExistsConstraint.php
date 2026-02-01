<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that the value is the name of an existing config object.
 */
#[Constraint(
  id: 'ConfigExists',
  label: new TranslatableMarkup('Config exists', [], ['context' => 'Validation'])
)]
class ConfigExistsConstraint extends SymfonyConstraint {

  /**
   * Optional prefix, to be specified when this contains a config entity ID.
   *
   * Every config entity type can have multiple instances, all with unique IDs
   * but the same config prefix. When config refers to a config entity,
   * typically only the ID is stored, not the prefix.
   *
   * @var string
   */
  public string $prefix = '';

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $prefix = NULL,
    public string $message = "The '@name' config does not exist.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->prefix = $prefix ?? $this->prefix;
  }

}
