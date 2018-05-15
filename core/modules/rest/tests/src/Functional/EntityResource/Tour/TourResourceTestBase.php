<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Tour;

@trigger_error('The ' . __NAMESPACE__ . '\TourResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\tour\Functional\Rest\TourResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\tour\Functional\Rest\TourResourceTestBase as TourResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\tour\Functional\Rest\TourResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class TourResourceTestBase extends TourResourceTestBaseReal {
}
