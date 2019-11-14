<?php

namespace Drupal\field\Tests\EntityReference;

@trigger_error(__NAMESPACE__ . '\EntityReferenceTestTrait is deprecated Drupal 8.6.2 for removal before 9.0.0. Use Drupal\Tests\field\Traits\EntityReferenceTestTrait instead. See https://www.drupal.org/node/2998888', E_USER_DEPRECATED);

use Drupal\Tests\field\Traits\EntityReferenceTestTrait as nonDeprecatedEntityReferenceTestTrait;

/**
 * Provides common functionality for the EntityReference test classes.
 *
 * @deprecated in drupal:8.6.2 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\field\Traits\EntityReferenceTestTrait instead.
 *
 * @see https://www.drupal.org/node/2998888
 */
trait EntityReferenceTestTrait {

  use nonDeprecatedEntityReferenceTestTrait;

}
