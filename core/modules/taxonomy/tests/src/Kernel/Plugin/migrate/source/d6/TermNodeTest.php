<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests d6_term_node source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d6\TermNode
 * @group taxonomy
 */
class TermNodeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['term_node'] = [
      [
        'nid' => '1',
        'vid' => '1',
        'tid' => '1',
      ],
      [
        'nid' => '1',
        'vid' => '1',
        'tid' => '4',
      ],
      [
        'nid' => '1',
        'vid' => '1',
        'tid' => '5',
      ],
    ];
    $tests[0]['source_data']['node'] = [
      [
        'nid' => '1',
        'vid' => '1',
        'type' => 'story',
        'language' => '',
        'title' => 'Test title',
        'uid' => '1',
        'status' => '1',
        'created' => '1388271197',
        'changed' => '1420861423',
        'comment' => '0',
        'promote' => '0',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '0',
        'translate' => '0',
      ],
    ];
    $tests[0]['source_data']['term_data'] = [
      [
        'tid' => '1',
        'vid' => '3',
        'name' => 'term 1 of vocabulary 3',
        'description' => 'description of term 1 of vocabulary 3',
        'weight' => '0',
      ],
      [
        'tid' => '4',
        'vid' => '3',
        'name' => 'term 4 of vocabulary 3',
        'description' => 'description of term 4 of vocabulary 3',
        'weight' => '6',
      ],
      [
        'tid' => '5',
        'vid' => '3',
        'name' => 'term 5 of vocabulary 3',
        'description' => 'description of term 5 of vocabulary 3',
        'weight' => '7',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'type' => 'story',
        'tid' => [1, 4, 5],
      ],
    ];

    // Set default value for expected count.
    $tests[0]['expected_count'] = NULL;

    // Set plugin configuration.
    $tests[0]['configuration'] = [
      'vid' => 3,
    ];

    return $tests;
  }

}
