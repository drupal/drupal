<?php

/**
 * @file
 * Contains Drupal\system\Tests\Path\PathUnitTestBase.
 */

namespace Drupal\system\Tests\Path;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Database\Database;

/**
 * Base class for Path/URL alias integration tests.
 */
abstract class PathUnitTestBase extends DrupalUnitTestBase {

  /**
   * @var \Drupal\system\Tests\Path\UrlAliasFixtures
   */
  protected $fixtures;

  protected function setUp() {
    parent::setUp();
    $this->fixtures = new UrlAliasFixtures();
    // The alias whitelist expects that the menu path roots are set by a
    // menu router rebuild.
    \Drupal::state()->set('router.path_roots', array('user', 'admin'));
  }

  protected function tearDown() {
    $this->fixtures->dropTables(Database::getConnection());

    parent::tearDown();
  }
}
