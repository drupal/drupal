<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the field option translation source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d7\FieldOptionTranslation
 * @group migrate_drupal
 */
class FieldOptionTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $test = [];

    // The source data.
    $test[0]['source_data']['field_config'] = [
      [
        'id' => '4',
        'field_name' => 'field_color',
        'type' => 'list_text',
        'module' => 'list',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";b:1;s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:14:"allowed_values";a:3:{i:0;s:5:"Green";i:1;s:5:"Black";i:2;s:5:"White";}s:23:"allowed_values_function";s:0:"";s:23:"entity_translation_sync";b:0;}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:22:"field_data_field_color";a:1:{s:5:"value";s:17:"field_color_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:26:"field_revision_field_color";a:1:{s:5:"value";s:17:"field_color_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:2:"id";s:2:"48";}',
        'cardinality' => '1',
        'translatable' => '1',
        'deleted' => '0',
      ],
      [
        'id' => '2',
        'field_name' => 'field_rating',
        'type' => 'list_text',
        'module' => 'list',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";b:1;s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:14:"allowed_values";a:3:{i:1;s:4:"High";i:2;s:6:"Medium";i:3;s:3:"Low";}s:23:"allowed_values_function";s:0:"";s:23:"entity_translation_sync";b:0;}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:23:"field_data_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:27:"field_revision_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:2:"id";s:2:"49";}',
        'cardinality' => '1',
        'translatable' => '1',
        'deleted' => '0',
      ],
    ];
    $test[0]['source_data']['field_config_instance'] = [
      [
        'id' => '76',
        'field_id' => '4',
        'field_name' => 'field_color',
        'entity_type' => 'node',
        'bundle' => 'blog',
        'data' => 'a:7:{s:5:"label";s:5:"Color";s:6:"widget";a:5:{s:6:"weight";s:2:"11";s:4:"type";s:14:"options_select";s:6:"module";s:7:"options";s:6:"active";i:1;s:8:"settings";a:0:{}}s:8:"settings";a:2:{s:18:"user_register_form";b:0;s:23:"entity_translation_sync";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"list_default";s:8:"settings";a:0:{}s:6:"module";s:4:"list";s:6:"weight";i:10;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
        'deleted' => '0',
      ],
      [
        'id' => '77',
        'field_id' => '2',
        'field_name' => 'field_rating',
        'entity_type' => 'node',
        'bundle' => 'blog',
        'data' => 'a:7:{s:5:"label";s:6:"Rating";s:6:"widget";a:5:{s:6:"weight";s:2:"12";s:4:"type";s:15:"options_buttons";s:6:"module";s:7:"options";s:6:"active";i:1;s:8:"settings";a:0:{}}s:8:"settings";a:2:{s:18:"user_register_form";b:0;s:23:"entity_translation_sync";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"list_default";s:8:"settings";a:0:{}s:6:"module";s:4:"list";s:6:"weight";i:11;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
        'deleted' => '0',
      ],
    ];

    $test[0]['source_data']['i18n_string'] = [
      [
        'lid' => '764',
        'textgroup' => 'field',
        'context' => 'field_color:blog:label',
        'objectid' => 'blog',
        'type' => 'field_color',
        'property' => 'label',
        'objectindex' => '0',
        'format' => '',
      ],
      [
        'lid' => '1',
        'textgroup' => 'field',
        'context' => 'field_rating:#allowed_values:1',
        'objectid' => '#allowed_values',
        'type' => 'field_rating',
        'property' => '1',
        'objectindex' => '0',
        'format' => '',
      ],
      [
        'lid' => '2',
        'textgroup' => 'field',
        'context' => 'field_rating:#allowed_values:2',
        'objectid' => '#allowed_values',
        'type' => 'field_rating',
        'property' => '2',
        'objectindex' => '0',
        'format' => '',
      ],
      [
        'lid' => '3',
        'textgroup' => 'field',
        'context' => 'field_rating:#allowed_values:3',
        'objectid' => '#allowed_values',
        'type' => 'field_rating',
        'property' => '3',
        'objectindex' => '0',
        'format' => '',
      ],
    ];
    $test[0]['source_data']['locales_target'] = [
      [
        'lid' => '764',
        'translation' => 'Color',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
      [
        'lid' => '1',
        'translation' => 'Haute',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
      [
        'lid' => '2',
        'translation' => 'Moyenne',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
      [
        'lid' => '3',
        'translation' => 'Faible',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
      [
        'lid' => '768',
        'translation' => 'Rating',
        'language' => 'fr',
        'plid' => '0',
        'plural' => '0',
        'i18n_status' => '0',
      ],
    ];
    $test[0]['expected_results'] = [
      [
        'i18n_lid' => '1',
        'textgroup' => 'field',
        'context' => 'field_rating:#allowed_values:1',
        'objectid' => '#allowed_values',
        'type' => 'list_text',
        'property' => '1',
        'objectindex' => '0',
        'format' => '',
        'language' => 'fr',
        'translation' => 'Haute',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
        'field_name' => 'field_rating',
        'data' => 'a:7:{s:12:"translatable";b:1;s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:14:"allowed_values";a:3:{i:1;s:4:"High";i:2;s:6:"Medium";i:3;s:3:"Low";}s:23:"allowed_values_function";s:0:"";s:23:"entity_translation_sync";b:0;}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:23:"field_data_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:27:"field_revision_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:2:"id";s:2:"49";}',
        'bundle' => 'blog',
        'entity_type' => 'node',
        'i18n_type' => 'field_rating',
      ],
      [
        'i18n_lid' => '2',
        'textgroup' => 'field',
        'context' => 'field_rating:#allowed_values:2',
        'objectid' => '#allowed_values',
        'type' => 'list_text',
        'property' => '2',
        'objectindex' => '0',
        'format' => '',
        'language' => 'fr',
        'translation' => 'Moyenne',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
        'field_name' => 'field_rating',
        'data' => 'a:7:{s:12:"translatable";b:1;s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:14:"allowed_values";a:3:{i:1;s:4:"High";i:2;s:6:"Medium";i:3;s:3:"Low";}s:23:"allowed_values_function";s:0:"";s:23:"entity_translation_sync";b:0;}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:23:"field_data_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:27:"field_revision_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:2:"id";s:2:"49";}',
        'bundle' => 'blog',
        'entity_type' => 'node',
        'i18n_type' => 'field_rating',
      ],
      [
        'i18n_lid' => '3',
        'textgroup' => 'field',
        'context' => 'field_rating:#allowed_values:3',
        'objectid' => '#allowed_values',
        'type' => 'list_text',
        'property' => '3',
        'objectindex' => '0',
        'format' => '',
        'language' => 'fr',
        'translation' => 'Faible',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
        'field_name' => 'field_rating',
        'data' => 'a:7:{s:12:"translatable";b:1;s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:14:"allowed_values";a:3:{i:1;s:4:"High";i:2;s:6:"Medium";i:3;s:3:"Low";}s:23:"allowed_values_function";s:0:"";s:23:"entity_translation_sync";b:0;}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:23:"field_data_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:27:"field_revision_field_rating";a:1:{s:5:"value";s:18:"field_rating_value";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:5:"value";a:1:{i:0;s:5:"value";}}s:2:"id";s:2:"49";}',
        'bundle' => 'blog',
        'entity_type' => 'node',
        'i18n_type' => 'field_rating',
      ],
    ];

    return $test;
  }

}
