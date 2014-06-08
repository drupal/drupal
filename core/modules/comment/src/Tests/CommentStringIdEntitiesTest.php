<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentStringIdEntitiesTest.
 */

namespace Drupal\comment\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests that comment fields cannot be added to entities with non-integer IDs.
 */
class CommentStringIdEntitiesTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'user', 'field', 'field_ui', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Comments on Entity Types with string IDs',
      'description' => 'Test that comment fields cannot be added to entities with non-integer IDs',
      'group' => 'Comment',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installSchema('comment', array('comment_entity_statistics'));
  }

  /**
   * Tests that comment fields cannot be added entities with non-integer IDs.
   */
  public function testCommentFieldNonStringId() {
    try {
      $field = entity_create('field_config', array(
        'name' => 'foo',
        'entity_type' => 'entity_test_string_id',
        'settings' => array(),
        'type' => 'comment',
      ));
      $field->save();
      $this->fail('Did not throw an exception as expected.');
    }
    catch (\UnexpectedValueException $e) {
      $this->pass('Exception thrown when trying to create comment field on Entity Type with string ID.');
    }
  }

}
