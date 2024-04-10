<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_url_alias source plugin.
 *
 * @covers \Drupal\path\Plugin\migrate\source\d6\UrlAlias
 * @group path
 */
class UrlAliasTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal', 'path'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['url_alias'] = [
      [
        'pid' => 1,
        'src' => 'node/1',
        'dst' => 'test-article',
        'language' => 'en',
      ],
      [
        'pid' => 2,
        'src' => 'node/2',
        'dst' => 'another-alias',
        'language' => 'en',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['url_alias'];

    return $tests;
  }

}
