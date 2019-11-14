<?php

namespace Drupal\system\Tests\Menu;

@trigger_error(__NAMESPACE__ . '\MenuTestBase is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\BrowserTestBase', E_USER_DEPRECATED);

use Drupal\simpletest\WebTestBase;

/**
 * Base class for Menu tests.
 *
 * @deprecated in drupal:8.?.? and is removed from drupal:9.0.0.
 *   Use \Drupal\Tests\BrowserTestBase instead.
 */
abstract class MenuTestBase extends WebTestBase {

  use AssertBreadcrumbTrait;

}
