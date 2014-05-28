<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\UserPictureTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 user picture source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class UserPictureTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\UserPicture';

  protected $migrationConfiguration = array(
    'id' => 'test_user_picture',
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_user_picture',
    ),
  );

  protected $expectedResults = array(
    array(
      'uid' => 1,
      'access' => 1382835435,
      'picture' => 'sites/default/files/pictures/picture-1.jpg',
    ),
    array(
      'uid' => 2,
      'access' => 1382835436,
      'picture' => 'sites/default/files/pictures/picture-2.jpg',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['users'] = $this->expectedResults;
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 user picture source functionality',
      'description' => 'Tests D6 user picture source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\UserPicture;

class TestUserPicture extends UserPicture {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
