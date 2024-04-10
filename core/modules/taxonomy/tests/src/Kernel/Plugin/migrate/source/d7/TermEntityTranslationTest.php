<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests taxonomy term entity translation source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d7\TermEntityTranslation
 * @group taxonomy
 */
class TermEntityTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['entity_translation'] = [
      [
        'entity_type' => 'taxonomy_term',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'en',
        'source' => '',
        'uid' => 1,
        'status' => 1,
        'translate' => 0,
        'created' => 1531343498,
        'changed' => 1531343498,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 2,
        'status' => 1,
        'translate' => 1,
        'created' => 1531343508,
        'changed' => 1531343508,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'es',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343528,
        'changed' => 1531343528,
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => 1,
        'field_name' => 'field_test',
        'type' => 'text',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 1,
        'data' => 'a:0:{}',
        'cardinality' => 1,
        'translatable' => 1,
        'deleted' => 0,
      ],
      [
        'id' => 2,
        'field_name' => 'name_field',
        'type' => 'text',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 1,
        'data' => 'a:0:{}',
        'cardinality' => 1,
        'translatable' => 1,
        'deleted' => 0,
      ],
      [
        'id' => 3,
        'field_name' => 'description_field',
        'type' => 'text',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 1,
        'data' => 'a:0:{}',
        'cardinality' => 1,
        'translatable' => 1,
        'deleted' => 0,
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '1',
        'field_id' => 1,
        'field_name' => 'field_test',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => 2,
        'field_id' => 2,
        'field_name' => 'name_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => 3,
        'field_id' => 3,
        'field_name' => 'description_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
    ];
    $tests[0]['source_data']['field_data_field_test'] = [
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'en',
        'delta' => 0,
        'field_test_value' => 'English field',
        'field_test_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'delta' => 0,
        'field_test_value' => 'French field',
        'field_test_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'es',
        'delta' => 0,
        'field_test_value' => 'Spanish field',
        'field_test_format' => 'filtered_html',
      ],
    ];
    $tests[0]['source_data']['field_data_name_field'] = [
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'en',
        'delta' => '0',
        'name_field_value' => 'Term Name EN',
        'name_field_format' => NULL,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'fr',
        'delta' => '0',
        'name_field_value' => 'Term Name FR',
        'name_field_format' => NULL,
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'es',
        'delta' => '0',
        'name_field_value' => 'Term Name ES',
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
        'language' => 'en',
        'delta' => '0',
        'description_field_value' => 'Term Description EN',
        'description_field_format' => 'full_html',
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'fr',
        'delta' => '0',
        'description_field_value' => 'Term Description FR',
        'description_field_format' => 'full_html',
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'tags',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'es',
        'delta' => '0',
        'description_field_value' => 'Term Description ES',
        'description_field_format' => 'full_html',
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
        'name' => 'title',
        'type' => 'module',
        'status' => 1,
      ],
    ];
    $tests[0]['source_data']['taxonomy_term_data'] = [
      [
        'tid' => 1,
        'vid' => 1,
        'name' => 'Term Name',
        'description' => 'Term Description',
        'format' => 'filtered_html',
        'weight' => 0,
      ],
    ];
    $tests[0]['source_data']['taxonomy_term_hierarchy'] = [
      [
        'tid' => 1,
        'parent' => 0,
      ],
    ];
    $tests[0]['source_data']['taxonomy_vocabulary'] = [
      [
        'vid' => 1,
        'name' => 'Tags',
        'machine_name' => 'tags',
        'description' => '',
        'hierarchy' => 0,
        'module' => 'taxonomy',
        'weight' => 0,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'taxonomy_term',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 2,
        'status' => 1,
        'translate' => 1,
        'created' => 1531343508,
        'changed' => 1531343508,
        'name' => 'Term Name FR',
        'description' => 'Term Description FR',
        'format' => 'full_html',
        'machine_name' => 'tags',
        'field_test' => [
          [
            'value' => 'French field',
            'format' => 'filtered_html',
          ],
        ],
      ],
      [
        'entity_type' => 'taxonomy_term',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'es',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343528,
        'changed' => 1531343528,
        'name' => 'Term Name ES',
        'description' => 'Term Description ES',
        'format' => 'full_html',
        'machine_name' => 'tags',
        'field_test' => [
          [
            'value' => 'Spanish field',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    return $tests;
  }

}
