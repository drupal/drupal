<?php

/**
 * @file
 * Definition of Drupal\edit\Tests\MetadataGeneratorTest.
 */

namespace Drupal\edit\Tests;

use Drupal\edit\EditorSelector;
use Drupal\edit\MetadataGenerator;
use Drupal\edit\Plugin\ProcessedTextEditorManager;
use Drupal\edit_test\MockEditEntityFieldAccessCheck;

/**
 * Test in-place field editing metadata.
 */
class MetadataGeneratorTest extends EditTestBase {

  /**
   * The metadata generator object to be tested.
   *
   * @var \Drupal\edit\MetadataGeneratorInterface.php
   */
  protected $metadataGenerator;

  /**
   * The editor selector object to be used by the metadata generator object.
   *
   * @var \Drupal\edit\EditorSelectorInterface
   */
  protected $editorSelector;

  /**
   * The access checker object to be used by the metadata generator object.
   *
   * @var \Drupal\edit\Access\EditEntityFieldAccessCheckInterface
   */
  protected $accessChecker;

  public static function getInfo() {
    return array(
      'name' => 'In-place field editing metadata',
      'description' => 'Tests in-place field editing metadata generation.',
      'group' => 'Edit',
    );
  }

  function setUp() {
    parent::setUp();

    // @todo Rather than using the real ProcessedTextEditorManager, which can
    //   find all text editor plugins in the codebase, create a mock one for
    //   testing that is populated with only the ones we want to test.
    $text_editor_manager = new ProcessedTextEditorManager();

    $this->accessChecker = new MockEditEntityFieldAccessCheck();
    $this->editorSelector = new EditorSelector($text_editor_manager);
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector);
  }

  /**
   * Tests a simple entity type, with two different simple fields.
   */
  function testSimpleEntityType() {
    $field_1_name = 'field_text';
    $field_1_label = 'Simple text field';
    $this->createFieldWithInstance(
      $field_1_name, 'text', 1, $field_1_label,
      // Instance settings.
      array('text_processing' => 0),
      // Widget type & settings.
      'text_textfield',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );
    $field_2_name = 'field_nr';
    $field_2_label = 'Simple number field';
    $this->createFieldWithInstance(
      $field_2_name, 'number_integer', 1, $field_2_label,
      // Instance settings.
      array(),
      // Widget type & settings.
      'number',
      array(),
      // 'default' formatter type & settings.
      'number_integer',
      array()
    );

    // Create an entity with values for this text field.
    $this->entity = field_test_create_entity();
    $this->is_new = TRUE;
    $this->entity->{$field_1_name}[LANGUAGE_NOT_SPECIFIED] = array(array('value' => 'Test'));
    $this->entity->{$field_2_name}[LANGUAGE_NOT_SPECIFIED] = array(array('value' => 42));
    field_test_entity_save($this->entity);
    $entity = entity_load('test_entity', $this->entity->ftid);

    // Verify metadata for field 1.
    $instance_1 = field_info_instance($entity->entityType(), $field_1_name, $entity->bundle());
    $metadata_1 = $this->metadataGenerator->generate($entity, $instance_1, LANGUAGE_NOT_SPECIFIED, 'default');
    $expected_1 = array(
      'access' => TRUE,
      'label' => 'Simple text field',
      'editor' => 'direct',
      'aria' => 'Entity test_entity 1, field Simple text field',
    );
    $this->assertEqual($expected_1, $metadata_1, 'The correct metadata is generated for the first field.');

    // Verify metadata for field 2.
    $instance_2 = field_info_instance($entity->entityType(), $field_2_name, $entity->bundle());
    $metadata_2 = $this->metadataGenerator->generate($entity, $instance_2, LANGUAGE_NOT_SPECIFIED, 'default');
    $expected_2 = array(
      'access' => TRUE,
      'label' => 'Simple number field',
      'editor' => 'form',
      'aria' => 'Entity test_entity 1, field Simple number field',
    );
    $this->assertEqual($expected_2, $metadata_2, 'The correct metadata is generated for the second field.');

  }
}
