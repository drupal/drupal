<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\user\Entity\Role;

/**
 * Tests using entity fields of the image field type.
 *
 * @group image
 */
class ImageItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
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
    $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent('image_test', ['type' => 'image_image'])->save();
  }

  /**
   * Tests using entity fields of the image field type.
   */
  public function testImageItem(): void {
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
    $entity = EntityTest::create(['name' => $this->randomMachineName()]);
    $entity->save();

    // Test image item properties.
    $expected = ['target_id', 'entity', 'alt', 'title', 'width', 'height'];
    $properties = $entity->getFieldDefinition('image_test')->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEquals($expected, array_keys($properties));

  }

  /**
   * Tests generateSampleItems() method under different dimensions.
   */
  public function testImageItemSampleValueGeneration(): void {

    // Default behavior. No dimensions configuration.
    $entity = EntityTest::create();
    $entity->image_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
    $this->assertEquals('image/jpeg', $entity->image_test->entity->get('filemime')->value);

    // Max dimensions bigger than 600x600.
    $entity->image_test_generation->generateSampleItems();
    $this->entityValidateAndSave($entity);
    $imageItem = $entity->image_test_generation->first()->getValue();
    $this->assertEquals('800', $imageItem['width']);
    $this->assertEquals('800', $imageItem['height']);
  }

  /**
   * Tests a malformed image.
   */
  public function testImageItemMalformed(): void {
    \Drupal::service('module_installer')->install(['dblog']);

    // Validate entity is an image and don't gather dimensions if it is not.
    $entity = EntityTest::create();
    $entity->image_test = NULL;
    $entity->image_test->target_id = 9999;
    $entity->save();
    // Check that the proper warning has been logged.
    $arguments = [
      '%id' => 9999,
    ];
    $logged = Database::getConnection()->select('watchdog')
      ->fields('watchdog', ['variables'])
      ->condition('type', 'image')
      ->condition('message', "Missing file with ID %id.")
      ->execute()
      ->fetchField();
    $this->assertEquals(serialize($arguments), $logged);
    $this->assertEmpty($entity->image_test->width);
    $this->assertEmpty($entity->image_test->height);
  }

  /**
   * Tests image URIs for empty and custom directories.
   */
  public function testImageUriDirectories(): void {
    $this->validateImageUriForDirectory('', 'public://');
    $this->validateImageUriForDirectory('custom_directory/subdir', 'public://custom_directory/subdir/');
  }

  /**
   * Tests display_default.
   */
  public function testDisplayDefaultValue(): void {
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $form_object = $this->container->get(EntityTypeManagerInterface::class)->getFormObject('entity_test', 'default');
    \assert($form_object instanceof ContentEntityForm);
    $form_object->setEntity($entity);
    $form_display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
    \assert($form_display instanceof EntityFormDisplay);
    $form_state = new FormState();
    $form_object->setFormDisplay($form_display, $form_state);
    $this->container->get(FormBuilderInterface::class)->buildForm($form_object, $form_state);
    self::assertEquals(1, $form_state->getValue(['image_test', 0, 'display']));
  }

  /**
   * Validates the image file URI generated for a given file directory.
   *
   * @param string $file_directory
   *   The file directory to test (e.g., empty or 'custom_directory/subdir').
   * @param string $expected_start
   *   The expected starting string of the file URI (e.g., 'public://').
   */
  private function validateImageUriForDirectory(string $file_directory, string $expected_start): void {
    // Mock the field definition with the specified file directory.
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->expects($this->any())
      ->method('getSettings')
      ->willReturn([
        'file_extensions' => 'jpg',
        'file_directory' => $file_directory,
        'uri_scheme' => 'public',
      ]);
    // Generate sample value and check the URI format.
    $value = ImageItem::generateSampleValue($definition);
    $this->assertNotEmpty($value);

    // Load the file entity and get its URI.
    $fid = $value['target_id'];
    $file = File::load($fid);
    $fileUri = $file->getFileUri();

    // Verify the file URI starts with the expected protocol and structure.
    $this->assertStringStartsWith($expected_start, $fileUri);
    $this->assertMatchesRegularExpression('#^' . preg_quote($expected_start, '#') . '[^/]+#', $fileUri);
  }

}
