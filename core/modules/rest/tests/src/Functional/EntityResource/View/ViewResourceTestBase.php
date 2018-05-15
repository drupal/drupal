<?php

namespace Drupal\Tests\rest\Functional\EntityResource\View;

@trigger_error('The ' . __NAMESPACE__ . '\ViewResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\views\Functional\Rest\ViewResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\views\Functional\Rest\ViewResourceTestBase as ViewResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\views\Functional\Rest\ViewResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ViewResourceTestBase extends ViewResourceTestBaseReal {
}
