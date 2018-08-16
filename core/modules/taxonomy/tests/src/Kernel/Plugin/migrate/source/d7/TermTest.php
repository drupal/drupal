<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests taxonomy term source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d7\Term
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
    $tests[0]['source_data']['taxonomy_term_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1',
        'description' => 'description value 1',
        'weight' => 0,
        'is_container' => FALSE,
      ],
      [
        'tid' => 2,
        'vid' => 6,
        'name' => 'name value 2',
        'description' => 'description value 2',
        'weight' => 0,
        'is_container' => TRUE,
      ],
      [
        'tid' => 3,
        'vid' => 6,
        'name' => 'name value 3',
        'description' => 'description value 3',
        'weight' => 0,
        'is_container' => FALSE,
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4',
        'description' => 'description value 4',
        'weight' => 1,
        'is_container' => FALSE,
      ],
      [
        'tid' => 5,
        'vid' => 6,
        'name' => 'name value 5',
        'description' => 'description value 5',
        'weight' => 1,
        'is_container' => FALSE,
      ],
      [
        'tid' => 6,
        'vid' => 6,
        'name' => 'name value 6',
        'description' => 'description value 6',
        'weight' => 0,
        'is_container' => TRUE,
      ],
      [
        'tid' => 7,
        'vid' => 3,
        'name' => 'name value 7',
        'description' => 'description value 7',
        'weight' => 0,
        'is_container' => TRUE,
      ],
    ];
    $tests[0]['source_data']['taxonomy_term_hierarchy'] = [
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
    $tests[0]['source_data']['taxonomy_vocabulary'] = [
      [
        'vid' => 5,
        'machine_name' => 'tags',
      ],
      [
        'vid' => 6,
        'machine_name' => 'categories',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '3',
        'translatable' => '0',
      ],
      [
        'id' => '4',
        'translatable' => '1',
      ],
      [
        'id' => '5',
        'translatable' => '1',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => 3,
        'field_name' => 'field_term_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => '3',
        'field_id' => 3,
        'field_name' => 'field_term_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'categories',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => '4',
        'field_id' => '4',
        'field_name' => 'name_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '5',
        'field_id' => '5',
        'field_name' => 'description_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_data_field_term_field'] = [
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => 0,
        'entity_id' => 1,
        'delta' => 0,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'categories',
        'deleted' => 0,
        'entity_id' => 1,
        'delta' => 0,
      ],
    ];
    $tests[0]['source_data']['field_data_name_field'] = [
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'name_field_value' => 'name value 1 (name_field)',
        'name_field_format' => NULL,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '4',
        'revision_id' => '4',
        'language' => 'und',
        'delta' => '0',
        'name_field_value' => 'name value 4 (name_field)',
        'name_field_format' => NULL,
      ],
    ];
    $tests[0]['source_data']['field_data_description_field'] = [
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'description_field_value' => 'description value 1 (description_field)',
        'description_field_format' => NULL,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '4',
        'revision_id' => '4',
        'language' => 'und',
        'delta' => '0',
        'description_field_value' => 'description value 4 (description_field)',
        'description_field_format' => NULL,
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
        'name' => 'title',
        'type' => 'module',
        'status' => 1,
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'forum_containers',
        'value' => 'a:3:{i:0;s:1:"5";i:1;s:1:"6";i:2;s:1:"7";}',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1 (name_field)',
        'description' => 'description value 1 (description_field)',
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
        'name' => 'name value 4 (name_field)',
        'description' => 'description value 4 (description_field)',
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

    // Change configuration to get one vocabulary, "tags".
    $tests[1]['source_data'] = $tests[0]['source_data'];
    $tests[1]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'name value 1 (name_field)',
        'description' => 'description value 1 (description_field)',
        'weight' => 0,
        'parent' => [0],
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'name value 4 (name_field)',
        'description' => 'description value 4 (description_field)',
        'weight' => 1,
        'parent' => [1],
      ],
    ];
    $tests[1]['expected_count'] = NULL;
    $tests[1]['configuration']['bundle'] = ['tags'];

    // Same as previous test, but with configuration vocabulary as a string
    // instead of an array.
    $tests[2]['source_data'] = $tests[0]['source_data'];
    $tests[2]['expected_data'] = $tests[1]['expected_data'];
    $tests[2]['expected_count'] = NULL;
    $tests[2]['configuration']['bundle'] = 'tags';

    // Change configuration to get two vocabularies, "tags" and "categories".
    $tests[3]['source_data'] = $tests[0]['source_data'];
    $tests[3]['expected_data'] = $tests[0]['expected_data'];
    // Remove the last element because it is for vid 3.
    array_pop($tests[3]['expected_data']);
    $tests[3]['expected_count'] = NULL;
    $tests[3]['configuration']['bundle'] = ['tags', 'categories'];

    return $tests;
  }

}
