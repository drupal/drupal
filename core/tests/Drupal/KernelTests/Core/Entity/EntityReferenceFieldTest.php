<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\entity_test\Entity\EntityTestStringId;

/**
 * Tests for the entity reference field.
 *
 * @group Entity
 */
class EntityReferenceFieldTest extends EntityKernelTestBase {

  use SchemaCheckTestTrait;
  use EntityReferenceTestTrait;

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The entity type that is being referenced.
   *
   * @var string
   */
  protected $referencedEntityType = 'entity_test_rev';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_reference_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    // Create a field.
    $this->createEntityReferenceField(
      $this->entityType,
      $this->bundle,
      $this->fieldName,
      'Field test',
      $this->referencedEntityType,
      'default',
      array('target_bundles' => array($this->bundle)),
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

  }

  /**
   * Tests reference field validation.
   */
  public function testEntityReferenceFieldValidation() {
    // Test a valid reference.
    $referenced_entity = $this->container->get('entity_type.manager')
      ->getStorage($this->referencedEntityType)
      ->create(array('type' => $this->bundle));
    $referenced_entity->save();

    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(array('type' => $this->bundle));
    $entity->{$this->fieldName}->target_id = $referenced_entity->id();
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passes.');

    // Test an invalid reference.
    $entity->{$this->fieldName}->target_id = 9999;
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual($violations->count(), 1, 'Validation throws a violation.');
    $this->assertEqual($violations[0]->getMessage(), t('The referenced entity (%type: %id) does not exist.', array('%type' => $this->referencedEntityType, '%id' => 9999)));

    // Test a non-referenceable bundle.
    entity_test_create_bundle('non_referenceable', NULL, $this->referencedEntityType);
    $referenced_entity = entity_create($this->referencedEntityType, array('type' => 'non_referenceable'));
    $referenced_entity->save();
    $entity->{$this->fieldName}->target_id = $referenced_entity->id();
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual($violations->count(), 1, 'Validation throws a violation.');
    $this->assertEqual($violations[0]->getMessage(), t('This entity (%type: %id) cannot be referenced.', array('%type' => $this->referencedEntityType, '%id' => $referenced_entity->id())));
  }

  /**
   * Tests the multiple target entities loader.
   */
  public function testReferencedEntitiesMultipleLoad() {
    // Create the parent entity.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(array('type' => $this->bundle));

    // Create three target entities and attach them to parent field.
    $target_entities = array();
    $reference_field = array();
    for ($i = 0; $i < 3; $i++) {
      $target_entity = $this->container->get('entity_type.manager')
        ->getStorage($this->referencedEntityType)
        ->create(array('type' => $this->bundle));
      $target_entity->save();
      $target_entities[] = $target_entity;
      $reference_field[]['target_id'] = $target_entity->id();
    }

    // Also attach a non-existent entity and a NULL target id.
    $reference_field[3]['target_id'] = 99999;
    $target_entities[3] = NULL;
    $reference_field[4]['target_id'] = NULL;
    $target_entities[4] = NULL;

    // Attach the first created target entity as the sixth item ($delta == 5) of
    // the parent entity field. We want to test the case when the same target
    // entity is referenced twice (or more times) in the same entity reference
    // field.
    $reference_field[5] = $reference_field[0];
    $target_entities[5] = $target_entities[0];

    // Create a new target entity that is not saved, thus testing the
    // "autocreate" feature.
    $target_entity_unsaved = $this->container->get('entity_type.manager')
      ->getStorage($this->referencedEntityType)
      ->create(array('type' => $this->bundle, 'name' => $this->randomString()));
    $reference_field[6]['entity'] = $target_entity_unsaved;
    $target_entities[6] = $target_entity_unsaved;

    // Set the field value.
    $entity->{$this->fieldName}->setValue($reference_field);

    // Load the target entities using EntityReferenceField::referencedEntities().
    $entities = $entity->{$this->fieldName}->referencedEntities();

    // Test returned entities:
    // - Deltas must be preserved.
    // - Non-existent entities must not be retrieved in target entities result.
    foreach ($target_entities as $delta => $target_entity) {
      if (!empty($target_entity)) {
        if (!$target_entity->isNew()) {
          // There must be an entity in the loaded set having the same id for
          // the same delta.
          $this->assertEqual($target_entity->id(), $entities[$delta]->id());
        }
        else {
          // For entities that were not yet saved, there must an entity in the
          // loaded set having the same label for the same delta.
          $this->assertEqual($target_entity->label(), $entities[$delta]->label());
        }
      }
      else {
        // A non-existent or NULL entity target id must not return any item in
        // the target entities set.
        $this->assertFalse(isset($entities[$delta]));
      }
    }
  }

  /**
   * Tests referencing entities with string IDs.
   */
  public function testReferencedEntitiesStringId() {
    $field_name = 'entity_reference_string_id';
    $this->installEntitySchema('entity_test_string_id');
    $this->createEntityReferenceField(
      $this->entityType,
      $this->bundle,
      $field_name,
      'Field test',
      'entity_test_string_id',
      'default',
      array('target_bundles' => array($this->bundle)),
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    // Create the parent entity.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(array('type' => $this->bundle));

    // Create the default target entity.
    $target_entity = EntityTestStringId::create([
      'id' => $this->randomString(),
      'type' => $this->bundle
    ]);
    $target_entity->save();

    // Set the field value.
    $entity->{$field_name}->setValue(array(array('target_id' => $target_entity->id())));

    // Load the target entities using EntityReferenceField::referencedEntities().
    $entities = $entity->{$field_name}->referencedEntities();
    $this->assertEqual($entities[0]->id(), $target_entity->id());

    // Test that a string ID works as a default value and the field's config
    // schema is correct.
    $field = FieldConfig::loadByName($this->entityType, $this->bundle, $field_name);
    $field->setDefaultValue($target_entity->id());
    $field->save();
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'field.field.' . $field->id(), $field->toArray());

    // Test that the default value works.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(array('type' => $this->bundle));
    $entities = $entity->{$field_name}->referencedEntities();
    $this->assertEqual($entities[0]->id(), $target_entity->id());
  }

  /**
   * Tests all the possible ways to autocreate an entity via the API.
   */
  function testAutocreateApi() {
    $entity = $this->entityManager
      ->getStorage($this->entityType)
      ->create(array('name' => $this->randomString()));

    // Test content entity autocreation.
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->set('user_id', $user);
    });
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->set('user_id', $user, FALSE);
    });
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->user_id->setValue($user);
    });
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->user_id[0]->get('entity')->setValue($user);
    });
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->user_id->setValue(array('entity' => $user, 'target_id' => NULL));
    });
    try {
      $message = 'Setting both the entity and an invalid target_id property fails.';
      $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
        $user->save();
        $entity->user_id->setValue(array('entity' => $user, 'target_id' => $this->generateRandomEntityId()));
      });
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->user_id = $user;
    });
    $this->assertUserAutocreate($entity, function(EntityInterface $entity, UserInterface $user) {
      $entity->user_id->entity = $user;
    });

    // Test config entity autocreation.
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->set('user_role', $role);
    });
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->set('user_role', $role, FALSE);
    });
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->user_role->setValue($role);
    });
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->user_role[0]->get('entity')->setValue($role);
    });
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->user_role->setValue(array('entity' => $role, 'target_id' => NULL));
    });
    try {
      $message = 'Setting both the entity and an invalid target_id property fails.';
      $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
        $role->save();
        $entity->user_role->setValue(array('entity' => $role, 'target_id' => $this->generateRandomEntityId(TRUE)));
      });
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->user_role = $role;
    });
    $this->assertUserRoleAutocreate($entity, function(EntityInterface $entity, RoleInterface $role) {
      $entity->user_role->entity = $role;
    });

    // Test target entity saving after setting it as new.
    $storage = $this->entityManager->getStorage('user');
    $user_id = $this->generateRandomEntityId();
    $user = $storage->create(array('uid' => $user_id, 'name' => $this->randomString()));
    $entity->user_id = $user;
    $user->save();
    $entity->save();
    $this->assertEqual($entity->user_id->target_id, $user->id());
  }

  /**
   * Asserts that the setter callback performs autocreation for users.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The referencing entity.
   * @param $setter_callback
   *   A callback setting the target entity on the referencing entity.
   *
   * @return bool
   *   TRUE if the user was autocreated, FALSE otherwise.
   */
  protected function assertUserAutocreate(EntityInterface $entity, $setter_callback) {
    $storage = $this->entityManager->getStorage('user');
    $user_id = $this->generateRandomEntityId();
    $user = $storage->create(array('uid' => $user_id, 'name' => $this->randomString()));
    $setter_callback($entity, $user);
    $entity->save();
    $storage->resetCache();
    $user = User::load($user_id);
    return $this->assertEqual($entity->user_id->target_id, $user->id());
  }

  /**
   * Asserts that the setter callback performs autocreation for user roles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The referencing entity.
   * @param $setter_callback
   *   A callback setting the target entity on the referencing entity.
   *
   * @return bool
   *   TRUE if the user was autocreated, FALSE otherwise.
   */
  protected function assertUserRoleAutocreate(EntityInterface $entity, $setter_callback) {
    $storage = $this->entityManager->getStorage('user_role');
    $role_id = $this->generateRandomEntityId(TRUE);
    $role = $storage->create(array('id' => $role_id, 'label' => $this->randomString()));
    $setter_callback($entity, $role);
    $entity->save();
    $storage->resetCache();
    $role = Role::load($role_id);
    return $this->assertEqual($entity->user_role->target_id, $role->id());
  }

  /**
   * Tests that the target entity is not unnecessarily loaded.
   */
  public function testTargetEntityNoLoad() {
    // Setup a test entity type with an entity reference field to itself. We use
    // a special storage class throwing exceptions when a load operation is
    // triggered to be able to detect them.
    $entity_type = clone $this->entityManager->getDefinition('entity_test_update');
    $entity_type->setHandlerClass('storage', '\Drupal\entity_test\EntityTestNoLoadStorage');
    $this->state->set('entity_test_update.entity_type', $entity_type);
    $definitions = array(
      'target_reference' => BaseFieldDefinition::create('entity_reference')
        ->setSetting('target_type', $entity_type->id())
        ->setSetting('handler', 'default')
    );
    $this->state->set('entity_test_update.additional_base_field_definitions', $definitions);
    $this->entityManager->clearCachedDefinitions();
    $this->installEntitySchema($entity_type->id());

    // Create the target entity.
    $storage = $this->entityManager->getStorage($entity_type->id());
    $target_id = $this->generateRandomEntityId();
    $target = $storage->create(array('id' => $target_id, 'name' => $this->randomString()));
    $target->save();
    $this->assertEqual($target_id, $target->id(), 'The target entity has a random identifier.');

    // Check that populating the reference with an existing target id does not
    // trigger a load operation.
    $message = 'The target entity was not loaded.';
    try {
      $entity = $this->entityManager
        ->getStorage($entity_type->id())
        ->create(array('name' => $this->randomString()));
      $entity->target_reference = $target_id;
      $this->pass($message);
    }
    catch (EntityStorageException $e) {
      $this->fail($message);
    }

    // Check that the storage actually triggers the expected exception when
    // trying to load the target entity.
    $message = 'An exception is thrown when trying to load the target entity';
    try {
      $storage->load($target_id);
      $this->fail($message);
    }
    catch (EntityStorageException $e) {
      $this->pass($message);
    }
  }

  /**
   * Tests the dependencies entity reference fields are created with.
   */
  public function testEntityReferenceFieldDependencies() {
    $field_name = 'user_reference_field';
    $entity_type = 'entity_test';

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'type' => 'entity_reference',
      'entity_type' => $entity_type,
      'settings' => [
        'target_type' => 'user',
      ],
    ]);
    $field_storage->save();
    $this->assertEqual(['module' => ['entity_test', 'user']], $field_storage->getDependencies());

    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => 'entity_test',
      'label' => $field_name,
      'settings' => [
        'handler' => 'default',
      ],
    ]);
    $field->save();
    $this->assertEqual(['config' => ['field.storage.entity_test.user_reference_field'], 'module' => ['entity_test']], $field->getDependencies());
  }

}
