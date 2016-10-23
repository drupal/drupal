<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_user_picture source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d6\UserPicture
 * @group user
 */
class UserPictureTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['users'] = [
      [
        'uid' => 1,
        'access' => 1382835435,
        'picture' => 'sites/default/files/pictures/picture-1.jpg',
      ],
      [
        'uid' => 2,
        'access' => 1382835436,
        'picture' => 'sites/default/files/pictures/picture-2.jpg',
      ],
    ];

    // User picture data model is identical in source input and output.
    $tests[0]['expected_data'] = $tests[0]['source_data']['users'];

    return $tests;
  }

}
