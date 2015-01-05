<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\MenuTestBase.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

abstract class MenuTestBase extends WebTestBase {

  use AssertBreadcrumbTrait;

}
