<?php

/**
 * @file
 * Contains \Drupal\image\Tests\ImageItemTest.
 */

namespace Drupal\image\Tests;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests using entity fields of the image field type.
 *
 * @group image
 */
class ImageItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file', 'image');

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $image;

  /**
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', array('file_usage'));

    entity_create('field_storage_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'bundle' => 'entity_test',
    ))->save();
    file_unmanaged_copy(\Drupal::root() . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = entity_create('file', array(
      'uri' => 'public://example.jpg',
    ));
    $this->image->save();
    $this->imageFactory = $this->container->get('image.factory');
  }

  /**
   * Tests using entity fields of the image field type.
   */
  public function testImageItem() {
    // Create a test entity with the image field set.
    $entity = entity_create('entity_test');
    $entity->image_test->target_id = $this->image->id();
    $entity->image_test->alt = $alt = $this->randomMachineName();
    $entity->image_test->title = $title = $this->randomMachineName();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->image_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->image_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->image_test->target_id, $this->image->id());
    $this->assertEqual($entity->image_test->alt, $alt);
    $this->assertEqual($entity->image_test->title, $title);
    $image = $this->imageFactory->get('public://example.jpg');
    $this->assertEqual($entity->image_test->width, $image->getWidth());
    $this->assertEqual($entity->image_test->height, $image->getHeight());
    $this->assertEqual($entity->image_test->entity->id(), $this->image->id());
    $this->assertEqual($entity->image_test->entity->uuid(), $this->image->uuid());

    // Make sure the computed entity reflects updates to the referenced file.
    file_unmanaged_copy(\Drupal::root() . '/core/misc/druplicon.png', 'public://example-2.jpg');
    $image2 = entity_create('file', array(
      'uri' => 'public://example-2.jpg',
    ));
    $image2->save();

    $entity->image_test->target_id = $image2->id();
    $entity->image_test->alt = $new_alt = $this->randomMachineName();
    // The width and height is only updated when width is not set.
    $entity->image_test->width = NULL;
    $entity->save();
    $this->assertEqual($entity->image_test->entity->id(), $image2->id());
    $this->assertEqual($entity->image_test->entity->getFileUri(), $image2->getFileUri());
    $image = $this->imageFactory->get('public://example-2.jpg');
    $this->assertEqual($entity->image_test->width, $image->getWidth());
    $this->assertEqual($entity->image_test->height, $image->getHeight());
    $this->assertEqual($entity->image_test->alt, $new_alt);

    // Check that the image item can be set to the referenced file directly.
    $entity->image_test = $this->image;
    $this->assertEqual($entity->image_test->target_id, $this->image->id());

    // Delete the image and try to save the entity again.
    $this->image->delete();
    $entity = entity_create('entity_test', array('mame' => $this->randomMachineName()));
    $entity->save();

    // Test image item properties.
    $expected = array('target_id', 'entity', 'alt', 'title', 'width', 'height');
    $properties = $entity->getFieldDefinition('image_test')->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEqual(array_keys($properties), $expected);

    // Test the generateSampleValue() method.
    $entity = entity_create('entity_test');
    $entity->image_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
