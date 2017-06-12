<?php

namespace Drupal\system\Tests\Database;

@trigger_error(__NAMESPACE__ . '\DatabaseWebTestBase is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\system\Functional\Database\DatabaseTestBase', E_USER_DEPRECATED);

use Drupal\KernelTests\Core\Database\DatabaseTestBase;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for databases database tests.
 *
 * @deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead
 *   use \Drupal\Tests\system\Functional\Database\DatabaseTestBase.
 */
abstract class DatabaseWebTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['database_test'];

  protected function setUp() {
    parent::setUp();

    DatabaseTestBase::addSampleData();
  }

}
