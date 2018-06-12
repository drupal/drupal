<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 field instance per form display source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d7\FieldInstancePerFormDisplay
 * @group field
 */
class FieldInstancePerFormDisplayTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
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
        'id' => '32',
        'field_id' => '14',
        'field_name' => 'field_integer',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'data' => 'a:7:{s:5:"label";s:7:"Integer";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:6:"number";s:6:"module";s:6:"number";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:5:{s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:14:"number_integer";s:8:"settings";a:4:{s:18:"thousand_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:5:"scale";i:0;s:13:"prefix_suffix";b:1;}s:6:"module";s:6:"number";s:6:"weight";i:1;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
        'deleted' => '0',
      ],
      [
        'id' => '25',
        'field_id' => '15',
        'field_name' => 'field_link',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'test_vocabulary',
        'data' => 'a:7:{s:5:"label";s:4:"Link";s:6:"widget";a:5:{s:6:"weight";s:2:"10";s:4:"type";s:10:"link_field";s:6:"module";s:4:"link";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:12:{s:12:"absolute_url";i:1;s:12:"validate_url";i:1;s:3:"url";i:0;s:5:"title";s:8:"optional";s:11:"title_value";s:19:"Unused Static Title";s:27:"title_label_use_field_label";i:0;s:15:"title_maxlength";s:3:"128";s:7:"display";a:1:{s:10:"url_cutoff";s:2:"81";}s:10:"attributes";a:6:{s:6:"target";s:6:"_blank";s:3:"rel";s:8:"nofollow";s:18:"configurable_class";i:0;s:5:"class";s:7:"classes";s:18:"configurable_title";i:1;s:5:"title";s:0:"";}s:10:"rel_remove";s:19:"rel_remove_external";s:13:"enable_tokens";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"link_default";s:6:"weight";s:1:"9";s:8:"settings";a:0:{}s:6:"module";s:4:"link";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'field_name' => 'body',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:4:"node";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:6:"format";a:2:{s:5:"table";s:13:"filter_format";s:7:"columns";a:1:{s:6:"format";s:6:"format";}}}s:7:"indexes";a:1:{s:6:"format";a:1:{i:0;s:6:"format";}}}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
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
        'translatable' => '1',
        'deleted' => '0',
      ],
      [
        'id' => '14',
        'field_name' => 'field_integer',
        'type' => 'number_integer',
        'module' => 'number',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:0:{}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:24:"field_data_field_integer";a:1:{s:5:"value";s:19:"field_integer_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:28:"field_revision_field_integer";a:1:{s:5:"value";s:19:"field_integer_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:0:{}s:2:"id";s:2:"14";}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '15',
        'field_name' => 'field_link',
        'type' => 'link_field',
        'module' => 'link',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:7:{s:10:"attributes";a:3:{s:6:"target";s:7:"default";s:5:"class";s:0:"";s:3:"rel";s:0:"";}s:3:"url";i:0;s:5:"title";s:8:"optional";s:11:"title_value";s:0:"";s:15:"title_maxlength";i:128;s:13:"enable_tokens";i:1;s:7:"display";a:1:{s:10:"url_cutoff";i:80;}}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_link";a:3:{s:3:"url";s:14:"field_link_url";s:5:"title";s:16:"field_link_title";s:10:"attributes";s:21:"field_link_attributes";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_link";a:3:{s:3:"url";s:14:"field_link_url";s:5:"title";s:16:"field_link_title";s:10:"attributes";s:21:"field_link_attributes";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:0:{}s:2:"id";s:2:"15";}',
        'cardinality' => '1',
        'translatable' => '1',
        'deleted' => '0',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
        'type' => 'text_with_summary',
        'widget' => [
          'module' => 'text',
          'settings' => [
            'rows' => 20,
            'summary_rows' => 5,
          ],
          'type' => 'text_textarea_with_summary',
          'weight' => -4,
        ],
        'field_definition' => [
          'id' => '2',
          'field_name' => 'body',
          'type' => 'text_with_summary',
          'module' => 'text',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:4:"node";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:6:"format";a:2:{s:5:"table";s:13:"filter_format";s:7:"columns";a:1:{s:6:"format";s:6:"format";}}}s:7:"indexes";a:1:{s:6:"format";a:1:{i:0;s:6:"format";}}}',
          'cardinality' => '1',
          'translatable' => '0',
          'deleted' => '0',
        ],
        'instances' => [
          [
            'id' => '2',
            'field_id' => '2',
            'field_name' => 'body',
            'entity_type' => 'node',
            'bundle' => 'page',
            'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
            'deleted' => '0',
            'type' => 'text_with_summary',
            'translatable' => '0',
          ],
        ],
      ],
      [
        'id' => '33',
        'field_id' => '11',
        'field_name' => 'field_file',
        'entity_type' => 'user',
        'bundle' => 'user',
        'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"8";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:3:"txt";s:12:"max_filesize";s:0:"";s:17:"description_field";i:0;s:18:"user_register_form";i:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:8:"settings";a:0:{}s:6:"module";s:4:"file";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
        'type' => 'file',
        'widget' => [
          'active' => 1,
          'module' => 'file',
          'settings' => [
            'progress_indicator' => 'throbber',
          ],
          'type' => 'file_generic',
          'weight' => '8',
        ],
        'field_definition' => [
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
          'translatable' => '1',
          'deleted' => '0',
        ],
        'instances' => [
          [
            'id' => '33',
            'field_id' => '11',
            'field_name' => 'field_file',
            'entity_type' => 'user',
            'bundle' => 'user',
            'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"8";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:3:"txt";s:12:"max_filesize";s:0:"";s:17:"description_field";i:0;s:18:"user_register_form";i:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:8:"settings";a:0:{}s:6:"module";s:4:"file";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";}',
            'deleted' => '0',
            'type' => 'file',
            'translatable' => '1',
          ],
        ],
      ],
      [
        'id' => '32',
        'field_id' => '14',
        'field_name' => 'field_integer',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'data' => 'a:7:{s:5:"label";s:7:"Integer";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:6:"number";s:6:"module";s:6:"number";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:5:{s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:14:"number_integer";s:8:"settings";a:4:{s:18:"thousand_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:5:"scale";i:0;s:13:"prefix_suffix";b:1;}s:6:"module";s:6:"number";s:6:"weight";i:1;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
        'deleted' => '0',
        'type' => 'number_integer',
        'widget' => [
          'weight' => '2',
          'type' => 'number',
          'module' => 'number',
          'active' => 0,
          'settings' => [],
        ],
        'field_definition' => [
          'id' => '14',
          'field_name' => 'field_integer',
          'type' => 'number_integer',
          'module' => 'number',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:0:{}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:24:"field_data_field_integer";a:1:{s:5:"value";s:19:"field_integer_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:28:"field_revision_field_integer";a:1:{s:5:"value";s:19:"field_integer_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:0:{}s:2:"id";s:2:"14";}',
          'cardinality' => '1',
          'translatable' => '0',
          'deleted' => '0',
        ],
        'instances' => [
          [
            'id' => '32',
            'field_id' => '14',
            'field_name' => 'field_integer',
            'entity_type' => 'comment',
            'bundle' => 'comment_node_test_content_type',
            'data' => 'a:7:{s:5:"label";s:7:"Integer";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:6:"number";s:6:"module";s:6:"number";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:5:{s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:14:"number_integer";s:8:"settings";a:4:{s:18:"thousand_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:5:"scale";i:0;s:13:"prefix_suffix";b:1;}s:6:"module";s:6:"number";s:6:"weight";i:1;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
            'deleted' => '0',
            'type' => 'number_integer',
            'translatable' => '0',
          ]
        ],
      ],
      [
        'id' => '25',
        'field_id' => '15',
        'field_name' => 'field_link',
        'entity_type' => 'taxonomy_term',
        'bundle' => 'test_vocabulary',
        'data' => 'a:7:{s:5:"label";s:4:"Link";s:6:"widget";a:5:{s:6:"weight";s:2:"10";s:4:"type";s:10:"link_field";s:6:"module";s:4:"link";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:12:{s:12:"absolute_url";i:1;s:12:"validate_url";i:1;s:3:"url";i:0;s:5:"title";s:8:"optional";s:11:"title_value";s:19:"Unused Static Title";s:27:"title_label_use_field_label";i:0;s:15:"title_maxlength";s:3:"128";s:7:"display";a:1:{s:10:"url_cutoff";s:2:"81";}s:10:"attributes";a:6:{s:6:"target";s:6:"_blank";s:3:"rel";s:8:"nofollow";s:18:"configurable_class";i:0;s:5:"class";s:7:"classes";s:18:"configurable_title";i:1;s:5:"title";s:0:"";}s:10:"rel_remove";s:19:"rel_remove_external";s:13:"enable_tokens";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"link_default";s:6:"weight";s:1:"9";s:8:"settings";a:0:{}s:6:"module";s:4:"link";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
        'deleted' => '0',
        'type' => 'link_field',
        'widget' => [
          'weight' => '10',
          'type' => 'link_field',
          'module' => 'link',
          'active' => 0,
          'settings' => [],
        ],
        'field_definition' => [
          'id' => '15',
          'field_name' => 'field_link',
          'type' => 'link_field',
          'module' => 'link',
          'active' => '1',
          'storage_type' => 'field_sql_storage',
          'storage_module' => 'field_sql_storage',
          'storage_active' => '1',
          'locked' => '0',
          'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:7:{s:10:"attributes";a:3:{s:6:"target";s:7:"default";s:5:"class";s:0:"";s:3:"rel";s:0:"";}s:3:"url";i:0;s:5:"title";s:8:"optional";s:11:"title_value";s:0:"";s:15:"title_maxlength";i:128;s:13:"enable_tokens";i:1;s:7:"display";a:1:{s:10:"url_cutoff";i:80;}}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_link";a:3:{s:3:"url";s:14:"field_link_url";s:5:"title";s:16:"field_link_title";s:10:"attributes";s:21:"field_link_attributes";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_link";a:3:{s:3:"url";s:14:"field_link_url";s:5:"title";s:16:"field_link_title";s:10:"attributes";s:21:"field_link_attributes";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:0:{}s:2:"id";s:2:"15";}',
          'cardinality' => '1',
          'translatable' => '1',
          'deleted' => '0',
        ],
        'instances' => [
          [
            'id' => '25',
            'field_id' => '15',
            'field_name' => 'field_link',
            'entity_type' => 'taxonomy_term',
            'bundle' => 'test_vocabulary',
            'data' => 'a:7:{s:5:"label";s:4:"Link";s:6:"widget";a:5:{s:6:"weight";s:2:"10";s:4:"type";s:10:"link_field";s:6:"module";s:4:"link";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:12:{s:12:"absolute_url";i:1;s:12:"validate_url";i:1;s:3:"url";i:0;s:5:"title";s:8:"optional";s:11:"title_value";s:19:"Unused Static Title";s:27:"title_label_use_field_label";i:0;s:15:"title_maxlength";s:3:"128";s:7:"display";a:1:{s:10:"url_cutoff";s:2:"81";}s:10:"attributes";a:6:{s:6:"target";s:6:"_blank";s:3:"rel";s:8:"nofollow";s:18:"configurable_class";i:0;s:5:"class";s:7:"classes";s:18:"configurable_title";i:1;s:5:"title";s:0:"";}s:10:"rel_remove";s:19:"rel_remove_external";s:13:"enable_tokens";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"link_default";s:6:"weight";s:1:"9";s:8:"settings";a:0:{}s:6:"module";s:4:"link";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
            'deleted' => '0',
            'type' => 'link_field',
            'translatable' => '1',
          ],
        ],
      ],
    ];

    return $tests;
  }

}
