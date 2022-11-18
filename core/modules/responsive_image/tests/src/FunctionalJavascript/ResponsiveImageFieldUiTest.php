<?php

namespace Drupal\Tests\responsive_image\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Tests the responsive image field UI.
 *
 * @group responsive_image
 */
class ResponsiveImageFieldUiTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'image',
    'responsive_image',
    'responsive_image_test_module',
    'block',
  ];

  /**
   * The content type id.
   *
   * @var string
   */
  protected string $type;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $this->type = $type->id();
  }

  /**
   * Tests formatter settings.
   */
  public function testResponsiveImageFormatterUi() {
    $manage = 'admin/structure/types/manage/' . $this->type;
    $add_field = $manage . '/fields/add-field';
    $manage_display = $manage . '/display';
    $assert_session = $this->assertSession();

    // Create a field, and a node with some data for the field.
    // Create the field.
    $this->drupalGet($add_field);

    $page = $this->getSession()->getPage();
    $storage_type = $page->findField('edit-new-storage-type');
    $storage_type->setValue('image');

    // Set the label.
    $label = $page->findField('edit-label');
    $label->setValue('Image');

    // Wait for the machine name.
    $assert_session->waitForElementVisible('css', '[name="label"] + * .machine-name-value');

    // Save the current page.
    $save_button = $page->findButton('Save and continue');
    $save_button->click();

    // Display the "Manage display" page.
    $this->drupalGet($manage_display);

    // Change the formatter and check that the summary is updated.
    $page = $this->getSession()->getPage();

    $field_image_type = $page->findField('fields[field_image][type]');
    $field_image_type->setValue('responsive_image');

    $summary_text = $assert_session->waitForElement('xpath', $this->cssSelectToXpath('#field-image .ajax-new-content .field-plugin-summary'));
    $this->assertEquals('Select a responsive image style. Loading attribute: lazy', $summary_text->getText());

    $page->pressButton('Save');
    $assert_session->responseContains("Select a responsive image style.");

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
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    // Refresh the page.
    $this->drupalGet($manage_display);
    $assert_session->responseContains("Select a responsive image style.");

    // Click on the formatter settings button to open the formatter settings
    // form.
    $field_image_type = $page->findField('fields[field_image][type]');
    $field_image_type->setValue('responsive_image');

    $page->find('css', '#edit-fields-field-image-settings-edit')->click();
    $assert_session->waitForField('fields[field_image][settings_edit_form][settings][responsive_image_style]');

    // Assert that the correct fields are present.
    $fieldnames = [
      'fields[field_image][settings_edit_form][settings][responsive_image_style]',
      'fields[field_image][settings_edit_form][settings][image_link]',
    ];
    foreach ($fieldnames as $fieldname) {
      $assert_session->fieldExists($fieldname);
    }
    $page->findField('fields[field_image][settings_edit_form][settings][responsive_image_style]')->setValue('style_one');
    $page->findField('fields[field_image][settings_edit_form][settings][image_link]')->setValue('content');
    // Save the form to save the settings.
    $page->pressButton('Save');

    $assert_session->responseContains('Responsive image style: Style One');
    $assert_session->responseContains('Linked to content');

    $page->find('css', '#edit-fields-field-image-settings-edit')->click();
    $assert_session->waitForField('fields[field_image][settings_edit_form][settings][responsive_image_style]');
    $page->findField('fields[field_image][settings_edit_form][settings][image_link]')->setValue('file');

    // Save the form to save the settings.
    $page->pressButton('Save');

    $assert_session->responseContains('Responsive image style: Style One');
    $assert_session->responseContains('Linked to file');
  }

}
