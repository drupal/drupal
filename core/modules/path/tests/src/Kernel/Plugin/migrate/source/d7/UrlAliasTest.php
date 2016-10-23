<?php

namespace Drupal\Tests\path\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d7_url_alias source plugin.
 *
 * @covers \Drupal\path\Plugin\migrate\source\d7\UrlAlias
 * @group path
 */
class UrlAliasTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'path'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['url_alias'] = [
      [
        'pid' => 1,
        'source' => 'node/1',
        'alias' => 'test-article',
        'language' => 'en',
      ],
      [
        'pid' => 2,
        'source' => 'node/2',
        'alias' => 'another-alias',
        'language' => 'en',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['url_alias'];

    return $tests;
  }

}
