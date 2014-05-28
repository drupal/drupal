<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityFormTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Entity Form Controller.
 */
class EntityFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'language');

  public static function getInfo() {
    return array(
      'name' => 'Entity form',
      'description' => 'Tests the entity form.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(array('administer entity_test content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests basic form CRUD functionality.
   */
  function testFormCRUD() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->assertFormCRUD($entity_type);
    }
  }

  /**
   * Tests hook_entity_form_display_alter().
   *
   * @see entity_test_entity_form_display_alter()
   */
  function testEntityFormDisplayAlter() {
    $this->drupalGet('entity_test/add');
    $altered_field = $this->xpath('//input[@name="field_test_text[0][value]" and @size="42"]');
    $this->assertTrue(count($altered_field) === 1, 'The altered field has the correct size value.');
  }

  /**
   * Executes the form CRUD tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function assertFormCRUD($entity_type) {
    $name1 = $this->randomName(8);
    $name2 = $this->randomName(10);

    $edit = array(
      'name' => $name1,
      'user_id' => mt_rand(0, 128),
      'field_test_text[0][value]' => $this->randomName(16),
    );

    $this->drupalPostForm($entity_type . '/add', $edit, t('Save'));
    $entity = $this->loadEntityByName($entity_type, $name1);
    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $entity_type)));

    $edit['name'] = $name2;
    $this->drupalPostForm($entity_type . '/manage/' . $entity->id(), $edit, t('Save'));
    $entity = $this->loadEntityByName($entity_type, $name1);
    $this->assertFalse($entity, format_string('%entity_type: The entity has been modified.', array('%entity_type' => $entity_type)));
    $entity = $this->loadEntityByName($entity_type, $name2);
    $this->assertTrue($entity, format_string('%entity_type: Modified entity found in the database.', array('%entity_type' => $entity_type)));
    $this->assertNotEqual($entity->name->value, $name1, format_string('%entity_type: The entity name has been modified.', array('%entity_type' => $entity_type)));

    $this->drupalGet($entity_type . '/manage/' . $entity->id());
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $entity = $this->loadEntityByName($entity_type, $name2);
    $this->assertFalse($entity, format_string('%entity_type: Entity not found in the database.', array('%entity_type' => $entity_type)));
  }

  /**
   * Loads a test entity by name always resetting the storage cache.
   */
  protected function loadEntityByName($entity_type, $name) {
    // Always load the entity from the database to ensure that changes are
    // correctly picked up.
    $this->container->get('entity.manager')->getStorage($entity_type)->resetCache();
    return current(entity_load_multiple_by_properties($entity_type, array('name' => $name)));
  }
}
