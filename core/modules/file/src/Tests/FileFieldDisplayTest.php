<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileFieldDisplayTest.
 */

namespace Drupal\file\Tests;

use Drupal\Core\Field\FieldDefinitionInterface;

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
      'cardinality' => FieldDefinitionInterface::CARDINALITY_UNLIMITED,
    );
    $instance_settings = array(
      'description_field' => '1',
    );
    $widget_settings = array();
    $this->createFileField($field_name, 'node', $type_name, $field_settings, $instance_settings, $widget_settings);

    // Create a new node *without* the file field set, and check that the field
    // is not shown for each node display.
    $node = $this->drupalCreateNode(array('type' => $type_name));
    // Check file_default last as the assertions below assume that this is the
    // case.
    $file_formatters = array('file_table', 'file_url_plain', 'hidden', 'file_default');
    foreach ($file_formatters as $formatter) {
      $edit = array(
        "fields[$field_name][type]" => $formatter,
      );
      $this->drupalPostForm("admin/structure/types/manage/$type_name/display", $edit, t('Save'));
      $this->drupalGet('node/' . $node->id());
      $this->assertNoText($field_name, format_string('Field label is hidden when no file attached for formatter %formatter', array('%formatter' => $formatter)));
    }

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the default formatter is displaying with the file name.
    $node = node_load($nid, TRUE);
    $node_file = file_load($node->{$field_name}->target_id);
    $file_link = array(
      '#theme' => 'file_link',
      '#file' => $node_file,
    );
    $default_output = drupal_render($file_link);
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Turn the "display" option off and check that the file is no longer displayed.
    $edit = array($field_name . '[0][display]' => FALSE);
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));

    $this->assertNoRaw($default_output, 'Field is hidden when "display" option is unchecked.');

    // Add a description and make sure that it is displayed.
    $description = $this->randomName();
    $edit = array(
      $field_name . '[0][description]' => $description,
      $field_name . '[0][display]' => TRUE,
    );
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));
    $this->assertText($description);

    // Test that fields appear as expected after during the preview.
    // Add a second file.
    $name = 'files[' . $field_name . '_1][]';
    $edit[$name] = drupal_realpath($test_file->getFileUri());

    // Uncheck the display checkboxes and go to the preview.
    $edit[$field_name . '[0][display]'] = FALSE;
    $edit[$field_name . '[1][display]'] = FALSE;
    $this->drupalPostForm("node/$nid/edit", $edit, t('Preview'));
    $this->assertRaw($field_name . '[0][display]', 'First file appears as expected.');
    $this->assertRaw($field_name . '[1][display]', 'Second file appears as expected.');
  }
}
