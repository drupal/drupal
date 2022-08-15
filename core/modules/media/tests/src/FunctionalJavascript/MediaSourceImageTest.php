<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\Plugin\media\Source\Image;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the image media source.
 *
 * @group media
 */
class MediaSourceImageTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the image media source.
   */
  public function testMediaImageSource() {
    $media_type_id = 'test_media_image_type';
    $source_field_id = 'field_media_image';
    $provided_fields = [
      Image::METADATA_ATTRIBUTE_WIDTH,
      Image::METADATA_ATTRIBUTE_HEIGHT,
    ];

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->doTestCreateMediaType($media_type_id, 'image', $provided_fields);

    // Create custom fields for the media type to store metadata attributes.
    $fields = [
      'field_string_width' => 'string',
      'field_string_height' => 'string',
    ];
    $this->createMediaTypeFields($fields, $media_type_id);

    // Hide the name field widget to test default name generation.
    $this->hideMediaTypeFieldWidget('name', $media_type_id);

    $this->drupalGet("admin/structure/media/manage/{$media_type_id}");
    $page->selectFieldOption("field_map[" . Image::METADATA_ATTRIBUTE_WIDTH . "]", 'field_string_width');
    $page->selectFieldOption("field_map[" . Image::METADATA_ATTRIBUTE_HEIGHT . "]", 'field_string_height');
    $page->pressButton('Save');

    // Create a media item.
    $this->drupalGet("media/add/{$media_type_id}");
    $page->attachFileToField("files[{$source_field_id}_0]", $this->root . '/core/modules/media/tests/fixtures/example_1.jpeg');
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField("{$source_field_id}[0][alt]", 'Image Alt Text 1');
    $page->pressButton('Save');

    $assert_session->addressEquals('admin/content/media');

    // Get the media entity view URL from the creation message.
    $this->drupalGet($this->assertLinkToCreatedMedia());

    // Assert the image element is present inside the media element and that its
    // src attribute uses the large image style, the label is visually hidden,
    // and there is no link to the image file.
    $label = $assert_session->elementExists('xpath', '//div[contains(@class, "visually-hidden") and text()="Image"]');
    // The field is the parent div of the label.
    $field = $label->getParent();
    $image_element = $field->find('css', 'img');
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $expected_image_src = $file_url_generator->generateString(\Drupal::token()->replace('public://styles/large/public/[date:custom:Y]-[date:custom:m]/example_1.jpeg'));
    $this->assertStringContainsString($expected_image_src, $image_element->getAttribute('src'));
    $assert_session->elementNotExists('css', 'a', $field);

    // Ensure the image has the correct alt attribute.
    $this->assertSame('Image Alt Text 1', $image_element->getAttribute('alt'));

    // Load the media and check that all fields are properly populated.
    $media = Media::load(1);
    $this->assertSame('example_1.jpeg', $media->getName());
    $this->assertSame('200', $media->get('field_string_width')->value);
    $this->assertSame('89', $media->get('field_string_height')->value);

    // Tests the warning when the default display's image style is missing.
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'access media overview',
      'administer media',
      'administer media types',
      'administer media display',
      'view media',
      // We need 'access content' for system.machine_name_transliterate.
      'access content',
    ]));

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // If for some reason a site builder deletes the 'large' image style, do
    // not add an image style to the new entity view display's image field.
    // Instead, add a warning on the 'Status report' page.
    ImageStyle::load('large')->delete();
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', 'Madame Bonacieux');
    $this->assertNotEmpty($assert_session->waitForText('Machine name: madame_bonacieux'));
    $page->selectFieldOption('source', 'image');
    // Wait for the form to complete with AJAX.
    $this->assertNotEmpty($assert_session->waitForText('Field mapping'));
    $page->pressButton('Save');
    $this->assertViewDisplayConfigured('madame_bonacieux');

    // Create user without the 'administer media display' permission.
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'access media overview',
      'administer media',
      'administer media types',
      'view media',
      // We need 'access content' for system.machine_name_transliterate.
      'access content',
    ]));
    // Test that hook_requirements adds warning about the lack of an image
    // style.
    $this->drupalGet('/admin/reports/status');
    // The image style warning should not include an action link when the
    // current user lacks the permission 'administer media display'.
    $assert_session->pageTextContains('The default display for the Madame Bonacieux media type is not currently using an image style on the Image field. Not using an image style can lead to much larger file downloads.');
    $assert_session->linkNotExists('add an image style to the Image field');
    $assert_session->linkByHrefNotExists('/admin/structure/media/manage/madame_bonacieux/display');

    // The image style warning should include an action link when the current
    // user has the permission 'administer media display'.
    Role::load(RoleInterface::AUTHENTICATED_ID)
      ->grantPermission('administer media display')
      ->save();
    $this->drupalGet('/admin/reports/status');
    $assert_session->pageTextContains('The default display for the Madame Bonacieux media type is not currently using an image style on the Image field. Not using an image style can lead to much larger file downloads. If you would like to change this, add an image style to the Image field.');
    $assert_session->linkExists('add an image style to the Image field');
    $assert_session->linkByHrefExists('/admin/structure/media/manage/madame_bonacieux/display');

    // The image style warning should not include an action link when the
    // Field UI module is uninstalled.
    $this->container->get('module_installer')->uninstall(['field_ui']);
    $this->drupalGet('/admin/reports/status');
    $assert_session->pageTextContains('The default display for the Madame Bonacieux media type is not currently using an image style on the Image field. Not using an image style can lead to much larger file downloads.');
    $assert_session->linkNotExists('add an image style to the Image field');
    $assert_session->linkByHrefNotExists('/admin/structure/media/manage/madame_bonacieux/display');
  }

  /**
   * Asserts the proper entity view display components for a media type.
   *
   * @param string $media_type_id
   *   The media type ID.
   *
   * @internal
   */
  protected function assertViewDisplayConfigured(string $media_type_id): void {
    $assert_session = $this->assertSession();
    $type = MediaType::load($media_type_id);
    $display = EntityViewDisplay::load('media.' . $media_type_id . '.' . EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE);
    $this->assertInstanceOf(EntityViewDisplay::class, $display);
    $source_field_definition = $type->getSource()->getSourceFieldDefinition($type);
    $component = $display->getComponent($source_field_definition->getName());
    $this->assertSame('visually_hidden', $component['label']);
    if (ImageStyle::load('large')) {
      $this->assertSame('large', $component['settings']['image_style']);
    }
    else {
      $this->assertEmpty($component['settings']['image_style']);
    }
    $this->assertEmpty($component['settings']['image_link']);

    // Since components that aren't explicitly hidden can show up on the
    // display edit form, check that only the image field appears enabled on
    // the display edit form.
    $this->drupalGet('/admin/structure/media/manage/' . $media_type_id . '/display');
    // Assert that only the source field is enabled.
    $assert_session->elementExists('css', 'input[name="' . $source_field_definition->getName() . '_settings_edit"]');
    $assert_session->elementsCount('css', 'input[name$="_settings_edit"]', 1);
  }

}
