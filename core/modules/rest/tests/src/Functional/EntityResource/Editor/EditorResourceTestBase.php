<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Editor;

@trigger_error('The ' . __NAMESPACE__ . '\EditorResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\editor\Functional\Rest\EditorResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\editor\Functional\Rest\EditorResourceTestBase as EditorResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\editor\Functional\Rest\EditorResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class EditorResourceTestBase extends EditorResourceTestBaseReal {
}
