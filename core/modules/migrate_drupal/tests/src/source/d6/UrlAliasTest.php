<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\UrlAliasTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 url alias source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class UrlAliasTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\UrlAlias';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highwaterProperty' => array('field' => 'test'),
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_url_alias',
    ),
  );

  protected $expectedResults = array(
    array(
      'pid' => 1,
      'src' => 'node/1',
      'dst' => 'test-article',
      'language' => 'en',
    ),
    array(
      'pid' => 2,
      'src' => 'node/2',
      'dst' => 'another-alias',
      'language' => 'en',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $row) {
      $this->databaseContents['url_alias'][] = $row;
    }
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 url alias source functionality',
      'description' => 'Tests the D6 url alias migrations.',
      'group' => 'Migrate Drupal',
    );
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\UrlAlias;

class TestUrlAlias extends UrlAlias {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
