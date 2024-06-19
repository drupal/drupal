<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the entity form.
 *
 * @group Entity
 * @group #slow
 */
class EntityFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The current user of the test.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser([
      'administer entity_test content',
      'view test entity',
    ]);
    $this->drupalLogin($this->webUser);

    // Add a language.
    ConfigurableLanguage::createFromLangcode('ro')->save();
  }

  /**
   * Tests basic form CRUD functionality.
   */
  public function testFormCRUD(): void {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestFormCRUD($entity_type);
    }
  }

  /**
   * Tests basic multilingual form CRUD functionality.
   */
  public function testMultilingualFormCRUD(): void {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_MULTILINGUAL) as $entity_type) {
      $this->doTestMultilingualFormCRUD($entity_type);
    }
  }

  /**
   * Tests hook_entity_form_mode_alter() and hook_ENTITY_TYPE_form_mode_alter().
   *
   * @see entity_test_entity_form_mode_alter()
   * @see entity_test_entity_test_form_mode_alter()
   */
  public function testEntityFormModeAlter(): void {
    // Create compact entity display.
    EntityFormMode::create([
      'id' => 'entity_test.compact',
      'label' => 'Compact',
      'targetEntityType' => 'entity_test',
    ])->save();
    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'compact',
      'status' => TRUE,
    ])->removeComponent('field_test_text')->save();

    // The field should be available on default form mode.
    $entity1 = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity1->save();
    $this->drupalGet($entity1->toUrl('edit-form'));
    $this->assertSession()->elementExists('css', 'input[name="field_test_text[0][value]"]');

    // The field should be hidden on compact form mode.
    // See: entity_test_entity_form_mode_alter().
    $entity2 = EntityTest::create([
      'name' => 'compact_form_mode',
    ]);
    $entity2->save();
    $this->drupalGet($entity2->toUrl('edit-form'));
    $this->assertSession()->elementNotExists('css', 'input[name="field_test_text[0][value]"]');

    $entity3 = EntityTest::create([
      'name' => 'test_entity_type_form_mode_alter',
    ]);
    $entity3->save();
    $this->drupalGet($entity3->toUrl('edit-form'));
    $this->assertSession()->elementNotExists('css', 'input[name="field_test_text[0][value]"]');
  }

  /**
   * Tests hook_entity_form_display_alter().
   *
   * Verify that the altered field has the correct size value.
   *
   * @see entity_test_entity_form_display_alter()
   */
  public function testEntityFormDisplayAlter(): void {
    $this->drupalGet('entity_test/add');
    $altered_field = $this->assertSession()->fieldExists('field_test_text[0][value]');
    $this->assertEquals(42, $altered_field->getAttribute('size'));
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

    $edit = [
      'name[0][value]' => $name1,
      'field_test_text[0][value]' => $this->randomMachineName(16),
    ];

    $this->drupalGet($entity_type . '/add');
    $this->submitForm($edit, 'Save');
    $entity = $this->loadEntityByName($entity_type, $name1);
    $this->assertNotNull($entity, "$entity_type: Entity found in the database.");

    $edit['name[0][value]'] = $name2;
    $this->drupalGet($entity_type . '/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $entity = $this->loadEntityByName($entity_type, $name1);
    $this->assertNull($entity, "$entity_type: The entity has been modified.");
    $entity = $this->loadEntityByName($entity_type, $name2);
    $this->assertNotNull($entity, "$entity_type: Modified entity found in the database.");
    $this->assertNotEquals($name1, $entity->name->value, "$entity_type: The entity name has been modified.");

    $this->drupalGet($entity_type . '/manage/' . $entity->id() . '/edit');
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');
    $entity = $this->loadEntityByName($entity_type, $name2);
    $this->assertNull($entity, "$entity_type: Entity not found in the database.");
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

    $edit = [
      'name[0][value]' => $name1,
      'field_test_text[0][value]' => $this->randomMachineName(16),
    ];

    $this->drupalGet($entity_type_id . '/add');
    $this->submitForm($edit, 'Save');
    $entity = $this->loadEntityByName($entity_type_id, $name1);
    $this->assertNotNull($entity, "$entity_type_id: Entity found in the database.");

    // Add a translation to the newly created entity without using the Content
    // translation module.
    $entity->addTranslation('ro', ['name' => $name1_ro])->save();
    $translated_entity = $this->loadEntityByName($entity_type_id, $name1)->getTranslation('ro');
    $this->assertEquals($name1_ro, $translated_entity->name->value, "$entity_type_id: The translation has been added.");

    $edit['name[0][value]'] = $name2_ro;
    $this->drupalGet('ro/' . $entity_type_id . '/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $translated_entity = $this->loadEntityByName($entity_type_id, $name1)->getTranslation('ro');
    $this->assertNotNull($translated_entity, "$entity_type_id: Modified translation found in the database.");
    $this->assertEquals($name2_ro, $translated_entity->name->value, "$entity_type_id: The name of the translation has been modified.");

    $this->drupalGet('ro/' . $entity_type_id . '/manage/' . $entity->id() . '/edit');
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete Romanian translation');
    $entity = $this->loadEntityByName($entity_type_id, $name1);
    $this->assertNotNull($entity, "$entity_type_id: The original entity still exists.");
    $this->assertFalse($entity->hasTranslation('ro'), "$entity_type_id: Entity translation does not exist anymore.");
  }

  /**
   * Loads a test entity by name always resetting the storage cache.
   */
  protected function loadEntityByName($entity_type, $name) {
    // Always load the entity from the database to ensure that changes are
    // correctly picked up.
    $entity_storage = $this->container->get('entity_type.manager')->getStorage($entity_type);
    $entity_storage->resetCache();
    $entities = $entity_storage->loadByProperties(['name' => $name]);
    return $entities ? current($entities) : NULL;
  }

  /**
   * Checks that validation handlers works as expected.
   */
  public function testValidationHandlers(): void {
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');

    // Check that from-level validation handlers can be defined and can alter
    // the form array.
    $state->set('entity_test.form.validate.test', 'form-level');
    $this->drupalGet('entity_test/add');
    $this->submitForm([], 'Save');
    $this->assertTrue($state->get('entity_test.form.validate.result'), 'Form-level validation handlers behave correctly.');

    // Check that defining a button-level validation handler causes an exception
    // to be thrown.
    $state->set('entity_test.form.validate.test', 'button-level');
    $this->drupalGet('entity_test/add');
    $this->submitForm([], 'Save');
    $this->assertEquals('Drupal\\Core\\Entity\\EntityStorageException: Entity validation is required, but was skipped.', $state->get('entity_test.form.save.exception'), 'Button-level validation handlers behave correctly.');
  }

  /**
   * Tests the route add-page with multiple parameters.
   */
  public function testAddPageWithMultipleParameters(): void {
    $this->drupalGet('entity_test_add_page/' . $this->webUser->id() . '/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add entity test route add page');
  }

}
