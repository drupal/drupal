<?php

namespace Drupal\field\Tests\EntityReference;

use Drupal\Tests\field\Traits\EntityReferenceTestTrait as nonDeprecatedEntityReferenceTestTrait;

/**
 * Provides common functionality for the EntityReference test classes.
 *
 * @deprecated in Drupal 8.6.2 for removal before 9.0.0. Use
 *   Drupal\Tests\field\Traits\EntityReferenceTestTrait instead.
 *
 * @see https://www.drupal.org/node/2998888
 */
trait EntityReferenceTestTrait {

  use nonDeprecatedEntityReferenceTestTrait;

}
