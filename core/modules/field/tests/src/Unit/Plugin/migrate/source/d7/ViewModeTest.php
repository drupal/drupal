<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\source\d7\ViewModeTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 view mode source plugin.
 *
 * @group field
 */
class ViewModeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\field\Plugin\migrate\source\d7\ViewMode';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_view_mode',
    ),
  );

  protected $expectedResults = array(
    array(
      'entity_type' => 'node',
      'view_mode' => 'default',
    ),
    array(
      'entity_type' => 'node',
      'view_mode' => 'teaser',
    ),
    array(
      'entity_type' => 'user',
      'view_mode' => 'default',
    ),
    array(
      'entity_type' => 'comment',
      'view_mode' => 'default',
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['field_config_instance'] = array(
      array(
        'id' => '13',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'forum',
        'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:1;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:11;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:11;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ),
      array(
        'id' => '33',
        'field_id' => '11',
        'field_name' => 'field_file',
        'entity_type' => 'user',
        'bundle' => 'user',
        'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"8";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:3:"txt";s:12:"max_filesize";s:0:"";s:17:"description_field";i:0;s:18:"user_register_form";i:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:8:"settings";a:0:{}s:6:"module";s:4:"file";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ),
      array(
        'id' => '12',
        'field_id' => '1',
        'field_name' => 'comment_body',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_forum',
        'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
        'deleted' => '0',
      ),
    );
    parent::setUp();
  }

}
