<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ImageStyle;

@trigger_error('The ' . __NAMESPACE__ . '\ImageStyleResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\image\Functional\Rest\ImageStyleResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\image\Functional\Rest\ImageStyleResourceTestBase as ImageStyleResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\image\Functional\Rest\ImageStyleResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ImageStyleResourceTestBase extends ImageStyleResourceTestBaseReal {
}
