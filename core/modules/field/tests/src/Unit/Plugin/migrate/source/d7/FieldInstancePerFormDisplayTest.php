<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\source\d7\FieldInstancePerFormDisplayTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 field instance per form display source plugin.
 *
 * @group field
 */
class FieldInstancePerFormDisplayTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\field\Plugin\migrate\source\d7\FieldInstancePerFormDisplay';

  protected $migrationConfiguration = array(
    'id' => 'test_fieldinstance',
    'source' => array(
      'plugin' => 'd7_field_instance_per_form_display',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  protected $expectedResults = array(
    array(
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'page',
      'widget_settings' => array(
      ),
      'display_settings' => array(
      ),
      'description' => '',
      'required' => FALSE,
      'global_settings' => array(),
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['field_config_instance'] = array(
      array(
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ),
    );
    $this->databaseContents['field_config'] = array(
      array(
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
      ),
    );

    parent::setUp();
  }

}
