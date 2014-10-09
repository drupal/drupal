<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\SqlBaseTest
 */

namespace Drupal\migrate\Tests;

use Drupal\migrate\Plugin\migrate\source\TestSqlBase;
use Drupal\Core\Database\Database;

/**
 * Test the functionality of SqlBase.
 *
 * @group migrate
 */
class SqlBaseTest extends MigrateTestBase {

  /**
   * Test different connection types.
   */
  public function testConnectionTypes() {
    $sql_base = new TestSqlBase();
    $this->assertIdentical($sql_base->getDatabase()->getTarget(), 'default');

    $target = 'test_db_target';
    $config = array('target' => $target);
    $sql_base->setConfiguration($config);
    Database::addConnectionInfo('migrate', $target, Database::getConnectionInfo('default')['default']);

    $this->assertIdentical($sql_base->getDatabase()->getTarget(), $target);
  }

}

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * A dummy source to help with testing SqlBase.
 *
 * @package Drupal\migrate\Plugin\migrate\source
 */
class TestSqlBase extends SqlBase {

  /**
   * Override the constructor so we can create one easily.
   */
  public function __construct() {}

  /**
   * Get the database without caching it.
   */
  public function getDatabase() {
    $this->database = NULL;
    return parent::getDatabase();
  }

  /**
   * Allow us to set the configuration from a test.
   *
   * @param array $config
   *   The config array.
   */
  public function setConfiguration($config) {
    $this->configuration = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {}

  /**
   * {@inheritdoc}
   */
  public function fields() {}

  /**
   * {@inheritdoc}
   */
  public function query() {}

}
