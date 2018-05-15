<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ResponsiveImageStyle;

@trigger_error('The ' . __NAMESPACE__ . '\ResponsiveImageStyleResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\responsive_image\Functional\Rest\ResponsiveImageStyleResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\responsive_image\Functional\Rest\ResponsiveImageStyleResourceTestBase as ResponsiveImageStyleResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\responsive_image\Functional\Rest\ResponsiveImageStyleResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ResponsiveImageStyleResourceTestBase extends ResponsiveImageStyleResourceTestBaseReal {
}
