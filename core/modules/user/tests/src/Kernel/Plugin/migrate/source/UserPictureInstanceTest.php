<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the user_picture_instance source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\UserPictureInstance
 * @group user
 */
class UserPictureInstanceTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'file_directory',
        'value' => serialize(NULL),
      ],
      [
        'name' => 'user_picture_file_size',
        'value' => serialize(128),
      ],
      [
        'name' => 'user_picture_dimensions',
        'value' => serialize('128x128'),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'id' => '',
        'file_directory' => 'pictures',
        'max_filesize' => '128KB',
        'max_resolution' => '128x128',
      ],
    ];

    return $tests;
  }

}
