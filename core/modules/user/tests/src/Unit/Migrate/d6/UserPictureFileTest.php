<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Migrate\d6\UserPictureFileTest.
 */

namespace Drupal\Tests\user\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;
use Drupal\user\Plugin\migrate\source\d6\UserPictureFile;

/**
 * Tests the d6_user_picture_file source plugin.
 *
 * @group user
 */
class UserPictureFileTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = UserPictureFile::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_user_picture_file',
    ),
  );

  protected $expectedResults = array(
    array(
      'uid' => '2',
      'picture' => 'core/modules/simpletest/files/image-test.jpg',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['users'] = array(
      array(
        'uid' => '2',
        'picture' => 'core/modules/simpletest/files/image-test.jpg',
      ),
      array(
        'uid' => '15',
        'picture' => '',
      ),
    );
    parent::setUp();
  }

}
