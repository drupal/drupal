<?php

namespace Drupal\Tests\rest\Functional\EntityResource\MenuLinkContent;

@trigger_error('The ' . __NAMESPACE__ . '\MenuLinkContentResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\menu_link_content\Functional\Rest\MenuLinkContentResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\menu_link_content\Functional\Rest\MenuLinkContentResourceTestBase as MenuLinkContentResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\menu_link_content\Functional\Rest\MenuLinkContentResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class MenuLinkContentResourceTestBase extends MenuLinkContentResourceTestBaseReal {
}
