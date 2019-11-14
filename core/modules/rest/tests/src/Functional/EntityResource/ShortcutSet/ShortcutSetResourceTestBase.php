<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ShortcutSet;

@trigger_error('The ' . __NAMESPACE__ . '\ShortcutSetResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\shortcut\Functional\Rest\ShortcutSetResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\shortcut\Functional\Rest\ShortcutSetResourceTestBase as ShortcutSetResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\shortcut\Functional\Rest\ShortcutSetResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ShortcutSetResourceTestBase extends ShortcutSetResourceTestBaseReal {
}
