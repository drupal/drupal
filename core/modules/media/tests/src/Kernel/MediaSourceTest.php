<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;

/**
 * Tests media source plugins related logic.
 *
 * @group media
 */
class MediaSourceTest extends MediaKernelTestBase {

  /**
   * Tests that metadata is correctly mapped irrespective of how media is saved.
   */
  public function testSave() {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => 'field_to_map_to',
      'type' => 'string',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->testMediaType->id(),
      'label' => 'Field to map to',
    ])->save();

    // Set an arbitrary metadata value to be mapped.
    $this->container->get('state')
      ->set('media_source_test_attributes', [
        'attribute_to_map' => [
          'title' => 'Attribute to map',
          'value' => 'Snowball',
        ],
        'thumbnail_uri' => [
          'value' => 'public://TheSisko.png',
        ],
      ]);
    $this->testMediaType->setFieldMap([
      'attribute_to_map' => 'field_to_map_to',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('media');

    /** @var \Drupal\media\MediaInterface $a */
    $a = $storage->create([
      'bundle' => $this->testMediaType->id(),
    ]);
    /** @var \Drupal\media\MediaInterface $b */
    $b = $storage->create([
      'bundle' => $this->testMediaType->id(),
    ]);

    // Set a random source value on both items.
    $a->set($a->getSource()->getSourceFieldDefinition($a->bundle->entity)->getName(), $this->randomString());
    $b->set($b->getSource()->getSourceFieldDefinition($b->bundle->entity)->getName(), $this->randomString());

    $a->save();
    $storage->save($b);

    // Assert that the default name was mapped into the name field for both
    // media items.
    $this->assertFalse($a->get('name')->isEmpty());
    $this->assertFalse($b->get('name')->isEmpty());

    // Assert that arbitrary metadata was mapped correctly.
    $this->assertFalse($a->get('field_to_map_to')->isEmpty());
    $this->assertFalse($b->get('field_to_map_to')->isEmpty());

    // Assert that the thumbnail was mapped correctly from the source.
    $this->assertSame('public://TheSisko.png', $a->thumbnail->entity->getFileUri());
    $this->assertSame('public://TheSisko.png', $b->thumbnail->entity->getFileUri());
  }

  /**
   * Tests default media name functionality.
   */
  public function testDefaultName() {
    // Make sure that the default name is set if not provided by the user.
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create(['bundle' => $this->testMediaType->id()]);
    $media_source = $media->getSource();
    $this->assertSame('default_name', $media_source->getPluginDefinition()['default_name_metadata_attribute'], 'Default metadata attribute is not used for the default name.');
    $this->assertSame('media:' . $media->bundle() . ':' . $media->uuid(), $media_source->getMetadata($media, 'default_name'), 'Value of the default name metadata attribute does not look correct.');
    $this->assertSame('media:' . $media->bundle() . ':' . $media->uuid(), $media->getName(), 'Default name was not used correctly by getName().');
    $this->assertSame($media->getName(), $media->label(), 'Default name and label are not the same.');
    $media->save();
    $this->assertSame('media:' . $media->bundle() . ':' . $media->uuid(), $media->getName(), 'Default name was not saved correctly.');
    $this->assertSame($media->getName(), $media->label(), 'The label changed during save.');

    // Make sure that the user-supplied name is used.
    /** @var \Drupal\media\MediaInterface $media */
    $name = 'User-supplied name';
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => $name,
    ]);
    $media_source = $media->getSource();
    $this->assertSame('default_name', $media_source->getPluginDefinition()['default_name_metadata_attribute'], 'Default metadata attribute is not used for the default name.');
    $this->assertSame('media:' . $media->bundle() . ':' . $media->uuid(), $media_source->getMetadata($media, 'default_name'), 'Value of the default name metadata attribute does not look correct.');
    $media->save();
    $this->assertSame($name, $media->getName(), 'User-supplied name was not set correctly.');
    $this->assertSame($media->getName(), $media->label(), 'The user-supplied name does not match the label.');

    // Change the default name attribute and see if it is used to set the name.
    $name = 'Old Major';
    \Drupal::state()->set('media_source_test_attributes', ['alternative_name' => ['title' => 'Alternative name', 'value' => $name]]);
    \Drupal::state()->set('media_source_test_definition', ['default_name_metadata_attribute' => 'alternative_name']);
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create(['bundle' => $this->testMediaType->id()]);
    $media_source = $media->getSource();
    $this->assertSame('alternative_name', $media_source->getPluginDefinition()['default_name_metadata_attribute'], 'Correct metadata attribute is not used for the default name.');
    $this->assertSame($name, $media_source->getMetadata($media, 'alternative_name'), 'Value of the default name metadata attribute does not look correct.');
    $media->save();
    $this->assertSame($name, $media->getName(), 'Default name was not set correctly.');
    $this->assertSame($media->getName(), $media->label(), 'The default name does not match the label.');
  }

  /**
   * Tests metadata mapping functionality.
   */
  public function testMetadataMapping() {
    $field_name = 'field_to_map_to';
    $attribute_name = 'attribute_to_map';
    $storage = FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => $field_name,
      'type' => 'string',
    ]);
    $storage->save();

    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => $this->testMediaType->id(),
      'label' => 'Field to map to',
    ])->save();

    // Save the entity without defining the metadata mapping and check that the
    // field stays empty.
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'field_media_test' => 'some_value',
    ]);
    $media->save();
    $this->assertEmpty($media->get($field_name)->value, 'Field stayed empty.');

    // Make sure that source plugin returns NULL for non-existing fields.
    $this->testMediaType->setFieldMap(['not_here_at_all' => $field_name])->save();
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'field_media_test' => 'some_value',
    ]);
    $media_source = $media->getSource();
    $this->assertNull($media_source->getMetadata($media, 'not_here_at_all'), 'NULL is not returned if asking for a value of non-existing metadata.');
    $media->save();
    $this->assertTrue($media->get($field_name)->isEmpty(), 'Non-existing metadata attribute was wrongly mapped to the field.');

    // Define mapping and make sure that the value was stored in the field.
    \Drupal::state()->set('media_source_test_attributes', [
      $attribute_name => ['title' => 'Attribute to map', 'value' => 'Snowball'],
    ]);
    $this->testMediaType->setFieldMap([$attribute_name => $field_name])->save();
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'field_media_test' => 'some_value',
    ]);
    $media_source = $media->getSource();
    $this->assertSame('Snowball', $media_source->getMetadata($media, $attribute_name), 'Value of the metadata attribute is not correct.');
    $media->save();
    $this->assertSame('Snowball', $media->get($field_name)->value, 'Metadata attribute was not mapped to the field.');

    // Change the metadata attribute value and re-save the entity. Field value
    // should stay the same.
    \Drupal::state()->set('media_source_test_attributes', [
      $attribute_name => ['title' => 'Attribute to map', 'value' => 'Pinkeye'],
    ]);
    $this->assertSame('Pinkeye', $media_source->getMetadata($media, $attribute_name), 'Value of the metadata attribute is not correct.');
    $media->save();
    $this->assertSame('Snowball', $media->get($field_name)->value, 'Metadata attribute was not mapped to the field.');

    // Now change the value of the source field and make sure that the mapped
    // values update too.
    $this->assertSame('Pinkeye', $media_source->getMetadata($media, $attribute_name), 'Value of the metadata attribute is not correct.');
    $media->set('field_media_test', 'some_new_value');
    $media->save();
    $this->assertSame('Pinkeye', $media->get($field_name)->value, 'Metadata attribute was not mapped to the field.');

    // Remove the value of the mapped field and make sure that it is re-mapped
    // on save.
    \Drupal::state()->set('media_source_test_attributes', [
      $attribute_name => ['title' => 'Attribute to map', 'value' => 'Snowball'],
    ]);
    $media->{$field_name}->value = NULL;
    $this->assertSame('Snowball', $media_source->getMetadata($media, $attribute_name), 'Value of the metadata attribute is not correct.');
    $media->save();
    $this->assertSame('Snowball', $media->get($field_name)->value, 'Metadata attribute was not mapped to the field.');
  }

  /**
   * Tests the getSourceFieldValue() method.
   */
  public function testGetSourceFieldValue() {
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'field_media_test' => 'some_value',
    ]);
    $media->save();
    $media_source = $media->getSource();
    $this->assertSame('some_value', $media_source->getSourceFieldValue($media));

    // Test that NULL is returned if there is no value in the source field.
    $media->set('field_media_test', NULL)->save();
    $this->assertNull($media_source->getSourceFieldValue($media));
  }

  /**
   * Tests the thumbnail functionality.
   */
  public function testThumbnail() {
    file_put_contents('public://thumbnail1.jpg', '');
    file_put_contents('public://thumbnail2.jpg', '');

    // Save a media item and make sure thumbnail was added.
    \Drupal::state()->set('media_source_test_attributes', [
      'thumbnail_uri' => ['value' => 'public://thumbnail1.jpg'],
    ]);
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Mr. Jones',
      'field_media_test' => 'some_value',
    ]);
    $media_source = $media->getSource();
    $this->assertSame('public://thumbnail1.jpg', $media_source->getMetadata($media, 'thumbnail_uri'), 'Value of the thumbnail metadata attribute is not correct.');
    $media->save();
    $this->assertSame('public://thumbnail1.jpg', $media->thumbnail->entity->getFileUri(), 'Thumbnail was not added to the media item.');
    // We expect the title not to be present on the Thumbnail.
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Now change the metadata attribute and make sure that the thumbnail stays
    // the same.
    \Drupal::state()->set('media_source_test_attributes', [
      'thumbnail_uri' => ['value' => 'public://thumbnail2.jpg'],
    ]);
    $this->assertSame('public://thumbnail2.jpg', $media_source->getMetadata($media, 'thumbnail_uri'), 'Value of the thumbnail metadata attribute is not correct.');
    $media->save();
    $this->assertSame('public://thumbnail1.jpg', $media->thumbnail->entity->getFileUri(), 'Thumbnail was not preserved.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Remove the thumbnail and make sure that it is auto-updated on save.
    $media->thumbnail->target_id = NULL;
    $this->assertSame('public://thumbnail2.jpg', $media_source->getMetadata($media, 'thumbnail_uri'), 'Value of the thumbnail metadata attribute is not correct.');
    $media->save();
    $this->assertSame('public://thumbnail2.jpg', $media->thumbnail->entity->getFileUri(), 'New thumbnail was not added to the media item.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Change the metadata attribute again, change the source field value too
    // and make sure that the thumbnail updates.
    \Drupal::state()->set('media_source_test_attributes', [
      'thumbnail_uri' => ['value' => 'public://thumbnail1.jpg'],
    ]);
    $media->field_media_test->value = 'some_new_value';
    $this->assertSame('public://thumbnail1.jpg', $media_source->getMetadata($media, 'thumbnail_uri'), 'Value of the thumbnail metadata attribute is not correct.');
    $media->save();
    $this->assertSame('public://thumbnail1.jpg', $media->thumbnail->entity->getFileUri(), 'New thumbnail was not added to the media item.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Change the thumbnail metadata attribute and make sure that the thumbnail
    // is set correctly.
    \Drupal::state()->set('media_source_test_attributes', [
      'thumbnail_uri' => ['value' => 'public://thumbnail1.jpg'],
      'alternative_thumbnail_uri' => ['value' => 'public://thumbnail2.jpg'],
    ]);
    \Drupal::state()->set('media_source_test_definition', ['thumbnail_uri_metadata_attribute' => 'alternative_thumbnail_uri']);
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Mr. Jones',
      'field_media_test' => 'some_value',
    ]);
    $media_source = $media->getSource();
    $this->assertSame('public://thumbnail1.jpg', $media_source->getMetadata($media, 'thumbnail_uri'), 'Value of the metadata attribute is not correct.');
    $this->assertSame('public://thumbnail2.jpg', $media_source->getMetadata($media, 'alternative_thumbnail_uri'), 'Value of the thumbnail metadata attribute is not correct.');
    $media->save();
    $this->assertSame('public://thumbnail2.jpg', $media->thumbnail->entity->getFileUri(), 'Correct metadata attribute was not used for the thumbnail.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Set the width and height metadata attributes and make sure they're used
    // for the thumbnail.
    \Drupal::state()->set('media_source_test_definition', [
      'thumbnail_width_metadata_attribute' => 'width',
      'thumbnail_height_metadata_attribute' => 'height',
    ]);
    \Drupal::state()->set('media_source_test_attributes', [
      'width' => ['value' => 1024],
      'height' => ['value' => 768],
    ]);
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Are you looking at me?',
      'field_media_test' => 'some_value',
    ]);
    $media->save();
    $this->assertSame(1024, $media->thumbnail->width);
    $this->assertSame(768, $media->thumbnail->height);

    // Enable queued thumbnails and make sure that the entity gets the default
    // thumbnail initially.
    \Drupal::state()->set('media_source_test_definition', []);
    \Drupal::state()->set('media_source_test_attributes', [
      'thumbnail_uri' => ['value' => 'public://thumbnail1.jpg'],
    ]);
    $this->testMediaType->setQueueThumbnailDownloadsStatus(TRUE)->save();
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Mr. Jones',
      'field_media_test' => 'some_value',
    ]);
    $this->assertSame('public://thumbnail1.jpg', $media->getSource()->getMetadata($media, 'thumbnail_uri'), 'Value of the metadata attribute is not correct.');
    $media->save();
    $this->assertSame('public://media-icons/generic/generic.png', $media->thumbnail->entity->getFileUri(), 'Default thumbnail was not set initially.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Process the queue item and make sure that the thumbnail was updated too.
    $queue_name = 'media_entity_thumbnail';
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance($queue_name);
    $queue = \Drupal::queue($queue_name);
    $this->assertSame(1, $queue->numberOfItems(), 'Item was not added to the queue.');

    $item = $queue->claimItem();
    $this->assertSame($media->id(), $item->data['id'], 'Queue item that was created does not belong to the correct entity.');

    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);
    $this->assertSame(0, $queue->numberOfItems(), 'Item was not removed from the queue.');

    $media = Media::load($media->id());
    $this->assertSame('public://thumbnail1.jpg', $media->thumbnail->entity->getFileUri(), 'Thumbnail was not updated by the queue.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('', $media->thumbnail->alt);

    // Set the alt metadata attribute and make sure it's used for the thumbnail.
    \Drupal::state()->set('media_source_test_definition', [
      'thumbnail_alt_metadata_attribute' => 'alt',
    ]);
    \Drupal::state()->set('media_source_test_attributes', [
      'alt' => ['value' => 'This will be alt.'],
    ]);
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Boxer',
      'field_media_test' => 'some_value',
    ]);
    $media->save();
    $this->assertSame('Boxer', $media->getName(), 'Correct name was not set on the media item.');
    $this->assertEmpty($media->thumbnail->title);
    $this->assertSame('This will be alt.', $media->thumbnail->alt);
  }

  /**
   * Tests the media item constraints functionality.
   */
  public function testConstraints() {
    // Test entity constraints.
    \Drupal::state()->set('media_source_test_entity_constraints', [
      'MediaTestConstraint' => [],
    ]);

    // Create a media item media that uses a source plugin with constraints and
    // make sure the constraints works as expected when validating.
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $this->testConstraintsMediaType->id(),
      'name' => 'I do not like Drupal',
      'field_media_test_constraints' => 'Not checked',
    ]);

    // Validate the entity and make sure violation is reported.
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations */
    $violations = $media->validate();
    $this->assertCount(1, $violations, 'Expected number of validations not found.');
    $this->assertEquals('Inappropriate text.', $violations->get(0)->getMessage(), 'Incorrect constraint validation message found.');

    // Fix the violation and make sure it is not reported anymore.
    $media->setName('I love Drupal!');
    $violations = $media->validate();
    $this->assertCount(0, $violations, 'Expected number of validations not found.');

    // Save and make sure it succeeded.
    $this->assertEmpty($media->id(), 'Entity ID was found.');
    $media->save();
    $this->assertNotEmpty($media->id(), 'Entity ID was not found.');
    $this->assertSame($media->getName(), 'I love Drupal!');

    // Test source field constraints.
    \Drupal::state()->set('media_source_test_field_constraints', [
      'MediaTestConstraint' => [],
    ]);
    \Drupal::state()->set('media_source_test_entity_constraints', []);

    // Create media that uses source with constraints and make sure it can't
    // be saved without validating them.
    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::create([
      'bundle' => $this->testConstraintsMediaType->id(),
      'name' => 'Not checked',
      'field_media_test_constraints' => 'I do not like Drupal',
    ]);

    // Validate the entity and make sure violation is reported.
    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations */
    $violations = $media->validate();
    $this->assertCount(1, $violations, 'Expected number of validations not found.');
    $this->assertEquals('Inappropriate text.', $violations->get(0)->getMessage(), 'Incorrect constraint validation message found.');

    // Fix the violation and make sure it is not reported anymore.
    $media->set('field_media_test_constraints', 'I love Drupal!');
    $violations = $media->validate();
    $this->assertCount(0, $violations, 'Expected number of validations not found.');

    // Save and make sure it succeeded.
    $this->assertEmpty($media->id(), 'Entity ID was found.');
    $media->save();
    $this->assertNotEmpty($media->id(), 'Entity ID was not found.');
  }

  /**
   * Tests logic related to the automated source field creation.
   */
  public function testSourceFieldCreation() {
    /** @var \Drupal\media\MediaTypeInterface $type */
    $type = MediaType::create([
      'id' => 'test_type',
      'label' => 'Test type',
      'source' => 'test',
    ]);

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = $type->getSource()->createSourceField($type);
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = $field->getFieldStorageDefinition();

    // Test field storage.
    $this->assertTrue($field_storage->isNew(), 'Field storage is saved automatically.');
    $this->assertFalse($field_storage->isLocked(), 'Field storage is not locked.');
    $this->assertSame('string', $field_storage->getType(), 'Field is not of correct type.');
    $this->assertSame('field_media_test_1', $field_storage->getName(), 'Incorrect field name is used.');
    $this->assertSame('media', $field_storage->getTargetEntityTypeId(), 'Field is not targeting media entities.');

    // Test field.
    $this->assertTrue($field->isNew(), 'Field is saved automatically.');
    $this->assertSame('field_media_test_1', $field->getName(), 'Incorrect field name is used.');
    $this->assertSame('string', $field->getType(), 'Field is of incorrect type.');
    $this->assertTrue($field->isRequired(), 'Field is not required.');
    $this->assertEquals('Test source', $field->label(), 'Incorrect label is used.');
    $this->assertSame('test_type', $field->getTargetBundle(), 'Field is not targeting correct bundle.');

    // Fields should be automatically saved only when creating the media type
    // using the media type creation form. Make sure that they are not saved
    // when creating a media type programmatically.
    // Drupal\Tests\media\FunctionalJavascript\MediaTypeCreationTest is testing
    // form part of the functionality.
    $type->save();
    $storage = FieldStorageConfig::load('media.field_media_test_1');
    $this->assertNull($storage, 'Field storage was not saved.');
    $field = FieldConfig::load('media.test_type.field_media_test_1');
    $this->assertNull($field, 'Field storage was not saved.');

    // Test the plugin with a different default source field type.
    $type = MediaType::create([
      'id' => 'test_constraints_type',
      'label' => 'Test type with constraints',
      'source' => 'test_constraints',
    ]);
    $field = $type->getSource()->createSourceField($type);
    $field_storage = $field->getFieldStorageDefinition();

    // Test field storage.
    $this->assertTrue($field_storage->isNew(), 'Field storage is saved automatically.');
    $this->assertFalse($field_storage->isLocked(), 'Field storage is not locked.');
    $this->assertSame('string_long', $field_storage->getType(), 'Field is of incorrect type.');
    $this->assertSame('field_media_test_constraints_1', $field_storage->getName(), 'Incorrect field name is used.');
    $this->assertSame('media', $field_storage->getTargetEntityTypeId(), 'Field is not targeting media entities.');

    // Test field.
    $this->assertTrue($field->isNew(), 'Field is saved automatically.');
    $this->assertSame('field_media_test_constraints_1', $field->getName(), 'Incorrect field name is used.');
    $this->assertSame('string_long', $field->getType(), 'Field is of incorrect type.');
    $this->assertTrue($field->isRequired(), 'Field is not required.');
    $this->assertEquals('Test source with constraints', $field->label(), 'Incorrect label is used.');
    $this->assertSame('test_constraints_type', $field->getTargetBundle(), 'Field is not targeting correct bundle.');
  }

  /**
   * Tests configuration form submit handler on the base media source plugin.
   */
  public function testSourceConfigurationSubmit() {
    /** @var \Drupal\media\MediaSourceManager $manager */
    $manager = $this->container->get('plugin.manager.media.source');
    $form = [];
    $form_state = new FormState();
    $form_state->setValues(['test_config_value' => 'Somewhere over the rainbow.']);

    /** @var \Drupal\media\MediaSourceInterface $source */
    $source = $manager->createInstance('test', []);
    $source->submitConfigurationForm($form, $form_state);
    $expected = ['source_field' => 'field_media_test_1', 'test_config_value' => 'Somewhere over the rainbow.'];
    $this->assertSame($expected, $source->getConfiguration(), 'Submitted values were saved correctly.');

    // Try to save a NULL value.
    $form_state->setValue('test_config_value', NULL);
    $source->submitConfigurationForm($form, $form_state);
    $expected['test_config_value'] = NULL;
    $this->assertSame($expected, $source->getConfiguration(), 'Submitted values were saved correctly.');

    // Make sure that the config keys are determined correctly even if the
    // existing value is NULL.
    $form_state->setValue('test_config_value', 'Somewhere over the rainbow.');
    $source->submitConfigurationForm($form, $form_state);
    $expected['test_config_value'] = 'Somewhere over the rainbow.';
    $this->assertSame($expected, $source->getConfiguration(), 'Submitted values were saved correctly.');

    // Make sure that a non-relevant value will be skipped.
    $form_state->setValue('not_relevant', 'Should not be saved in the plugin.');
    $source->submitConfigurationForm($form, $form_state);
    $this->assertSame($expected, $source->getConfiguration(), 'Submitted values were saved correctly.');
  }

  /**
   * Tests different display options for the source field.
   */
  public function testDifferentSourceFieldDisplays() {
    $id = 'test_different_displays';
    $field_name = 'field_media_different_display';

    $this->createMediaTypeViaForm($id, $field_name);

    // Source field not in displays.
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $id);
    $components = $display->getComponents();
    $this->assertArrayHasKey($field_name, $components);
    $this->assertSame('entity_reference_entity_id', $components[$field_name]['type']);

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('media', $id);
    $components = $display->getComponents();
    $this->assertArrayHasKey($field_name, $components);
    $this->assertSame('entity_reference_autocomplete_tags', $components[$field_name]['type']);
  }

  /**
   * Tests hidden source field in media type.
   */
  public function testHiddenSourceField() {
    $id = 'test_hidden_source_field';
    $field_name = 'field_media_hidden';

    $this->createMediaTypeViaForm($id, $field_name);

    // Source field not in displays.
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $id);
    $this->assertArrayNotHasKey($field_name, $display->getComponents());

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('media', $id);
    $this->assertArrayNotHasKey($field_name, $display->getComponents());
  }

  /**
   * Creates a media type via form submit.
   *
   * @param string $source_plugin_id
   *   Source plugin ID.
   * @param string $field_name
   *   Source field name.
   */
  protected function createMediaTypeViaForm($source_plugin_id, $field_name) {
    /** @var \Drupal\media\MediaTypeInterface $type */
    $type = MediaType::create(['source' => $source_plugin_id]);

    $form = $this->container->get('entity_type.manager')
      ->getFormObject('media_type', 'add')
      ->setEntity($type);

    $form_state = new FormState();
    $form_state->setValues([
      'label' => 'Test type',
      'id' => $source_plugin_id,
      'op' => 'Save',
    ]);

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');

    // Source field not created yet.
    $fields = $field_manager->getFieldDefinitions('media', $source_plugin_id);
    $this->assertArrayNotHasKey($field_name, $fields);

    \Drupal::formBuilder()->submitForm($form, $form_state);

    // Source field exists now.
    $fields = $field_manager->getFieldDefinitions('media', $source_plugin_id);
    $this->assertArrayHasKey($field_name, $fields);
  }

}
