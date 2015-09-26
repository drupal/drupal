<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFileUploadTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests an autocomplete widget with file upload.
 *
 * @group entity_reference
 */
class EntityReferenceFileUploadTest extends WebTestBase {

  public static $modules = array('entity_reference', 'node', 'file');

  /**
   * The name of a content type that will reference $referencedType.
   *
   * @var string
   */
  protected $referencingType;

  /**
   * The name of a content type that will be referenced by $referencingType.
   *
   * @var string
   */
  protected $referencedType;

  /**
   * Node id.
   *
   * @var integer
   */
  protected $nodeId;

  protected function setUp() {
    parent::setUp();

    // Create "referencing" and "referenced" node types.
    $referencing = $this->drupalCreateContentType();
    $this->referencingType = $referencing->id();

    $referenced = $this->drupalCreateContentType();
    $this->referencedType = $referenced->id();
    $this->nodeId = $this->drupalCreateNode(array('type' => $referenced->id()))->id();

    entity_create('field_storage_config', array(
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'translatable' => FALSE,
      'entity_types' => array(),
      'settings' => array(
        'target_type' => 'node',
      ),
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();

    entity_create('field_config', array(
      'label' => 'Entity reference field',
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'required' => TRUE,
      'bundle' => $referencing->id(),
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          // Reference a single vocabulary.
          'target_bundles' => array(
            $referenced->id(),
          ),
        ),
      ),
    ))->save();


    // Create a file field.
    $file_field_name = 'file_field';
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $file_field_name,
      'entity_type' => 'node',
      'type' => 'file'
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'entity_type' => 'node',
      'field_storage' => $field_storage,
      'bundle' => $referencing->id(),
      'label' => $this->randomMachineName() . '_label',
    ))->save();

    entity_get_display('node', $referencing->id(), 'default')
      ->setComponent('test_field')
      ->setComponent($file_field_name)
      ->save();
    entity_get_form_display('node', $referencing->id(), 'default')
      ->setComponent('test_field', array(
        'type' => 'entity_reference_autocomplete',
      ))
      ->setComponent($file_field_name, array(
         'type' => 'file_generic',
      ))
      ->save();
  }

  /**
   * Tests that the autocomplete input element does not cause ajax fatal.
   */
  public function testFileUpload() {
    $user1 = $this->drupalCreateUser(array('access content', "create $this->referencingType content"));
    $this->drupalLogin($user1);

    $test_file = current($this->drupalGetTestFiles('text'));
    $edit['files[file_field_0]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('node/add/' . $this->referencingType, $edit, 'Upload');
    $this->assertResponse(200);
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'test_field[0][target_id]' => $this->nodeId,
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
  }
}
