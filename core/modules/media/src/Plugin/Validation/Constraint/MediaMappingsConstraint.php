<?php

namespace Drupal\media\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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

  /**
   * The error message if source is used in media mapping.
   *
   * @var string
   */
  public string $invalidMappingMessage = 'It is not possible to map the source field @source_field_name of a media type.';

}
