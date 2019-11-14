<?php

namespace Drupal\Tests\rest\Functional\EntityResource\NodeType;

@trigger_error('The ' . __NAMESPACE__ . '\NodeTypeResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\node\Functional\Rest\NodeTypeResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\node\Functional\Rest\NodeTypeResourceTestBase as NodeTypeResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\node\Functional\Rest\NodeTypeResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class NodeTypeResourceTestBase extends NodeTypeResourceTestBaseReal {
}
