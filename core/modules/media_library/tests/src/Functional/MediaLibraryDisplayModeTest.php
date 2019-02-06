<?php

namespace Drupal\Tests\media_library\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Plugin\media\Source\File;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\media\Entity\MediaType;

/**
 * Tests that the Media library automatically configures form/view modes.
 *
 * @group media_library
 */
class MediaLibraryDisplayModeTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'media',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'access media overview',
      'administer media',
      'administer media fields',
      'administer media form display',
      'administer media display',
      'administer media types',
      'view media',
    ]));
  }

  /**
   * Tests that the Media library can automatically configure display modes.
   */
  public function testDisplayModes() {
    $this->createMediaType('file', [
      'id' => 'type_one',
    ]);
    $this->createMediaType('file', [
      'id' => 'type_two',
      'field_map' => ['name' => File::METADATA_ATTRIBUTE_NAME],
    ]);
    $this->createMediaType('image', [
      'id' => 'type_three',
    ]);
    $this->createMediaType('image', [
      'id' => 'type_four',
      'field_map' => ['name' => Image::METADATA_ATTRIBUTE_NAME],
    ]);

    // Display modes are not automatically created when creating a media type
    // programmatically, only when installing the module or when creating a
    // media type via the UI.
    $this->assertNull(EntityFormDisplay::load('media.type_one.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.type_one.media_library'));
    $this->assertNull(EntityFormDisplay::load('media.type_two.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.type_two.media_library'));
    $this->assertNull(EntityFormDisplay::load('media.type_three.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.type_three.media_library'));
    $this->assertNull(EntityFormDisplay::load('media.type_four.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.type_four.media_library'));

    // Display modes are created on install.
    $this->container->get('module_installer')->install(['media_library']);

    // For a non-image media type without a mapped name field, the media_library
    // form mode should only contain the name field.
    $this->assertFormDisplay('type_one', TRUE, FALSE);
    $this->assertViewDisplay('type_one', 'medium');

    // For a non-image media type with a mapped name field, the media_library
    // form mode should not contain any fields.
    $this->assertFormDisplay('type_two', FALSE, FALSE);
    $this->assertViewDisplay('type_two', 'medium');

    // For an image media type without a mapped name field, the media_library
    // form mode should contain the name field and the source field.
    $this->assertFormDisplay('type_three', TRUE, TRUE);
    $this->assertViewDisplay('type_three', 'medium');

    // For an image media type with a mapped name field, the media_library form
    // mode should only contain the source field.
    $this->assertFormDisplay('type_four', FALSE, TRUE);
    $this->assertViewDisplay('type_four', 'medium');

    // Create a non-image media type without a mapped name field in the UI.
    $type_five_id = 'type_five';
    $edit = [
      'label' => $type_five_id,
      'id' => $type_five_id,
      'source' => 'file',
    ];
    $this->drupalPostForm('admin/structure/media/add', $edit, 'Save');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->pageTextContains("Media Library form and view displays have been created for the $type_five_id media type.");
    $this->assertFormDisplay($type_five_id, TRUE, FALSE);
    $this->assertViewDisplay($type_five_id, 'medium');

    // Create a non-image media type with a mapped name field in the UI.
    $type_six_id = 'type_six';
    $edit = [
      'label' => $type_six_id,
      'id' => $type_six_id,
      'source' => 'file',
    ];
    $this->drupalPostForm('admin/structure/media/add', $edit, 'Save');
    $edit = [
      'field_map[name]' => File::METADATA_ATTRIBUTE_NAME,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains("Media Library form and view displays have been created for the $type_six_id media type.");
    $this->assertFormDisplay($type_six_id, FALSE, FALSE);
    $this->assertViewDisplay($type_six_id, 'medium');

    // Create an image media type without a mapped name field in the UI.
    $type_seven_id = 'type_seven';
    $edit = [
      'label' => $type_seven_id,
      'id' => $type_seven_id,
      'source' => 'image',
    ];
    $this->drupalPostForm('admin/structure/media/add', $edit, 'Save');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->pageTextContains("Media Library form and view displays have been created for the $type_seven_id media type.");
    $this->assertFormDisplay($type_seven_id, TRUE, TRUE);
    $this->assertViewDisplay($type_seven_id, 'medium');

    // Create an image media type with a mapped name field in the UI.
    $type_eight_id = 'type_eight';
    $edit = [
      'label' => $type_eight_id,
      'id' => $type_eight_id,
      'source' => 'image',
    ];
    $this->drupalPostForm('admin/structure/media/add', $edit, 'Save');
    $edit = [
      'field_map[name]' => Image::METADATA_ATTRIBUTE_NAME,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains("Media Library form and view displays have been created for the $type_eight_id media type.");
    $this->assertFormDisplay($type_eight_id, FALSE, TRUE);
    $this->assertViewDisplay($type_eight_id, 'medium');

    // Delete a form and view display.
    EntityFormDisplay::load('media.type_one.media_library')->delete();
    EntityViewDisplay::load('media.type_one.media_library')->delete();
    // Make sure the form and view display are not created when saving existing
    // media types.
    $this->drupalPostForm('admin/structure/media/manage/type_one', [], 'Save');
    $this->assertNull(EntityFormDisplay::load('media.type_one.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.type_one.media_library'));

    // Delete the medium image style.
    ImageStyle::load('medium')->delete();
    // Create an image media type, assert the displays are created and the
    // fallback 'media_library' image style is used.
    $type_nine_id = 'type_nine';
    $edit = [
      'label' => $type_nine_id,
      'id' => $type_nine_id,
      'source' => 'image',
    ];
    $this->drupalPostForm('admin/structure/media/add', $edit, 'Save');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->pageTextContains("Media Library form and view displays have been created for the $type_nine_id media type.");
    $this->assertFormDisplay($type_nine_id, TRUE, TRUE);
    $this->assertViewDisplay($type_nine_id, 'media_library');
  }

  /**
   * Asserts the media library form display components for a media type.
   *
   * @param string $type_id
   *   The media type ID.
   * @param bool $has_name
   *   Whether the media library form display should contain the name field or
   *   not.
   * @param bool $has_source_field
   *   Whether the media library form display should contain the source field or
   *   not.
   */
  protected function assertFormDisplay($type_id, $has_name, $has_source_field) {
    // These components are added by default and invisible.
    $components = [
      'revision_log_message',
      'langcode',
    ];

    // Only assert the name and source field if needed.
    if ($has_name) {
      $components[] = 'name';
    }
    if ($has_source_field) {
      $type = MediaType::load($type_id);
      $components[] = $type->getSource()->getSourceFieldDefinition($type)->getName();
    }

    $form_display = EntityFormDisplay::load('media.' . $type_id . '.media_library');
    $this->assertInstanceOf(EntityFormDisplay::class, $form_display);
    $actual_components = array_keys($form_display->getComponents());
    sort($components);
    sort($actual_components);
    $this->assertSame($components, $actual_components);
  }

  /**
   * Asserts the media library view display components for a media type.
   *
   * @param string $type_id
   *   The media type ID.
   * @param string $image_style
   *   The ID of the image style that should be configured for the thumbnail.
   */
  protected function assertViewDisplay($type_id, $image_style) {
    $view_display = EntityViewDisplay::load('media.' . $type_id . '.media_library');
    $this->assertInstanceOf(EntityViewDisplay::class, $view_display);
    // Assert the media library view display contains only the thumbnail.
    $this->assertSame(['thumbnail'], array_keys($view_display->getComponents()));
    // Assert the thumbnail image style.
    $thumbnail = $view_display->getComponent('thumbnail');
    $this->assertInternalType('array', $thumbnail);
    $this->assertSame($image_style, $thumbnail['settings']['image_style']);
  }

}
