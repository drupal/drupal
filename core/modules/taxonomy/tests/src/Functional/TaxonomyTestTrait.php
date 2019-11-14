<?php

namespace Drupal\Tests\taxonomy\Functional;

@trigger_error(__NAMESPACE__ . '\TaxonomyTestTrait trait is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait instead. See https://www.drupal.org/node/3041703.', E_USER_DEPRECATED);

use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait as ActualTaxonomyTestTrait;

/**
 * Provides common helper methods for Taxonomy module tests.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait instead.
 *
 * @see https://www.drupal.org/node/3041703
 */
trait TaxonomyTestTrait {

  use ActualTaxonomyTestTrait;

}
