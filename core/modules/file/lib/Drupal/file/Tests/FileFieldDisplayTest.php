<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileFieldDisplayTest.
 */

namespace Drupal\file\Tests;

/**
 * Tests that formatters are working properly.
 */
class FileFieldDisplayTest extends FileFieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File field display tests',
      'description' => 'Test the display of file fields in node and views.',
      'group' => 'File',
    );
  }

  /**
   * Tests normal formatter display on node display.
   */
  function testNodeDisplay() {
    $field_name = strtolower($this->randomName());
    $type_name = 'article';
    $field_settings = array(
      'display_field' => '1',
      'display_default' => '1',
    );
    $instance_settings = array(
      'description_field' => '1',
    );
    $widget_settings = array();
    $this->createFileField($field_name, $type_name, $field_settings, $instance_settings, $widget_settings);
    $field = field_info_field($field_name);
    $instance = field_info_instance('node', $field_name, $type_name);

    // Create a new node *without* the file field set, and check that the field
    // is not shown for each node display.
    $node = $this->drupalCreateNode(array('type' => $type_name));
    $file_formatters = array('file_default', 'file_table', 'file_url_plain', 'hidden');
    foreach ($file_formatters as $formatter) {
      $edit = array(
        "fields[$field_name][type]" => $formatter,
      );
      $this->drupalPost("admin/structure/types/manage/$type_name/display", $edit, t('Save'));
      $this->drupalGet('node/' . $node->nid);
      $this->assertNoText($field_name, t('Field label is hidden when no file attached for formatter %formatter', array('%formatter' => $formatter)));
    }

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->drupalGet('node/' . $nid . '/edit');

    // Check that the default formatter is displaying with the file name.
    $node = node_load($nid, NULL, TRUE);
    $node_file = (object) $node->{$field_name}[LANGUAGE_NOT_SPECIFIED][0];
    $default_output = theme('file_link', array('file' => $node_file));
    $this->assertRaw($default_output, t('Default formatter displaying correctly on full node view.'));

    // Turn the "display" option off and check that the file is no longer displayed.
    $edit = array($field_name . '[' . LANGUAGE_NOT_SPECIFIED . '][0][display]' => FALSE);
    $this->drupalPost('node/' . $nid . '/edit', $edit, t('Save'));

    $this->assertNoRaw($default_output, t('Field is hidden when "display" option is unchecked.'));

  }
}
