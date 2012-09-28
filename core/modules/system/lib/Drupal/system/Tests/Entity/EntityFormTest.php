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
      'description' => 'Tests the entity form controller.',
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
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $name1 = $this->randomName(8);
    $name2 = $this->randomName(8);

    $edit = array(
      'name' => $name1,
      'user_id' => mt_rand(0, 128),
      "field_test_text[$langcode][0][value]" => $this->randomName(16),
    );

    $this->drupalPost('entity-test/add', $edit, t('Save'));
    $entity = $this->loadEntityByName($name1);
    $this->assertTrue($entity, t('Entity found in the database.'));

    $edit['name'] = $name2;
    $this->drupalPost('entity-test/manage/' . $entity->id() . '/edit', $edit, t('Save'));
    $entity = $this->loadEntityByName($name1);
    $this->assertFalse($entity, 'The entity has been modified.');
    $entity = $this->loadEntityByName($name2);
    $this->assertTrue($entity, 'Modified entity found in the database.');
    $this->assertNotEqual($entity->name->value, $name1, 'The entity name has been modified.');

    $this->drupalPost('entity-test/manage/' . $entity->id() . '/edit', array(), t('Delete'));
    $entity = $this->loadEntityByName($name2);
    $this->assertFalse($entity, t('Entity not found in the database.'));
  }

  /**
   * Loads a test entity by name always resetting the storage controller cache.
   */
  protected function loadEntityByName($name) {
    $entity_type = 'entity_test';
    // Always load the entity from the database to ensure that changes are
    // correctly picked up.
    entity_get_controller($entity_type)->resetCache();
    return current(entity_load_multiple_by_properties($entity_type, array('name' => $name)));
  }
}
