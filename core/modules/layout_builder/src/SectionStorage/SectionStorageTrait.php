<?php

namespace Drupal\layout_builder\SectionStorage;

@trigger_error(__NAMESPACE__ . '\SectionStorageTrait is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\layout_builder\SectionListTrait instead. See https://www.drupal.org/node/3091432', E_USER_DEPRECATED);

use Drupal\layout_builder\SectionListTrait;

/**
 * Provides a trait for storing sections on an object.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\layout_builder\SectionListTrait instead.
 *
 * @see https://www.drupal.org/node/3091432
 */
trait SectionStorageTrait {

  use SectionListTrait;

}
