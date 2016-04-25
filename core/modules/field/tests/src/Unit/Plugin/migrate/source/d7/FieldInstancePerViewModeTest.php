<?php

namespace Drupal\Tests\field\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 field instance per view mode source plugin.
 *
 * @group field
 */
class FieldInstancePerViewModeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\field\Plugin\migrate\source\d7\FieldInstancePerViewMode';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_field_instance_per_view_mode',
    ),
  );

  protected $expectedResults = array(
    array(
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'body',
      'label' => 'hidden',
      'type' => 'text_default',
      'settings' => array(),
      'module' => 'text',
      'weight' => 0,
      'view_mode' => 'default',
    ),
    array(
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'body',
      'label' => 'hidden',
      'type' => 'text_summary_or_trimmed',
      'settings' => array(
        'trim_length' => 600,
      ),
      'module' => 'text',
      'weight' => 0,
      'view_mode' => 'teaser',
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
    parent::setUp();
  }

}
