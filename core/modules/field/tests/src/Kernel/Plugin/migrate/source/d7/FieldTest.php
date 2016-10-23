<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 field source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d7\Field
 * @group field
 */
class FieldTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
      ],
    ];

    // The source data.
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '11',
        'field_name' => 'field_file',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:13:"display_field";i:0;s:15:"display_default";i:0;s:10:"uri_scheme";s:6:"public";}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}}}}s:12:"foreign keys";a:1:{s:3:"fid";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:3:"fid";s:3:"fid";}}}s:7:"indexes";a:1:{s:3:"fid";a:1:{i:0;s:3:"fid";}}s:2:"id";s:2:"11";}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '33',
        'field_id' => '11',
        'field_name' => 'field_file',
        'entity_type' => 'user',
        'bundle' => 'user',
        'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"8";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:3:"txt";s:12:"max_filesize";s:0:"";s:17:"description_field";i:0;s:18:"user_register_form";i:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:8:"settings";a:0:{}s:6:"module";s:4:"file";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
      [
        'id' => '21',
        'field_id' => '11',
        'field_name' => 'field_file',
        'entity_type' => 'node',
        'bundle' => 'test_content_type',
        'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"5";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:15:"txt pdf ods odf";s:12:"max_filesize";s:5:"10 MB";s:17:"description_field";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:6:"weight";s:1:"5";s:8:"settings";a:0:{}s:6:"module";s:4:"file";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'field_name' => 'field_file',
        'type' => 'file',
        'storage' => [
          'active' => 1,
          'details' => [
            'sql' => [
              'FIELD_LOAD_CURRENT' => [
                'field_data_field_file' => [
                  'description' => 'field_file_description',
                  'display' => 'field_file_display',
                  'fid' => 'field_file_fid',
                ],
              ],
              'FIELD_LOAD_REVISION' => [
                'field_revision_field_file' => [
                  'description' => 'field_file_description',
                  'display' => 'field_file_display',
                  'fid' => 'field_file_fid',
                ],
              ],
            ],
          ],
          'module' => 'field_sql_storage',
          'settings' => [],
          'type' => 'field_sql_storage',
        ],
        'module' => 'file',
        'locked' => 0,
        'entity_type' => 'node',
      ],
      [
        'field_name' => 'field_file',
        'type' => 'file',
        'storage' => [
          'active' => 1,
          'details' => [
            'sql' => [
              'FIELD_LOAD_CURRENT' => [
                'field_data_field_file' => [
                  'description' => 'field_file_description',
                  'display' => 'field_file_display',
                  'fid' => 'field_file_fid',
                ],
              ],
              'FIELD_LOAD_REVISION' => [
                'field_revision_field_file' => [
                  'description' => 'field_file_description',
                  'display' => 'field_file_display',
                  'fid' => 'field_file_fid',
                ],
              ],
            ],
          ],
          'module' => 'field_sql_storage',
          'settings' => [],
          'type' => 'field_sql_storage',
        ],
        'module' => 'file',
        'locked' => 0,
        'entity_type' => 'user',
      ],
    ];

    return $tests;
  }

}
