<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel\Migrate\d6;

use Drupal\search\Plugin\migrate\source\d6\SearchPage;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests D6 search page source plugin.
 */
#[CoversClass(SearchPage::class)]
#[Group('search')]
#[RunTestsInSeparateProcesses]
class SearchPageTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests[0]['source_data'] = [
      'variable' => [
        [
          'name' => 'node_rank_comments',
          'value' => 's:1:"5";',
        ],
        [
          'name' => 'node_rank_promote',
          'value' => 's:1:"1";',
        ],
      ],
      'system' => [
        [
          'name' => 'node',
          'type' => 'module',
          'status' => '1',
        ],
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'module' => 'node',
        'node_rank_comments' => '5',
        'node_rank_promote' => '1',
      ],
    ];

    $tests[0]['expected_count'] = NULL;

    $tests[0]['configuration'] = [
      'variables' => ['node_rank_comments', 'node_rank_promote'],
    ];

    return $tests;
  }

}
