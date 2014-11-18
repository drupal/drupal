<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentStringIdEntitiesTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Entity\CommentType;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests that comment fields cannot be added to entities with non-integer IDs.
 *
 * @group comment
 */
class CommentStringIdEntitiesTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'comment',
    'user',
    'field',
    'field_ui',
    'entity',
    'entity_test',
    'text',
  );

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installSchema('comment', array('comment_entity_statistics'));
    // Create the comment body field storage.
    $this->installConfig(array('field'));
  }

  /**
   * Tests that comment fields cannot be added entities with non-integer IDs.
   */
  public function testCommentFieldNonStringId() {
    try {
      $bundle = CommentType::create(array(
        'id' => 'foo',
        'label' => 'foo',
        'description' => '',
        'target_entity_type_id' => 'entity_test_string_id',
      ));
      $bundle->save();
      $field_storage = entity_create('field_storage_config', array(
        'field_name' => 'foo',
        'entity_type' => 'entity_test_string_id',
        'settings' => array(
          'comment_type' => 'entity_test_string_id',
        ),
        'type' => 'comment',
      ));
      $field_storage->save();
      $this->fail('Did not throw an exception as expected.');
    }
    catch (\UnexpectedValueException $e) {
      $this->pass('Exception thrown when trying to create comment field on Entity Type with string ID.');
    }
  }

}
