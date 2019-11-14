<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Block;

@trigger_error('The ' . __NAMESPACE__ . '\BlockResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\block\Functional\Rest\BlockResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\block\Functional\Rest\BlockResourceTestBase as BlockResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\block\Functional\Rest\BlockResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class BlockResourceTestBase extends BlockResourceTestBaseReal {
}
