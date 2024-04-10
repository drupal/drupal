<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_user source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d6\User
 * @group user
 */
class UserTest extends MigrateSqlSourceTestBase {

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
        'uid' => 2,
        'name' => 'admin',
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
      ],
      [
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
      ],
    ];

    // getDatabase() will not create empty tables, so we need to insert data
    // even if it's irrelevant to the test.
    $tests[0]['source_data']['users_roles'] = [
      [
        'uid' => 99,
        'rid' => 99,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['users'];

    return $tests;
  }

}
