<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\source\d6\FieldInstancePerViewModeTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\source\d6;

use Drupal\field\Plugin\migrate\source\d6\FieldInstancePerFormDisplay;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests d6_field_instance_per_form_display source plugin.
 *
 * @group field
 */
class FieldInstancePerFormDisplayTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = FieldInstancePerFormDisplay::class;

  protected $migrationConfiguration = array(
    'id' => 'view_mode_test',
    'source' => array(
      'plugin' => 'd6_field_instance_per_form_display',
    ),
  );

  protected $expectedResults = array(
    array(
      'display_settings' => array(),
      'widget_settings' => array(),
      'type_name' => 'story',
      'widget_active' => TRUE,
      'field_name' => 'field_test_filefield',
      'type' => 'filefield',
      'module' => 'filefield',
      'weight' => '8',
      'widget_type' => 'filefield_widget',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $empty_array = serialize([]);

    $this->databaseContents['content_node_field'] = array(
      array(
        'field_name' => 'field_test_filefield',
        'type' => 'filefield',
        'global_settings' => $empty_array,
        'required' => '0',
        'multiple' => '0',
        'db_storage' => '1',
        'module' => 'filefield',
        'db_columns' => $empty_array,
        'active' => '1',
        'locked' => '0',
      )
    );
    $this->databaseContents['content_node_field_instance'] = array(
      array(
        'field_name' => 'field_test_filefield',
        'type_name' => 'story',
        'weight' => '8',
        'label' => 'File Field',
        'widget_type' => 'filefield_widget',
        'widget_settings' => $empty_array,
        'display_settings' => $empty_array,
        'description' => 'An example image field.',
        'widget_module' => 'filefield',
        'widget_active' => '1',
      ),
    );
    parent::setUp();
  }

}
