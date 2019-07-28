<?php

namespace Drupal\Tests\search\Kernel\Migrate\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 search page source plugin.
 *
 * @covers \Drupal\search\Plugin\migrate\source\d6\SearchPage
 * @group search
 */
class SearchPageTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['search', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
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
