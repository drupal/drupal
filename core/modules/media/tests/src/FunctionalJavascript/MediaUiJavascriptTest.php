<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\field\FieldConfigInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaSourceInterface;

/**
 * Ensures that media UI works correctly.
 *
 * @group media
 */
class MediaUiJavascriptTest extends MediaJavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $testMediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests a media type administration.
   */
  public function testMediaTypes() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/media');
    $assert_session->pageTextContains('No media types available. Add media type.');
    $assert_session->linkExists('Add media type');

    // Test the creation of a media type using the UI.
    $name = $this->randomMachineName();
    $description = $this->randomMachineName();
    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $name);
    $machine_name = strtolower($name);
    $this->assertJsCondition("jQuery('.machine-name-value').html() == '$machine_name'");
    $page->selectFieldOption('source', 'test');
    $this->assertJsCondition("jQuery('.form-item-source-configuration-test-config-value').length > 0");
    $page->fillField('description', $description);
    $page->pressButton('Save');
    // The wait prevents intermittent test failures.
    $result = $assert_session->waitForLink('Add media type');
    $this->assertNotEmpty($result);
    $assert_session->addressEquals('admin/structure/media');
    $assert_session->pageTextContains('The media type ' . $name . ' has been added.');
    $this->drupalGet('admin/structure/media');
    $assert_session->pageTextContains($name);
    $assert_session->pageTextContains($description);

    // We need to clear the statically cached field definitions to account for
    // fields that have been created by API calls in this test, since they exist
    // in a separate memory space from the web server.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
    // Assert that the field and field storage were created.
    $media_type = MediaType::load($machine_name);
    $source = $media_type->getSource();
    /** @var \Drupal\field\FieldConfigInterface $source_field */
    $source_field = $source->getSourceFieldDefinition($media_type);
    $this->assertInstanceOf(FieldConfigInterface::class, $source_field);
    $this->assertFalse($source_field->isNew(), 'Source field was saved.');
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $source_field->getFieldStorageDefinition();
    $this->assertFalse($storage->isNew(), 'Source field storage definition was saved.');
    $this->assertFalse($storage->isLocked(), 'Source field storage definition was not locked.');

    /** @var \Drupal\media\MediaTypeInterface $media_type_storage */
    $media_type_storage = $this->container->get('entity_type.manager')->getStorage('media_type');
    $this->testMediaType = $media_type_storage->load(strtolower($name));

    // Check if all action links exist.
    $assert_session->linkByHrefExists('admin/structure/media/add');
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testMediaType->id());
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testMediaType->id() . '/fields');
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testMediaType->id() . '/form-display');
    $assert_session->linkByHrefExists('admin/structure/media/manage/' . $this->testMediaType->id() . '/display');

    // Assert that fields have expected values before editing.
    $page->clickLink('Edit');
    $assert_session->fieldValueEquals('label', $name);
    $assert_session->fieldValueEquals('description', $description);
    $assert_session->fieldValueEquals('source', 'test');
    $assert_session->fieldValueEquals('label', $name);
    $assert_session->checkboxNotChecked('edit-options-new-revision');
    $assert_session->checkboxChecked('edit-options-status');
    $assert_session->checkboxNotChecked('edit-options-queue-thumbnail-downloads');
    $assert_session->pageTextContains('Create new revision');
    $assert_session->pageTextContains('Automatically create new revisions. Users with the "Administer media" permission will be able to override this option.');
    $assert_session->pageTextContains('Download thumbnails via a queue.');
    $assert_session->pageTextContains('Media will be automatically published when created.');
    $assert_session->pageTextContains('Media sources can provide metadata fields such as title, caption, size information, credits, etc. Media can automatically save this metadata information to entity fields, which can be configured below. Information will only be mapped if the entity field is empty.');

    // Try to change media type and check if new configuration sub-form appears.
    $page->selectFieldOption('source', 'test');
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);
    $assert_session->fieldExists('Test config value');
    $assert_session->fieldValueEquals('Test config value', 'This is default value.');
    $assert_session->fieldExists('Attribute 1');
    $assert_session->fieldExists('Attribute 2');

    // Test if the edit machine name is not editable.
    $assert_session->fieldDisabled('Machine-readable name');

    // Edit and save media type form fields with new values.
    $new_name = $this->randomMachineName();
    $new_description = $this->randomMachineName();
    $page->fillField('label', $new_name);
    $page->fillField('description', $new_description);
    $page->selectFieldOption('source', 'test');
    $page->fillField('Test config value', 'This is new config value.');
    $page->selectFieldOption('field_map[attribute_1]', 'name');
    $page->checkField('options[new_revision]');
    $page->uncheckField('options[status]');
    $page->checkField('options[queue_thumbnail_downloads]');
    $page->pressButton('Save');
    // The wait prevents intermittent test failures.
    $result = $assert_session->waitForLink('Add media type');
    $this->assertNotEmpty($result);
    $assert_session->addressEquals('admin/structure/media');
    $assert_session->pageTextContains("The media type $new_name has been updated.");

    // Test if edit worked and if new field values have been saved as expected.
    $this->drupalGet('admin/structure/media/manage/' . $this->testMediaType->id());
    $assert_session->fieldValueEquals('label', $new_name);
    $assert_session->fieldValueEquals('description', $new_description);
    $assert_session->fieldValueEquals('source', 'test');
    $assert_session->checkboxChecked('options[new_revision]');
    $assert_session->checkboxNotChecked('options[status]');
    $assert_session->checkboxChecked('options[queue_thumbnail_downloads]');
    $assert_session->fieldValueEquals('Test config value', 'This is new config value.');
    $assert_session->fieldValueEquals('Attribute 1', 'name');
    $assert_session->fieldValueEquals('Attribute 2', MediaSourceInterface::METADATA_FIELD_EMPTY);

    /** @var \Drupal\media\MediaTypeInterface $loaded_media_type */
    $loaded_media_type = $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->load($this->testMediaType->id());
    $this->assertSame($loaded_media_type->id(), $this->testMediaType->id());
    $this->assertSame($loaded_media_type->label(), $new_name);
    $this->assertSame($loaded_media_type->getDescription(), $new_description);
    $this->assertSame($loaded_media_type->getSource()->getPluginId(), 'test');
    $this->assertSame($loaded_media_type->getSource()->getConfiguration()['test_config_value'], 'This is new config value.');
    $this->assertTrue($loaded_media_type->shouldCreateNewRevision());
    $this->assertTrue($loaded_media_type->thumbnailDownloadsAreQueued());
    $this->assertFalse($loaded_media_type->getStatus());
    $this->assertSame($loaded_media_type->getFieldMap(), ['attribute_1' => 'name']);

    // We need to clear the statically cached field definitions to account for
    // fields that have been created by API calls in this test, since they exist
    // in a separate memory space from the web server.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Test that a media item being created with default status to "FALSE",
    // will be created unpublished.
    /** @var \Drupal\media\MediaInterface $unpublished_media */
    $unpublished_media = Media::create(['name' => 'unpublished test media', 'bundle' => $loaded_media_type->id()]);
    $this->assertFalse($unpublished_media->isPublished());
    $unpublished_media->delete();

    // Tests media type delete form.
    $page->clickLink('Delete');
    $assert_session->addressEquals('admin/structure/media/manage/' . $this->testMediaType->id() . '/delete');
    $page->pressButton('Delete');
    $assert_session->addressEquals('admin/structure/media');
    $assert_session->pageTextContains('The media type ' . $new_name . ' has been deleted.');

    // Test that the system for preventing the deletion of media types works
    // (they cannot be deleted if there is media content of that type/bundle).
    $media_type2 = $this->createMediaType('test');
    $label2 = $media_type2->label();
    $media = Media::create(['name' => 'lorem ipsum', 'bundle' => $media_type2->id()]);
    $media->save();
    $this->drupalGet('admin/structure/media/manage/' . $media_type2->id());
    $page->clickLink('Delete');
    $assert_session->addressEquals('admin/structure/media/manage/' . $media_type2->id() . '/delete');
    $assert_session->buttonNotExists('edit-submit');
    $assert_session->pageTextContains("$label2 is used by 1 media item on your site. You can not remove this media type until you have removed all of the $label2 media items.");
  }

}
