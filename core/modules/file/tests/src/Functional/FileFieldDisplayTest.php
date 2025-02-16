<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the display of file fields in node and views.
 *
 * @group file
 */
class FileFieldDisplayTest extends FileFieldTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests normal formatter display on node display.
   */
  public function testNodeDisplay(): void {
    $field_name = $this->randomMachineName();
    $type_name = 'article';
    $field_storage_settings = [
      'display_field' => '1',
      'display_default' => '1',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $field_settings = [
      'description_field' => '1',
    ];
    $widget_settings = [];
    $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);

    // Create a new node *without* the file field set, and check that the field
    // is not shown for each node display.
    $node = $this->drupalCreateNode(['type' => $type_name]);
    // Check file_default last as the assertions below assume that this is the
    // case.
    $file_formatters = ['file_table', 'file_url_plain', 'hidden', 'file_default'];
    foreach ($file_formatters as $formatter) {
      if ($formatter === 'hidden') {
        $edit = [
          "fields[$field_name][region]" => 'hidden',
        ];
      }
      else {
        $edit = [
          "fields[$field_name][type]" => $formatter,
          "fields[$field_name][region]" => 'content',
        ];
      }
      $this->drupalGet("admin/structure/types/manage/{$type_name}/display");
      $this->submitForm($edit, 'Save');
      $this->drupalGet('node/' . $node->id());
      // Verify that the field label is hidden when no file is attached.
      $this->assertSession()->pageTextNotContains($field_name);
    }

    $this->generateFile('escaped-&-text', 64, 10, 'text');
    $test_file = File::create([
      'uri' => 'public://escaped-&-text.txt',
      'name' => 'escaped-&-text',
      'filesize' => filesize('public://escaped-&-text.txt'),
    ]);

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the default formatter is displaying with the file name.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $file_link = [
      '#theme' => 'file_link',
      '#file' => $node_file,
    ];
    $default_output = \Drupal::service('renderer')->renderRoot($file_link);
    $this->assertSession()->responseContains($default_output);

    // Turn the "display" option off and check that the file is no longer
    // displayed.
    $edit = [$field_name . '[0][display]' => FALSE];
    $this->drupalGet('node/' . $nid . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->responseNotContains($default_output);

    // Add a description and make sure that it is displayed.
    $description = $this->randomMachineName();
    $edit = [
      $field_name . '[0][description]' => $description,
      $field_name . '[0][display]' => TRUE,
    ];
    $this->drupalGet('node/' . $nid . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($description);

    // Ensure the filename in the link's title attribute is escaped.
    $this->assertSession()->responseContains('title="escaped-&amp;-text.txt"');

    // Test that fields appear as expected after during the preview.
    // Add a second file.
    $name = 'files[' . $field_name . '_1][]';
    $edit_upload[$name] = \Drupal::service('file_system')->realpath($test_file->getFileUri());
    $this->drupalGet("node/{$nid}/edit");
    $this->submitForm($edit_upload, 'Upload');

    // Uncheck the display checkboxes and go to the preview.
    $edit[$field_name . '[0][display]'] = FALSE;
    $edit[$field_name . '[1][display]'] = FALSE;
    $this->submitForm($edit, 'Preview');
    $this->clickLink('Back to content editing');
    // First file.
    $this->assertSession()->responseContains($field_name . '[0][display]');
    // Second file.
    $this->assertSession()->responseContains($field_name . '[1][display]');
    $this->assertSession()->responseContains($field_name . '[1][description]');

    // Check that the file fields don't contain duplicate HTML IDs.
    $this->assertSession()->pageContainsNoDuplicateId();
  }

  /**
   * Tests default display of File Field.
   */
  public function testDefaultFileFieldDisplay(): void {
    $field_name = $this->randomMachineName();
    $type_name = 'article';
    $field_storage_settings = [
      'display_field' => '1',
      'display_default' => '0',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $field_settings = [
      'description_field' => '1',
    ];
    $widget_settings = [];
    $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    $this->drupalGet('node/' . $nid . '/edit');
    $this->assertSession()->fieldExists($field_name . '[0][display]');
    $this->assertSession()->checkboxNotChecked($field_name . '[0][display]');
  }

  /**
   * Tests description toggle for field instance configuration.
   */
  public function testDescToggle(): void {
    $type_name = 'test';
    $field_type = 'file';
    $field_name = $this->randomMachineName();
    // Use the UI to add a new content type that also contains a file field.
    $edit = [
      'name' => $type_name,
      'type' => $type_name,
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, 'Save and manage fields');
    $field_edit = [
      'settings[description_field]' => TRUE,
    ];
    $this->fieldUIAddNewField('/admin/structure/types/manage/' . $type_name, $field_name, $this->randomString(), $field_type, [], $field_edit);
    // Add a node of our new type and upload a file to it.
    $file = current($this->drupalGetTestFiles('text'));
    $title = $this->randomString();
    $edit = [
      'title[0][value]' => $title,
      'files[field_' . $field_name . '_0]' => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalGet('node/add/' . $type_name);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains('The description may be used as the label of the link to the file.');
  }

  /**
   * Tests description display of File Field.
   */
  public function testDescriptionDefaultFileFieldDisplay(): void {
    $field_name = $this->randomMachineName();
    $type_name = 'article';
    $field_storage_settings = [
      'display_field' => '1',
      'display_default' => '1',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $field_settings = [
      'description_field' => '1',
    ];
    $widget_settings = [];
    $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Add file description.
    $description = 'This is the test file description';
    $this->drupalGet("node/{$nid}/edit");
    $this->submitForm([
      $field_name . '[0][description]' => $description,
    ], 'Save');

    // Load uncached node.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
    $node = Node::load($nid);

    // Test default formatter.
    $this->drupalGet('node/' . $nid);
    $this->assertSession()->elementTextContains('xpath', '//a[@href="' . $node->{$field_name}->entity->createFileUrl() . '"]', $description);

    // Change formatter to "Table of files".
    $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $type_name . '.default');
    $display->setComponent($field_name, [
      'label' => 'hidden',
      'type' => 'file_table',
    ])->save();

    $this->drupalGet('node/' . $nid);
    $this->assertSession()->elementTextContains('xpath', '//a[@href="' . $node->{$field_name}->entity->createFileUrl() . '"]', $description);

    // Test that null file size is rendered as "Unknown".
    $nonexistent_file = File::create([
      'uri' => 'temporary://' . $this->randomMachineName() . '.txt',
    ]);
    $nonexistent_file->save();
    $node->set($field_name, $nonexistent_file->id());
    $node->save();
    $this->drupalGet('node/' . $nid);
    $this->assertSession()->elementTextEquals('xpath', '//a[@href="' . $node->{$field_name}->entity->createFileUrl() . '"]/../../../td[2]', 'Unknown');
  }

}
