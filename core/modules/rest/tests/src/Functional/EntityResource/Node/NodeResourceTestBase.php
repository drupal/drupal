<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Node;

@trigger_error('The ' . __NAMESPACE__ . '\NodeResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\node\Functional\Rest\NodeResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\node\Functional\Rest\NodeResourceTestBase as NodeResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\node\Functional\Rest\NodeResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class NodeResourceTestBase extends NodeResourceTestBaseReal {
}
