<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Migrate\d6\UserPictureTest.
 */

namespace Drupal\Tests\user\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 user picture source plugin.
 *
 * @group user
 */
class UserPictureTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\user\Plugin\migrate\source\d6\UserPicture';

  protected $migrationConfiguration = array(
    'id' => 'test_user_picture',
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

}
