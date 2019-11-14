<?php

namespace Drupal\Tests\rest\Functional\EntityResource\DateFormat;

@trigger_error('The ' . __NAMESPACE__ . '\DateFormatResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\FunctionalTests\Rest\DateFormatResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\FunctionalTests\Rest\DateFormatResourceTestBase as DateFormatResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\FunctionalTests\Rest\DateFormatResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class DateFormatResourceTestBase extends DateFormatResourceTestBaseReal {
}
