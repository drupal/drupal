<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityFormTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity form.
 *
 * @group Entity
 */
class EntityFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'language');

  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(array('administer entity_test content', 'view test entity'));
    $this->drupalLogin($web_user);

    // Add a language.
    ConfigurableLanguage::createFromLangcode('ro')->save();
  }

  /**
   * Tests basic form CRUD functionality.
   */
  function testFormCRUD() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestFormCRUD($entity_type);
    }
  }

  /**
   * Tests basic multilingual form CRUD functionality.
   */
  public function testMultilingualFormCRUD() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_MULTILINGUAL) as $entity_type) {
      $this->doTestMultilingualFormCRUD($entity_type);
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
  protected function doTestFormCRUD($entity_type) {
    $name1 = $this->randomMachineName(8);
    $name2 = $this->randomMachineName(10);

    $edit = array(
      'name[0][value]' => $name1,
      'field_test_text[0][value]' => $this->randomMachineName(16),
    );

    $this->drupalPostForm($entity_type . '/add', $edit, t('Save'));
    $entity = $this->loadEntityByName($entity_type, $name1);
    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $entity_type)));

    $edit['name[0][value]'] = $name2;
    $this->drupalPostForm($entity_type . '/manage/' . $entity->id() . '/edit', $edit, t('Save'));
    $entity = $this->loadEntityByName($entity_type, $name1);
    $this->assertFalse($entity, format_string('%entity_type: The entity has been modified.', array('%entity_type' => $entity_type)));
    $entity = $this->loadEntityByName($entity_type, $name2);
    $this->assertTrue($entity, format_string('%entity_type: Modified entity found in the database.', array('%entity_type' => $entity_type)));
    $this->assertNotEqual($entity->name->value, $name1, format_string('%entity_type: The entity name has been modified.', array('%entity_type' => $entity_type)));

    $this->drupalGet($entity_type . '/manage/' . $entity->id() . '/edit');
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $entity = $this->loadEntityByName($entity_type, $name2);
    $this->assertFalse($entity, format_string('%entity_type: Entity not found in the database.', array('%entity_type' => $entity_type)));
  }

  /**
   * Executes the multilingual form CRUD tests for the given entity type ID.
   *
   * @param string $entity_type_id
   *   The ID of entity type to run the tests with.
   */
  protected function doTestMultilingualFormCRUD($entity_type_id) {
    $name1 = $this->randomMachineName(8);
    $name1_ro = $this->randomMachineName(9);
    $name2_ro = $this->randomMachineName(11);

    $edit = array(
      'name[0][value]' => $name1,
      'field_test_text[0][value]' => $this->randomMachineName(16),
    );

    $this->drupalPostForm($entity_type_id . '/add', $edit, t('Save'));
    $entity = $this->loadEntityByName($entity_type_id, $name1);
    $this->assertTrue($entity, format_string('%entity_type: Entity found in the database.', array('%entity_type' => $entity_type_id)));

    // Add a translation to the newly created entity without using the Content
    // translation module.
    $entity->addTranslation('ro', ['name' => $name1_ro])->save();
    $translated_entity = $this->loadEntityByName($entity_type_id, $name1)->getTranslation('ro');
    $this->assertEqual($translated_entity->name->value, $name1_ro, format_string('%entity_type: The translation has been added.', array('%entity_type' => $entity_type_id)));

    $edit['name[0][value]'] = $name2_ro;
    $this->drupalPostForm('ro/' . $entity_type_id . '/manage/' . $entity->id() . '/edit', $edit, t('Save'));
    $translated_entity = $this->loadEntityByName($entity_type_id, $name1)->getTranslation('ro');
    $this->assertTrue($translated_entity, format_string('%entity_type: Modified translation found in the database.', array('%entity_type' => $entity_type_id)));
    $this->assertEqual($translated_entity->name->value, $name2_ro, format_string('%entity_type: The name of the translation has been modified.', array('%entity_type' => $entity_type_id)));

    $this->drupalGet('ro/' . $entity_type_id . '/manage/' . $entity->id() . '/edit');
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete Romanian translation'));
    $entity = $this->loadEntityByName($entity_type_id, $name1);
    $this->assertNotNull($entity, format_string('%entity_type: The original entity still exists.', array('%entity_type' => $entity_type_id)));
    $this->assertFalse($entity->hasTranslation('ro'), format_string('%entity_type: Entity translation does not exist anymore.', array('%entity_type' => $entity_type_id)));
  }

  /**
   * Loads a test entity by name always resetting the storage cache.
   */
  protected function loadEntityByName($entity_type, $name) {
    // Always load the entity from the database to ensure that changes are
    // correctly picked up.
    $entity_storage = $this->container->get('entity.manager')->getStorage($entity_type);
    $entity_storage->resetCache();
    $entities = $entity_storage->loadByProperties(array('name' => $name));
    return $entities ? current($entities) : NULL;
  }

  /**
   * Checks that validation handlers works as expected.
   */
  public function testValidationHandlers() {
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');

    // Check that from-level validation handlers can be defined and can alter
    // the form array.
    $state->set('entity_test.form.validate.test', 'form-level');
    $this->drupalPostForm('entity_test/add', [], 'Save');
    $this->assertTrue($state->get('entity_test.form.validate.result'), 'Form-level validation handlers behave correctly.');

    // Check that defining a button-level validation handler causes an exception
    // to be thrown.
    $state->set('entity_test.form.validate.test', 'button-level');
    $this->drupalPostForm('entity_test/add', [], 'Save');
    $this->assertEqual($state->get('entity_test.form.save.exception'), 'Drupal\Core\Entity\EntityStorageException: Entity validation was skipped.', 'Button-level validation handlers behave correctly.');
  }

}
