<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\CommentTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests D6 user source plugin.
 *
 * @group migrate_drupal
 */
class UserTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\User';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_user',
    ),
  );

  protected $expectedResults = array(
    array(
      'uid' => 2,
      'name' => 'admin',
      // @todo d6 hash?
      'pass' => '1234',
      'mail' => 'admin@example.com',
      'theme' => '',
      'signature' => '',
      'signature_format' => 0,
      'created' => 1279402616,
      'access' => 1322981278,
      'login' => 1322699994,
      'status' => 0,
      'timezone' => 'America/Lima',
      'language' => 'en',
      // @todo Add the file when needed.
      'picture' => 'sites/default/files/pictures/picture-1.jpg',
      'init' => 'admin@example.com',
      'data' => NULL,
    ),
    array(
      'uid' => 4,
      'name' => 'alice',
      // @todo d6 hash?
      'pass' => '1234',
      'mail' => 'alice@example.com',
      'theme' => '',
      'signature' => '',
      'signature_format' => 0,
      'created' => 1322981368,
      'access' => 1322982419,
      'login' => 132298140,
      'status' => 0,
      'timezone' => 'America/Lima',
      'language' => 'en',
      'picture' => '',
      'init' => 'alice@example.com',
      'data' => NULL,
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['users'][$k] = $row;
    }
    $this->databaseContents['users_roles'] = array();
    parent::setUp();
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\User;

class TestUser extends User {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
