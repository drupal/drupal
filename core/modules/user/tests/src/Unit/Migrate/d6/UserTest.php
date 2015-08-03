<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Migrate\d6\UserTest.
 */

namespace Drupal\Tests\user\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 user source plugin.
 *
 * @group user
 */
class UserTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\user\Plugin\migrate\source\d6\User';

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
    // getDatabase() will not create empty tables, so we need to insert data
    // even if it's irrelevant to the test.
    $this->databaseContents['users_roles'] = array(
      array(
        'uid' => 99,
        'rid' => 99,
      ),
    );
    parent::setUp();
  }

}
