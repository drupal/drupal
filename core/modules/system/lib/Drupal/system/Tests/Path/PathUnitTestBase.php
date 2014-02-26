<?php

/**
 * @file
 * Contains Drupal\system\Tests\Path\PathUnitTestBase.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Database\Database;

/**
 * Defines a base class for path unit testing.
 */
class PathUnitTestBase extends DrupalUnitTestBase {

  public function setUp() {
    parent::setUp();
    $this->fixtures = new UrlAliasFixtures();
    // The alias whitelist expects that the menu path roots are set by a
    // menu router rebuild.
    \Drupal::state()->set('router.path_roots', array('user', 'admin'));
  }

  public function tearDown() {
    $this->fixtures->dropTables(Database::getConnection());

    parent::tearDown();
  }
}
