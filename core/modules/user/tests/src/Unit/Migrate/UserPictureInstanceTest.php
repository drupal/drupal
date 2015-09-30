<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Migrate\UserPictureInstanceTest.
 */

namespace Drupal\Tests\user\Unit\Migrate;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;
use Drupal\user\Plugin\migrate\source\UserPictureInstance;

/**
 * Tests user_picture_instance source plugin.
 *
 * @group user
 */
class UserPictureInstanceTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = UserPictureInstance::class;

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'user_picture_instance',
    ],
  ];

  protected $expectedResults = array(
    array(
      'id' => '',
      'file_directory' => 'pictures',
      'max_filesize' => '128KB',
      'max_resolution' => '128x128',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['variable'] = array(
      array(
        'name' => 'file_directory',
        'value' => serialize(NULL),
      ),
      array(
        'name' => 'user_picture_file_size',
        'value' => serialize(128),
      ),
      array(
        'name' => 'user_picture_dimensions',
        'value' => serialize('128x128'),
      ),
    );
    parent::setUp();
  }

}
