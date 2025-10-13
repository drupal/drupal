<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Kernel\Plugin\migrate\source\d7;

use Drupal\path\Plugin\migrate\source\d7\UrlAlias;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the d7_url_alias source plugin.
 */
#[CoversClass(UrlAlias::class)]
#[Group('path')]
#[RunTestsInSeparateProcesses]
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
