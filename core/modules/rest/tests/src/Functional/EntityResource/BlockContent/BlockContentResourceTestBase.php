<?php

namespace Drupal\Tests\rest\Functional\EntityResource\BlockContent;

@trigger_error('The ' . __NAMESPACE__ . '\BlockContentResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\block_content\Functional\Rest\BlockContentResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\block_content\Functional\Rest\BlockContentResourceTestBase as BlockContentResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\block_content\Functional\Rest\BlockContentResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class BlockContentResourceTestBase extends BlockContentResourceTestBaseReal {
}
