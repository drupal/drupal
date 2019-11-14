<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Media;

@trigger_error('The ' . __NAMESPACE__ . '\MediaResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\media\Functional\Rest\MediaResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\media\Functional\Rest\MediaResourceTestBase as MediaResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\media\Functional\Rest\MediaResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class MediaResourceTestBase extends MediaResourceTestBaseReal {
}
