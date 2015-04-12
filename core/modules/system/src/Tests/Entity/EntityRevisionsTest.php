<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityRevisionsTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Create a entity with revisions and test viewing, saving, reverting, and
 * deleting revisions.
 *
 * @group Entity
 */
class EntityRevisionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * A user with permission to administer entity_test content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    // Create and login user.
    $this->webUser = $this->drupalCreateUser(array(
      'administer entity_test content',
    ));
    $this->drupalLogin($this->webUser);
  }

  /**
   * Check node revision related operations.
   */
  public function testRevisions() {

    // All revisable entity variations have to have the same results.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_REVISABLE) as $entity_type) {
      $this->runRevisionsTests($entity_type);
    }
  }

  /**
   * Executes the revision tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function runRevisionsTests($entity_type) {

    // Create initial entity.
    $entity = entity_create($entity_type, array(
      'name' => 'foo',
      'user_id' => $this->webUser->id(),
    ));
    $entity->field_test_text->value = 'bar';
    $entity->save();

    $names = array();
    $texts = array();
    $created = array();
    $revision_ids = array();

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $legacy_revision_id = $entity->revision_id->value;
      $legacy_name = $entity->name->value;
      $legacy_text = $entity->field_test_text->value;

      $entity = entity_load($entity_type, $entity->id->value);
      $entity->setNewRevision(TRUE);
      $names[] = $entity->name->value = $this->randomMachineName(32);
      $texts[] = $entity->field_test_text->value = $this->randomMachineName(32);
      $created[] = $entity->created->value = time() + $i + 1;
      $entity->save();
      $revision_ids[] = $entity->revision_id->value;

      // Check that the fields and properties contain new content.
      $this->assertTrue($entity->revision_id->value > $legacy_revision_id, format_string('%entity_type: Revision ID changed.', array('%entity_type' => $entity_type)));
      $this->assertNotEqual($entity->name->value, $legacy_name, format_string('%entity_type: Name changed.', array('%entity_type' => $entity_type)));
      $this->assertNotEqual($entity->field_test_text->value, $legacy_text, format_string('%entity_type: Text changed.', array('%entity_type' => $entity_type)));
    }

    for ($i = 0; $i < $revision_count; $i++) {
      // Load specific revision.
      $entity_revision = entity_revision_load($entity_type, $revision_ids[$i]);

      // Check if properties and fields contain the revision specific content.
      $this->assertEqual($entity_revision->revision_id->value, $revision_ids[$i], format_string('%entity_type: Revision ID matches.', array('%entity_type' => $entity_type)));
      $this->assertEqual($entity_revision->name->value, $names[$i], format_string('%entity_type: Name matches.', array('%entity_type' => $entity_type)));
      $this->assertEqual($entity_revision->field_test_text->value, $texts[$i], format_string('%entity_type: Text matches.', array('%entity_type' => $entity_type)));

      // Check non-revisioned values are loaded.
      $this->assertTrue(isset($entity_revision->created->value), format_string('%entity_type: Non-revisioned field is loaded.', array('%entity_type' => $entity_type)));
      $this->assertEqual($entity_revision->created->value, $created[2], format_string('%entity_type: Non-revisioned field value is the same between revisions.', array('%entity_type' => $entity_type)));
    }

    // Confirm the correct revision text appears in the edit form.
    $entity = entity_load($entity_type, $entity->id->value);
    $this->drupalGet($entity_type . '/manage/' . $entity->id->value);
    $this->assertFieldById('edit-name-0-value', $entity->name->value, format_string('%entity_type: Name matches in UI.', array('%entity_type' => $entity_type)));
    $this->assertFieldById('edit-field-test-text-0-value', $entity->field_test_text->value, format_string('%entity_type: Text matches in UI.', array('%entity_type' => $entity_type)));
  }
}
