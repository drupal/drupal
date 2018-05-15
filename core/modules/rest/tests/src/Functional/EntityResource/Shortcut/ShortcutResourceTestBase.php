<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Shortcut;

@trigger_error('The ' . __NAMESPACE__ . '\ShortcutResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\shortcut\Functional\Rest\ShortcutResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\shortcut\Functional\Rest\ShortcutResourceTestBase as ShortcutResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\shortcut\Functional\Rest\ShortcutResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ShortcutResourceTestBase extends ShortcutResourceTestBaseReal {
}
