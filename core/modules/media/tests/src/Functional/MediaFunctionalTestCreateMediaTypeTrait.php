<?php

namespace Drupal\Tests\media\Functional;

@trigger_error(__NAMESPACE__ . '\MediaFunctionalTestCreateMediaTypeTrait is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Use \Drupal\Tests\media\Traits\MediaTypeCreationTrait instead. See https://www.drupal.org/node/2981614.', E_USER_DEPRECATED);

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Trait with helpers for Media functional tests.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Tests\media\Traits\MediaTypeCreationTrait instead.
 *
 * @see https://www.drupal.org/node/2981614
 */
trait MediaFunctionalTestCreateMediaTypeTrait {

  use MediaTypeCreationTrait {
    createMediaType as traitCreateMediaType;
  }

  /**
   * Creates a media type.
   *
   * @param array $values
   *   The media type values.
   * @param string $source
   *   (optional) The media source plugin that is responsible for additional
   *   logic related to this media type. Defaults to 'test'.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A newly created media type.
   */
  protected function createMediaType(array $values = [], $source = 'test') {
    return $this->traitCreateMediaType($source, $values);
  }

}
