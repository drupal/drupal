<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests validation constraints for ValidReferenceConstraintValidator.
 *
 * @group Validation
 */
class ValidReferenceConstraintValidatorTest extends EntityKernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig('node');
    $this->typedData = $this->container->get('typed_data_manager');

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->createContentType(['type' => 'page', 'name' => 'Basic page']);
  }

  /**
   * Tests the ValidReferenceConstraintValidator.
   */
  public function testValidation() {
    // Create a test entity to be referenced.
    $entity = $this->createUser();
    // By default entity references already have the ValidReference constraint.
    $definition = BaseFieldDefinition::create('entity_reference')
      ->setSettings(['target_type' => 'user']);

    $typed_data = $this->typedData->create($definition, ['target_id' => $entity->id()]);
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passed for correct value.');

    // NULL is also considered a valid reference.
    $typed_data = $this->typedData->create($definition, ['target_id' => NULL]);
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passed for correct value.');

    $typed_data = $this->typedData->create($definition, ['target_id' => $entity->id()]);
    // Delete the referenced entity.
    $entity->delete();
    $violations = $typed_data->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Validation failed for incorrect value.');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEquals(sprintf('The referenced entity (user: %s) does not exist.', $entity->id()), $violation->getMessage(), 'The message for invalid value is correct.');
    $this->assertEquals($typed_data, $violation->getRoot(), 'Violation root is correct.');
  }

  /**
   * Tests the validation of pre-existing items in an entity reference field.
   */
  public function testPreExistingItemsValidation() {
    // Create two types of users, with and without access to bypass content
    // access.
    /** @var \Drupal\user\RoleInterface $role_with_access */
    $role_with_access = Role::create(['id' => 'role_with_access', 'label' => 'With access']);
    $role_with_access->grantPermission('access content');
    $role_with_access->grantPermission('bypass node access');
    $role_with_access->save();

    /** @var \Drupal\user\RoleInterface $role_without_access */
    $role_without_access = Role::create(['id' => 'role_without_access', 'label' => 'Without access']);
    $role_without_access->grantPermission('access content');
    $role_without_access->save();

    $user_with_access = User::create(['roles' => ['role_with_access']]);
    $user_without_access = User::create(['roles' => ['role_without_access']]);

    // Add an entity reference field.
    $this->createEntityReferenceField(
      'entity_test',
      'entity_test',
      'field_test',
      'Field test',
      'node',
      'default',
      ['target_bundles' => ['article', 'page']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create four test nodes.
    $published_node = Node::create([
      'title' => 'Test published node',
      'type' => 'article',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $published_node->save();

    $unpublished_node = Node::create([
      'title' => 'Test unpublished node',
      'type' => 'article',
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $unpublished_node->save();

    $different_bundle_node = Node::create([
      'title' => 'Test page node',
      'type' => 'page',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $different_bundle_node->save();

    $deleted_node = Node::create([
      'title' => 'Test deleted node',
      'type' => 'article',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $deleted_node->save();

    $referencing_entity = EntityTest::create([
      'field_test' => [
        ['entity' => $published_node],
        ['entity' => $unpublished_node],
        ['entity' => $different_bundle_node],
        ['entity' => $deleted_node],
      ],
    ]);

    // Check that users with access are able pass the validation for fields
    // without pre-existing content.
    $this->container->get('account_switcher')->switchTo($user_with_access);

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(0, $violations);

    // Check that users without access are not able pass the validation for
    // fields without pre-existing content.
    $this->container->get('account_switcher')->switchTo($user_without_access);

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(sprintf('This entity (node: %s) cannot be referenced.', $unpublished_node->id()), $violations[0]->getMessage());

    // Now save the referencing entity which will create a pre-existing state
    // for it and repeat the checks. This time, the user without access should
    // be able to pass the validation as well because it's not changing the
    // pre-existing state.
    $referencing_entity->save();

    $this->container->get('account_switcher')->switchTo($user_with_access);

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(0, $violations);

    // Check that users without access are able pass the validation for fields
    // with pre-existing content.
    $this->container->get('account_switcher')->switchTo($user_without_access);

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(0, $violations);

    // Re-save the referencing entity and check that the referenced entity is
    // not affected.
    $referencing_entity->name->value = $this->randomString();
    $referencing_entity->save();
    $this->assertEquals($published_node->id(), $referencing_entity->field_test[0]->target_id);
    $this->assertEquals($unpublished_node->id(), $referencing_entity->field_test[1]->target_id);
    $this->assertEquals($different_bundle_node->id(), $referencing_entity->field_test[2]->target_id);
    $this->assertEquals($deleted_node->id(), $referencing_entity->field_test[3]->target_id);

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(0, $violations);

    // Remove one of the referenceable bundles and check that a pre-existing node
    // of that bundle can not be referenced anymore.
    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_test');
    $field->setSetting('handler_settings', ['target_bundles' => ['article']]);
    $field->save();
    $referencing_entity = $this->reloadEntity($referencing_entity);

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(sprintf('This entity (node: %s) cannot be referenced.', $different_bundle_node->id()), $violations[0]->getMessage());

    // Delete the last node and check that the pre-existing reference is not
    // valid anymore.
    $deleted_node->delete();

    $violations = $referencing_entity->field_test->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals(sprintf('This entity (node: %s) cannot be referenced.', $different_bundle_node->id()), $violations[0]->getMessage());
    $this->assertEquals(sprintf('The referenced entity (node: %s) does not exist.', $deleted_node->id()), $violations[1]->getMessage());
  }

}
