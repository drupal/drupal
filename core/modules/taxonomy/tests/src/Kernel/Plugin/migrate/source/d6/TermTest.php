<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests taxonomy term source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d6\Term
 * @group taxonomy
 */
class TermTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['term_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
      ],
      [
        'tid' => 2,
        'vid' => 6,
        'name' => 'name value 2',
        'description' => 'description value 2',
        'weight' => 0,
      ],
      [
        'tid' => 3,
        'vid' => 6,
        'name' => 'name value 3',
        'description' => 'description value 3',
        'weight' => 0,
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4',
        'description' => 'description value 4',
        'weight' => 1,
      ],
      [
        'tid' => 5,
        'vid' => 6,
        'name' => 'name value 5',
        'description' => 'description value 5',
        'weight' => 1,
      ],
      [
        'tid' => 6,
        'vid' => 6,
        'name' => 'name value 6',
        'description' => 'description value 6',
        'weight' => 0,
      ],
      [
        'tid' => 7,
        'vid' => 3,
        'name' => 'name value 7',
        'description' => 'description value 7',
        'weight' => 0,
      ],
    ];
    $tests[0]['source_data']['term_hierarchy'] = [
      [
        'tid' => 1,
        'parent' => 0,
      ],
      [
        'tid' => 2,
        'parent' => 0,
      ],
      [
        'tid' => 3,
        'parent' => 0,
      ],
      [
        'tid' => 4,
        'parent' => 1,
      ],
      [
        'tid' => 5,
        'parent' => 2,
      ],
      [
        'tid' => 6,
        'parent' => 3,
      ],
      [
        'tid' => 6,
        'parent' => 2,
      ],
      [
        'tid' => 7,
        'parent' => 0,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'parent' => [0],
      ],
      [
        'tid' => 2,
        'vid' => 6,
        'name' => 'name value 2',
        'description' => 'description value 2',
        'weight' => 0,
        'parent' => [0],
      ],
      [
        'tid' => 3,
        'vid' => 6,
        'name' => 'name value 3',
        'description' => 'description value 3',
        'weight' => 0,
        'parent' => [0],
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4',
        'description' => 'description value 4',
        'weight' => 1,
        'parent' => [1],
      ],
      [
        'tid' => 5,
        'vid' => 6,
        'name' => 'name value 5',
        'description' => 'description value 5',
        'weight' => 1,
        'parent' => [2],
      ],
      [
        'tid' => 6,
        'vid' => 6,
        'name' => 'name value 6',
        'description' => 'description value 6',
        'weight' => 0,
        'parent' => [3, 2],
      ],
      [
        'tid' => 7,
        'vid' => 3,
        'name' => 'name value 7',
        'description' => 'description value 7',
        'weight' => 0,
        'parent' => [0],
      ],
    ];

    $tests[0]['expected_count'] = NULL;
    // Empty configuration will return terms for all vocabularies.
    $tests[0]['configuration'] = [];

    // Change configuration to get one vocabulary, 5.
    $tests[1]['source_data'] = $tests[0]['source_data'];
    $tests[1]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'parent' => [0],
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4',
        'description' => 'description value 4',
        'weight' => 1,
        'parent' => [1],
      ],
    ];
    $tests[1]['expected_count'] = NULL;
    $tests[1]['configuration']['bundle'] = ['5'];

    // Same as previous test, but with configuration vocabulary as a string
    // instead of an array.
    $tests[2]['source_data'] = $tests[0]['source_data'];
    $tests[2]['expected_data'] = $tests[1]['expected_data'];
    $tests[2]['expected_count'] = NULL;
    $tests[2]['configuration']['bundle'] = '5';

    // Change configuration to get two vocabularies, 5 and 6.
    $tests[3]['source_data'] = $tests[0]['source_data'];
    $tests[3]['expected_data'] = $tests[0]['expected_data'];
    // Remove the last element because it is for vid 3.
    array_pop($tests[3]['expected_data']);
    $tests[3]['expected_count'] = NULL;
    $tests[3]['configuration']['bundle'] = ['5', '6'];

    return $tests;
  }

}
