<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Message;

@trigger_error('The ' . __NAMESPACE__ . '\MessageResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\contact\Functional\Rest\MessageResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\contact\Functional\Rest\MessageResourceTestBase as MessageResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\contact\Functional\Rest\MessageResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class MessageResourceTestBase extends MessageResourceTestBaseReal {
}
