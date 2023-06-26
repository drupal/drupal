<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Error\Warning;

/**
 * Tests using entity fields of the image field type.
 *
 * @group image
 */
class ImageItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'image'];

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['user']);
    // Give anonymous users permission to access content, so that we can view
    // and download public file.
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->save();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test_generation',
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'bundle' => 'entity_test',
      'settings' => [
        'file_extensions' => 'jpg',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'image_test_generation',
      'bundle' => 'entity_test',
      'settings' => [
        'min_resolution' => '800x800',
      ],
    ])->save();

    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = File::create([
      'uri' => 'public://example.jpg',
    ]);
    $this->image->save();
    $this->imageFactory = $this->container->get('image.factory');
  }

  /**
   * Tests using entity fields of the image field type.
   */
  public function testImageItem() {
    // Create a test entity with the image field set.
    $entity = EntityTest::create();
    $entity->image_test->target_id = $this->image->id();
    $entity->image_test->alt = $alt = $this->randomMachineName();
    $entity->image_test->title = $title = $this->randomMachineName();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = EntityTest::load($entity->id());
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->image_test);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->image_test[0]);
    $this->assertEquals($this->image->id(), $entity->image_test->target_id);
    $this->assertEquals($alt, $entity->image_test->alt);
    $this->assertEquals($title, $entity->image_test->title);
    $image = $this->imageFactory->get('public://example.jpg');
    $this->assertEquals($image->getWidth(), $entity->image_test->width);
    $this->assertEquals($image->getHeight(), $entity->image_test->height);
    $this->assertEquals($this->image->id(), $entity->image_test->entity->id());
    $this->assertEquals($this->image->uuid(), $entity->image_test->entity->uuid());

    // Make sure the computed entity reflects updates to the referenced file.
    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://example-2.jpg');
    $image2 = File::create([
      'uri' => 'public://example-2.jpg',
    ]);
    $image2->save();

    $entity->image_test->target_id = $image2->id();
    $entity->image_test->alt = $new_alt = $this->randomMachineName();
    // The width and height is only updated when width is not set.
    $entity->image_test->width = NULL;
    $entity->save();
    $this->assertEquals($image2->id(), $entity->image_test->entity->id());
    $this->assertEquals($image2->getFileUri(), $entity->image_test->entity->getFileUri());
    $image = $this->imageFactory->get('public://example-2.jpg');
    $this->assertEquals($image->getWidth(), $entity->image_test->width);
    $this->assertEquals($image->getHeight(), $entity->image_test->height);
    $this->assertEquals($new_alt, $entity->image_test->alt);

    // Check that the image item can be set to the referenced file directly.
    $entity->image_test = $this->image;
    $this->assertEquals($this->image->id(), $entity->image_test->target_id);

    // Delete the image and try to save the entity again.
    $this->image->delete();
    $entity = EntityTest::create(['mame' => $this->randomMachineName()]);
    $entity->save();

    // Test image item properties.
    $expected = ['target_id', 'entity', 'alt', 'title', 'width', 'height'];
    $properties = $entity->getFieldDefinition('image_test')->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEquals($expected, array_keys($properties));

  }

  /**
   * Tests generateSampleItems() method under different resolutions.
   */
  public function testImageItemSampleValueGeneration() {

    // Default behaviour. No resolution configuration.
    $entity = EntityTest::create();
    $entity->image_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
    $this->assertEquals('image/jpeg', $entity->image_test->entity->get('filemime')->value);

    // Max resolution bigger than 600x600.
    $entity->image_test_generation->generateSampleItems();
    $this->entityValidateAndSave($entity);
    $imageItem = $entity->image_test_generation->first()->getValue();
    $this->assertEquals('800', $imageItem['width']);
    $this->assertEquals('800', $imageItem['height']);
  }

  /**
   * Tests a malformed image.
   */
  public function testImageItemMalformed() {
    // Validate entity is an image and don't gather dimensions if it is not.
    $entity = EntityTest::create();
    $entity->image_test = NULL;
    $entity->image_test->target_id = 9999;
    // PHPUnit re-throws E_USER_WARNING as an exception.
    try {
      $entity->save();
      $this->fail('Exception did not fail');
    }
    catch (EntityStorageException $exception) {
      $this->assertInstanceOf(Warning::class, $exception->getPrevious());
      $this->assertEquals('Missing file with ID 9999.', $exception->getMessage());
      $this->assertEmpty($entity->image_test->width);
      $this->assertEmpty($entity->image_test->height);
    }

  }

}
