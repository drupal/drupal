<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Entity Validation API.
 *
 * @group Entity
 */
class EntityValidationTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter', 'text', 'language'];

  /**
   * @var string
   */
  protected $entityName;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $entityUser;

  /**
   * @var string
   */
  protected $entityFieldText;

  /**
   * @var array
   */
  protected array $cachedDiscoveries;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')
      ->save();

    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mul_langcode_key');
    $this->installEntitySchema('entity_test_mul_changed');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('entity_test_mulrev_changed');

    // Create the test field.
    $this->container->get('module_handler')->loadInclude('entity_test', 'install');
    entity_test_install();

    // Install required default configuration for filter module.
    $this->installConfig(['system', 'filter']);
  }

  /**
   * Creates a test entity.
   *
   * @param string $entity_type
   *   An entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created test entity.
   */
  protected function createTestEntity($entity_type) {
    $this->entityName = $this->randomMachineName();
    $this->entityUser = $this->createUser();

    // Pass in the value of the name field when creating. With the user
    // field we test setting a field after creation.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create();
    $entity->user_id->target_id = $this->entityUser->id();
    $entity->name->value = $this->entityName;

    // Set a value for the test field.
    if ($entity->hasField('field_test_text')) {
      $this->entityFieldText = $this->randomMachineName();
      $entity->field_test_text->value = $this->entityFieldText;
    }

    return $entity;
  }

  /**
   * Tests validating test entity types.
   */
  public function testValidation() {
    // Ensure that the constraint manager is marked as cached cleared.

    // Use the protected property on the cache_clearer first to check whether
    // the constraint manager is added there.

    // Ensure that the proxy class is initialized, which has the necessary
    // method calls attached.
    \Drupal::service('plugin.cache_clearer');
    $plugin_cache_clearer = \Drupal::service('drupal.proxy_original_service.plugin.cache_clearer');
    $get_cached_discoveries = function () {
      return $this->cachedDiscoveries;
    };
    $get_cached_discoveries = $get_cached_discoveries->bindTo($plugin_cache_clearer, $plugin_cache_clearer);
    $cached_discoveries = $get_cached_discoveries();
    $cached_discovery_classes = [];
    foreach ($cached_discoveries as $cached_discovery) {
      $cached_discovery_classes[] = get_class($cached_discovery);
    }
    $this->assertContains('Drupal\Core\Validation\ConstraintManager', $cached_discovery_classes);

    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->checkValidation($entity_type);
    }
  }

  /**
   * Executes the validation test set for a defined entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function checkValidation($entity_type) {
    $entity = $this->createTestEntity($entity_type);
    $violations = $entity->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passes.');

    // Test triggering a fail for each of the constraints specified.
    $test_entity = clone $entity;
    $test_entity->id->value = -1;
    $violations = $test_entity->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed.');
    $this->assertEquals(t('%name: The integer must be larger or equal to %min.', ['%name' => 'ID', '%min' => 0]), $violations[0]->getMessage());

    $test_entity = clone $entity;
    $test_entity->uuid->value = $this->randomString(129);
    $violations = $test_entity->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed.');
    $this->assertEquals(t('%name: may not be longer than @max characters.', ['%name' => 'UUID', '@max' => 128]), $violations[0]->getMessage());

    $test_entity = clone $entity;
    $langcode_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode');
    $test_entity->{$langcode_key}->value = $this->randomString(13);
    $violations = $test_entity->validate();
    // This should fail on AllowedValues and Length constraints.
    $this->assertEquals(2, $violations->count(), 'Validation failed.');
    $this->assertEquals(t('This value is too long. It should have %limit characters or less.', ['%limit' => '12']), $violations[0]->getMessage());
    $this->assertEquals('The value you selected is not a valid choice.', $violations[1]->getMessage());

    $test_entity = clone $entity;
    $test_entity->type->value = NULL;
    $violations = $test_entity->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed.');
    $this->assertEquals('This value should not be null.', $violations[0]->getMessage());

    $test_entity = clone $entity;
    $test_entity->name->value = $this->randomString(33);
    $violations = $test_entity->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed.');
    $this->assertEquals(t('%name: may not be longer than @max characters.', ['%name' => 'Name', '@max' => 32]), $violations[0]->getMessage());

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEquals($test_entity, $violation->getRoot()->getValue(), 'Violation root is entity.');
    $this->assertEquals('name.0.value', $violation->getPropertyPath(), 'Violation property path is correct.');
    $this->assertEquals($test_entity->name->value, $violation->getInvalidValue(), 'Violation contains invalid value.');

    $test_entity = clone $entity;
    $test_entity->set('user_id', 9999);
    $violations = $test_entity->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed.');
    $this->assertEquals(t('The referenced entity (%type: %id) does not exist.', ['%type' => 'user', '%id' => 9999]), $violations[0]->getMessage());

    $test_entity = clone $entity;
    $test_entity->field_test_text->format = $this->randomString(33);
    $violations = $test_entity->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed.');
    $this->assertEquals('The value you selected is not a valid choice.', $violations[0]->getMessage());

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEquals($test_entity, $violation->getRoot()->getValue(), 'Violation root is entity.');
    $this->assertEquals('field_test_text.0.format', $violation->getPropertyPath(), 'Violation property path is correct.');
    $this->assertEquals($test_entity->field_test_text->format, $violation->getInvalidValue(), 'Violation contains invalid value.');
  }

  /**
   * Tests composite constraints.
   */
  public function testCompositeConstraintValidation() {
    $entity = $this->createTestEntity('entity_test_composite_constraint');
    $violations = $entity->validate();
    $this->assertEquals(0, $violations->count());

    // Trigger violation condition.
    $entity->name->value = 'test';
    $entity->type->value = 'test2';
    $violations = $entity->validate();
    $this->assertEquals(1, $violations->count());

    // Make sure we can determine this is composite constraint.
    $constraint = $violations[0]->getConstraint();
    $this->assertInstanceOf(CompositeConstraintBase::class, $constraint);
    $this->assertEquals('type', $violations[0]->getPropertyPath());

    /** @var \Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase $constraint */
    $this->assertEquals(['name', 'type'], $constraint->coversFields(), 'Information about covered fields can be retrieved.');
  }

  /**
   * Tests the EntityChangedConstraintValidator with multiple translations.
   */
  public function testEntityChangedConstraintOnConcurrentMultilingualEditing() {
    $this->installEntitySchema('entity_test_mulrev_changed');
    $storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    // Create a test entity.
    $entity = $this->createTestEntity('entity_test_mulrev_changed');
    $entity->save();

    $entity->setChangedTime($entity->getChangedTime() - 1);
    $violations = $entity->validate();
    $this->assertEquals(1, $violations->count());
    $this->assertEquals('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.', $violations[0]->getMessage());

    $entity = $storage->loadUnchanged($entity->id());
    $translation = $entity->addTranslation('de');
    $entity->save();

    // Ensure that the new translation has a newer changed timestamp than the
    // default translation.
    $this->assertGreaterThan($entity->getChangedTime(), $translation->getChangedTime());

    // Simulate concurrent form editing by saving the entity with an altered
    // non-translatable field in order for the changed timestamp to be updated
    // across all entity translations.
    $original_entity_time = $entity->getChangedTime();
    $entity->set('not_translatable', $this->randomString());
    $entity->save();
    // Simulate form submission of an uncached form by setting the previous
    // timestamp of an entity translation on the saved entity object. This
    // happens in the entity form API where we put the changed timestamp of
    // the entity in a form hidden value and then set it on the entity which on
    // form submit is loaded from the storage if the form is not yet cached.
    $entity->setChangedTime($original_entity_time);
    // Setting the changed timestamp from the user input on the entity loaded
    // from the storage is used as a prevention from saving a form built with a
    // previous version of the entity and thus reverting changes by other users.
    $violations = $entity->validate();
    $this->assertEquals(1, $violations->count());
    $this->assertEquals('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.', $violations[0]->getMessage());
  }

}
