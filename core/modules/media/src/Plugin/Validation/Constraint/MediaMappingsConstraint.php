<?php

namespace Drupal\media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates media mappings.
 *
 * @internal
 *
 * @Constraint(
 *   id = "MediaMappingsConstraint",
 *   label = @Translation("Media Mapping Constraint", context = "Validation"),
 *   type = {"string"}
 * )
 */
class MediaMappingsConstraint extends Constraint {

  /**
   * The error message if source is used in media mapping.
   *
   * @var string
   */
  public string $invalidMappingMessage = 'It is not possible to map the source field @source_field_name of a media type.';

}
