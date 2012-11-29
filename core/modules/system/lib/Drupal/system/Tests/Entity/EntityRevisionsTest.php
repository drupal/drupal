<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityRevisionsTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the basic revisioning functionality of entities.
 */
class EntityRevisionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Entity revisions',
      'description' => 'Create a entity with revisions and test viewing, saving, reverting, and deleting revisions.',
      'group' => 'Entity API',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create and login user.
    $this->web_user = $this->drupalCreateUser(array(
      'view all revisions',
      'revert all revisions',
      'delete all revisions',
      'administer entity_test content',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Check node revision related operations.
   */
  public function testRevisions() {

    // Create initial entity.
    $entity = entity_create('entity_test', array(
      'name' => 'foo',
      'user_id' => $this->web_user->uid,
    ));
    $entity->field_test_text->value = 'bar';
    $entity->save();

    $entities = array();
    $names = array();
    $texts = array();
    $revision_ids = array();

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $legacy_revision_id = $entity->revision_id->value;
      $legacy_name = $entity->name->value;
      $legacy_text = $entity->field_test_text->value;

      $entity = entity_test_load($entity->id->value);
      $entity->setNewRevision(TRUE);
      $names[] = $entity->name->value = $this->randomName(32);
      $texts[] = $entity->field_test_text->value = $this->randomName(32);
      $entity->save();
      $revision_ids[] = $entity->revision_id->value;

      // Check that the fields and properties contain new content.
      $this->assertTrue($entity->revision_id->value > $legacy_revision_id, 'Revision ID changed.');
      $this->assertNotEqual($entity->name->value, $legacy_name, 'Name changed.');
      $this->assertNotEqual($entity->field_test_text->value, $legacy_text, 'Text changed.');
    }

    for ($i = 0; $i < $revision_count; $i++) {
      // Load specific revision.
      $entity_revision = entity_revision_load('entity_test', $revision_ids[$i]);

      // Check if properties and fields contain the revision specific content.
      $this->assertEqual($entity_revision->revision_id->value, $revision_ids[$i], 'Revision ID matches.');
      $this->assertEqual($entity_revision->name->value, $names[$i], 'Name matches.');
      $this->assertEqual($entity_revision->field_test_text->value, $texts[$i], 'Text matches.');
    }

    // Confirm the correct revision text appears in the edit form.
    $entity = entity_load('entity_test', $entity->id->value);
    $this->drupalGet('entity-test/manage/' . $entity->id->value);
    $this->assertFieldById('edit-name', $entity->name->value, 'Name matches in UI.');
    $this->assertFieldById('edit-field-test-text-und-0-value', $entity->field_test_text->value, 'Text matches in UI.');
  }
}
