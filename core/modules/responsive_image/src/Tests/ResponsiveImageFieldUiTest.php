<?php

namespace Drupal\responsive_image\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Tests the "Responsive Image" formatter settings form.
 *
 * @group responsive_image
 */
class ResponsiveImageFieldUiTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'image', 'responsive_image', 'responsive_image_test_module', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    // Create a test user.
    $admin_user = $this->drupalCreateUser(['access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'bypass node access']);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();
  }

  /**
   * Tests formatter settings.
   */
  public function testResponsiveImageFormatterUI() {
    $manage_fields = 'admin/structure/types/manage/' . $this->type;
    $manage_display = $manage_fields . '/display';

    // Create a field, and a node with some data for the field.
    $this->fieldUIAddNewField($manage_fields, 'image', 'Image field', 'image');
    // Display the "Manage display".
    $this->drupalGet($manage_display);

    // Change the formatter and check that the summary is updated.
    $edit = [
      'fields[field_image][type]' => 'responsive_image',
      'fields[field_image][region]' => 'content',
      'refresh_rows' => 'field_image',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Refresh')]);
    $this->assertText("Select a responsive image style.", 'The expected summary is displayed.');

    // Submit the form.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText("Select a responsive image style.", 'The expected summary is displayed.');

    // Create responsive image styles.
    $responsive_image_style = ResponsiveImageStyle::create([
      'id' => 'style_one',
      'label' => 'Style One',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'thumbnail',
    ]);
    $responsive_image_style
      ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'thumbnail',
      ])
      ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'medium',
      ])
      // Test the normal output of mapping to an image style.
      ->addImageStyleMapping('responsive_image_test_module.wide', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'large',
      ])
      ->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    // Refresh the page.
    $this->drupalGet($manage_display);
    $this->assertText("Select a responsive image style.", 'The expected summary is displayed.');

    // Click on the formatter settings button to open the formatter settings
    // form.
    $this->drupalPostAjaxForm(NULL, [], "field_image_settings_edit");

    // Assert that the correct fields are present.
    $fieldnames = [
      'fields[field_image][settings_edit_form][settings][responsive_image_style]',
      'fields[field_image][settings_edit_form][settings][image_link]',
    ];
    foreach ($fieldnames as $fieldname) {
      $this->assertField($fieldname);
    }
    $edit = [
      'fields[field_image][settings_edit_form][settings][responsive_image_style]' => 'style_one',
      'fields[field_image][settings_edit_form][settings][image_link]' => 'content',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, "field_image_plugin_settings_update");

    // Save the form to save the settings.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Responsive image style: Style One');
    $this->assertText('Linked to content');

    // Click on the formatter settings button to open the formatter settings
    // form.
    $this->drupalPostAjaxForm(NULL, [], "field_image_settings_edit");
    $edit = [
      'fields[field_image][settings_edit_form][settings][responsive_image_style]' => 'style_one',
      'fields[field_image][settings_edit_form][settings][image_link]' => 'file',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, "field_image_plugin_settings_update");

    // Save the form to save the third party settings.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Responsive image style: Style One');
    $this->assertText('Linked to file');
  }

}
