<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;

/**
 * Tests using entity fields of the file field type.
 *
 * @group file
 */
class FileItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file'];

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * Directory where the sample files are stored.
   *
   * @var string
   */
  protected $directory;

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    FieldStorageConfig::create([
      'field_name' => 'file_test',
      'entity_type' => 'entity_test',
      'type' => 'file',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    $this->directory = $this->getRandomGenerator()->name(8);
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'file_test',
      'bundle' => 'entity_test',
      'settings' => ['file_directory' => $this->directory],
    ])->save();
    file_put_contents('public://example.txt', $this->randomMachineName());
    $this->file = File::create([
      'uri' => 'public://example.txt',
    ]);
    $this->file->save();
  }

  /**
   * Tests using entity fields of the file field type.
   */
  public function testFileItem() {
    // Check that the selection handler was automatically assigned to
    // 'default:file'.
    $field_definition = FieldConfig::load('entity_test.entity_test.file_test');
    $handler_id = $field_definition->getSetting('handler');
    $this->assertEqual($handler_id, 'default:file');

    // Create a test entity with the
    $entity = EntityTest::create();
    $entity->file_test->target_id = $this->file->id();
    $entity->file_test->display = 1;
    $entity->file_test->description = $description = $this->randomMachineName();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = EntityTest::load($entity->id());
    $this->assertTrue($entity->file_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->file_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->file_test->target_id, $this->file->id());
    $this->assertEqual($entity->file_test->display, 1);
    $this->assertEqual($entity->file_test->description, $description);
    $this->assertEqual($entity->file_test->entity->getFileUri(), $this->file->getFileUri());
    $this->assertEqual($entity->file_test->entity->url(), $url = file_create_url($this->file->getFileUri()));
    $this->assertEqual($entity->file_test->entity->id(), $this->file->id());
    $this->assertEqual($entity->file_test->entity->uuid(), $this->file->uuid());

    // Make sure the computed files reflects updates to the file.
    file_put_contents('public://example-2.txt', $this->randomMachineName());
    $file2 = File::create([
      'uri' => 'public://example-2.txt',
    ]);
    $file2->save();

    $entity->file_test->target_id = $file2->id();
    $this->assertEqual($entity->file_test->entity->id(), $file2->id());
    $this->assertEqual($entity->file_test->entity->getFileUri(), $file2->getFileUri());

    // Test the deletion of an entity having an entity reference field targeting
    // a non-existing entity.
    $file2->delete();
    $entity->delete();

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->file_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
    // Verify that the sample file was stored in the correct directory.
    $uri = $entity->file_test->entity->getFileUri();
    $this->assertEqual($this->directory, dirname(file_uri_target($uri)));

    // Make sure the computed files reflects updates to the file.
    file_put_contents('public://example-3.txt', $this->randomMachineName());
    // Test unsaved file entity.
    $file3 = File::create([
      'uri' => 'public://example-3.txt',
    ]);
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $display->setComponent('file_test', [
      'label' => 'above',
      'type' => 'file_default',
      'weight' => 1,
    ])->save();
    $entity = EntityTest::create();
    $entity->file_test = ['entity' => $file3];
    $uri = $file3->getFileUri();
    $output = entity_view($entity, 'default');
    \Drupal::service('renderer')->renderRoot($output);
    $this->assertTrue(!empty($entity->file_test->entity));
    $this->assertEqual($entity->file_test->entity->getFileUri(), $uri);
  }

}
