<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that config dependencies contain specific types of entities.
 */
#[Constraint(
  id: 'RequiredConfigDependencies',
  label: new TranslatableMarkup('Required config dependency types', [], ['context' => 'Validation'])
)]
class RequiredConfigDependenciesConstraint extends SymfonyConstraint {

  /**
   * The IDs of entity types that need to exist in config dependencies.
   *
   * For example, if an entity requires a filter format in its config
   * dependencies, this should contain `filter_format`.
   *
   * @var string[]
   */
  public array $entityTypes = [];

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?array $entityTypes = NULL,
    public string $message = 'This @entity_type requires a @dependency_type.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->entityTypes = $entityTypes ?? $this->entityTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['entityTypes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'entityTypes';
  }

}
