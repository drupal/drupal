<?php

namespace Drupal\Tests\taxonomy\Kernel\Plugin\migrate\source\d7;

/**
 * Tests D7 i18n term localized source plugin.
 *
 * @covers \Drupal\taxonomy\Plugin\migrate\source\d7\TermTranslation
 * @group taxonomy
 */
class TermTranslationTest extends TermTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // Ignore i18_modes 0 and 1, get i18n_mode 4.
    $tests[0]['source_data']['taxonomy_term_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'fr - name 1',
        'description' => 'desc 1',
        'weight' => 0,
        'is_container' => FALSE,
        'language' => 'fr',
        'i18n_tsid' => '1',
      ],
      [
        'tid' => 2,
        'vid' => 5,
        'name' => 'name 2',
        'description' => 'desc 2',
        'weight' => 0,
        'is_container' => TRUE,
        'language' => 'en',
        'i18n_tsid' => '1',
      ],
      [
        'tid' => 3,
        'vid' => 6,
        'name' => 'name 3',
        'description' => 'desc 3',
        'weight' => 0,
        'is_container' => FALSE,
        'language' => '',
        'i18n_tsid' => '',
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'is - name 4',
        'description' => 'desc 4',
        'weight' => 1,
        'is_container' => FALSE,
        'language' => 'is',
        'i18n_tsid' => '1',
      ],
      [
        'tid' => 5,
        'vid' => 6,
        'name' => 'name 5',
        'description' => 'desc 5',
        'weight' => 1,
        'is_container' => FALSE,
        'language' => '',
        'i18n_tsid' => '',
      ],
      [
        'tid' => 6,
        'vid' => 6,
        'name' => 'name 6',
        'description' => 'desc 6',
        'weight' => 0,
        'is_container' => TRUE,
        'language' => '',
        'i18n_tsid' => '',
      ],
      [
        'tid' => 7,
        'vid' => 7,
        'name' => 'is - captains',
        'description' => 'desc 7',
        'weight' => 0,
        'is_container' => TRUE,
        'language' => 'is',
        'i18n_tsid' => '',
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
        'vid' => 3,
        'machine_name' => 'foo',
        'language' => 'und',
        'i18n_mode' => '0',
      ],
      [
        'vid' => 5,
        'machine_name' => 'tags',
        'language' => 'und',
        'i18n_mode' => '4',
      ],
      [
        'vid' => 6,
        'machine_name' => 'categories',
        'language' => 'is',
        'i18n_mode' => '1',
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
        'name_field_value' => 'fr - name 1',
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
        'name_field_value' => 'is - name 4',
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
        'description_field_value' => 'desc 1',
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
        'description_field_value' => 'desc 4',
        'description_field_format' => NULL,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'tid' => 1,
        'vid' => 5,
        'name' => 'fr - name 1',
        'description' => 'desc 1',
        'weight' => 0,
        'is_container' => '',
        'language' => 'fr',
        'i18n_tsid' => '1',
        'machine_name' => 'tags',
        'i18n_mode' => '4',
        'td_language' => 'fr',
        'tv_i18n_mode' => '4',
      ],
      [
        'tid' => 2,
        'vid' => 5,
        'name' => 'name 2',
        'description' => 'desc 2',
        'weight' => 0,
        'is_container' => '',
        'language' => 'en',
        'i18n_tsid' => '1',
        'machine_name' => 'tags',
        'i18n_mode' => '4',
        'td_language' => 'en',
        'tv_i18n_mode' => '4',
      ],
      [
        'tid' => 4,
        'vid' => 5,
        'name' => 'is - name 4',
        'description' => 'desc 4',
        'weight' => 1,
        'is_container' => '',
        'language' => 'is',
        'i18n_tsid' => '1',
        'machine_name' => 'tags',
        'i18n_mode' => '4',
        'td_language' => 'is',
        'tv_i18n_mode' => '4',
      ],
    ];

    $tests[0]['expected_count'] = NULL;
    // Get translations for the tags bundle.
    $tests[0]['configuration']['bundle'] = ['tags'];

    // Ignore i18_modes 0. get i18n_mode 2 and 4.
    $tests[1] = $tests[0];
    // Change a vocabulary to using fixed translation.
    $tests[1]['source_data']['taxonomy_vocabulary'][2] = [
      'vid' => 7,
      'machine_name' => 'categories',
      'language' => 'is',
      'i18n_mode' => '2',
    ];

    // Add the term with fixed translation.
    $tests[1]['expected_data'][] = [
      'tid' => 7,
      'vid' => 7,
      'name' => 'is - captains',
      'description' => 'desc 7',
      'weight' => 0,
      'is_container' => '',
      'language' => 'is',
      'i18n_tsid' => '',
      'machine_name' => 'categories',
      'i18n_mode' => '2',
      'td_language' => 'is',
      'tv_i18n_mode' => '2',
    ];

    $tests[1]['expected_count'] = NULL;
    $tests[1]['configuration']['bundle'] = NULL;

    // No data returned when there is no i18n_mode column.
    $tests[2] = [];
    $tests[2]['source_data'] = $tests[0]['source_data'];
    foreach ($tests[2]['source_data']['taxonomy_vocabulary'] as &$table) {
      unset($table['i18n_mode']);
    }
    $tests[2]['expected_data'] = [0];
    $tests[2]['expected_count'] = 0;

    return $tests;
  }

}
