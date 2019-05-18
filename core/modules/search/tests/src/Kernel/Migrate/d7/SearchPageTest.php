<?php

namespace Drupal\Tests\search\Kernel\Migrate\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 search page source plugin.
 *
 * @covers \Drupal\search\Plugin\migrate\source\d7\SearchPage
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
          'name' => 'search_active_modules',
          'value' => 'a:2:{s:4:"node";s:4:"node";s:4:"user";i:0;}',
        ],
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
          'status' => '0',
        ],
        [
          'name' => 'user',
          'type' => 'module',
          'status' => '1',
        ],
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'module' => 'node',
        'status' => 'node',
        'module_exists' => FALSE,
        'node_rank_comments' => '5',
        'node_rank_promote' => '1',
      ],
      [
        'module' => 'user',
        'status' => 0,
        'module_exists' => TRUE,
      ],
    ];

    $tests[0]['expected_count'] = NULL;

    $tests[0]['configuration'] = [
      'variables' => ['node_rank_comments', 'node_rank_promote'],
    ];

    return $tests;
  }

}
