<?php

namespace Drupal\Tests\rest\Functional\EntityResource\FilterFormat;

@trigger_error('The ' . __NAMESPACE__ . '\FilterFormatResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\filter\Functional\Rest\FilterFormatResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\filter\Functional\Rest\FilterFormatResourceTestBase as FilterFormatResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\filter\Functional\Rest\FilterFormatResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class FilterFormatResourceTestBase extends FilterFormatResourceTestBaseReal {
}
