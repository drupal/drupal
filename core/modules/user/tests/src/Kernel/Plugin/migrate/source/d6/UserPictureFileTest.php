<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\user\Plugin\migrate\source\d6\UserPictureFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the d6_user_picture_file source plugin.
 *
 * @legacy-covers \Drupal\user\Plugin\migrate\source\d6\UserPictureFile
 */
#[CoversClass(UserPictureFile::class)]
#[Group('user')]
class UserPictureFileTest extends MigrateSqlSourceTestBase {

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
