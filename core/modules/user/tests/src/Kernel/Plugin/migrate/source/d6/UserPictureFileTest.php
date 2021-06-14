<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_user_picture_file source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d6\UserPictureFile
 * @group user
 */
class UserPictureFileTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['users'] = [
      [
        'uid' => '2',
        'picture' => 'core/tests/fixtures/files/image-test.jpg',
      ],
      [
        'uid' => '15',
        'picture' => '',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'uid' => '2',
        'picture' => 'core/tests/fixtures/files/image-test.jpg',
      ],
    ];

    return $tests;
  }

}
