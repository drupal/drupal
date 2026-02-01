<?php

namespace Drupal\media\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates media mappings.
 *
 * @internal
 */
#[Constraint(
  id: 'MediaMappingsConstraint',
  label: new TranslatableMarkup('Media Mapping Constraint', [], ['context' => 'Validation']),
  type: 'string'
)]
class MediaMappingsConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $invalidMappingMessage = 'It is not possible to map the source field @source_field_name of a media type.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
