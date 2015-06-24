<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileItemTest.
 */

namespace Drupal\file\Tests;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests using entity fields of the file field type.
 *
 * @group file
 */
class FileItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file');

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', array('file_usage'));

    entity_create('field_storage_config', array(
      'field_name' => 'file_test',
      'entity_type' => 'entity_test',
      'type' => 'file',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'file_test',
      'bundle' => 'entity_test',
    ))->save();
    file_put_contents('public://example.txt', $this->randomMachineName());
    $this->file = entity_create('file', array(
      'uri' => 'public://example.txt',
    ));
    $this->file->save();
  }

  /**
   * Tests using entity fields of the file field type.
   */
  public function testFileItem() {
    // Create a test entity with the
    $entity = entity_create('entity_test');
    $entity->file_test->target_id = $this->file->id();
    $entity->file_test->display = 1;
    $entity->file_test->description = $description = $this->randomMachineName();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
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
    $file2 = entity_create('file', array(
      'uri' => 'public://example-2.txt',
    ));
    $file2->save();

    $entity->file_test->target_id = $file2->id();
    $this->assertEqual($entity->file_test->entity->id(), $file2->id());
    $this->assertEqual($entity->file_test->entity->getFileUri(), $file2->getFileUri());

    // Test the deletion of an entity having an entity reference field targeting
    // a non-existing entity.
    $file2->delete();
    $entity->delete();

    // Test the generateSampleValue() method.
    $entity = entity_create('entity_test');
    $entity->file_test->generateSampleItems();
    $this->entityValidateAndSave($entity);

    // Make sure the computed files reflects updates to the file.
    file_put_contents('public://example-3.txt', $this->randomMachineName());
    // Test unsaved file entity.
    $file3 = entity_create('file', array(
      'uri' => 'public://example-3.txt',
    ));
    $display = entity_get_display('entity_test', 'entity_test', 'default');
    $display->setComponent('file_test', [
      'label' => 'above',
      'type' => 'file_default',
      'weight' => 1,
    ])->save();
    $entity = entity_create('entity_test');
    $entity->file_test = array('entity' => $file3);
    $uri = $file3->getFileUri();
    $output = entity_view($entity, 'default');
    \Drupal::service('renderer')->renderRoot($output);
    $this->assertTrue(!empty($entity->file_test->entity));
    $this->assertEqual($entity->file_test->entity->getFileUri(), $uri);
  }

}
