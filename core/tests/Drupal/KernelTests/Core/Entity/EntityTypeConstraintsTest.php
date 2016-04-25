<?php

namespace Drupal\KernelTests\Core\Entity;

/**
 * Tests entity level validation constraints.
 *
 * @group Entity
 */
class EntityTypeConstraintsTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test_constraints');
  }

  /**
   * Tests defining entity constraints via entity type annotations and hooks.
   */
  public function testConstraintDefinition() {
    // Test reading the annotation. There should be two constraints, the defined
    // constraint and the automatically added EntityChanged constraint.
    $entity_type = $this->entityManager->getDefinition('entity_test_constraints');
    $default_constraints = ['NotNull' => [], 'EntityChanged' => NULL];
    $this->assertEqual($default_constraints, $entity_type->getConstraints());

    // Enable our test module and test extending constraints.
    $this->enableModules(['entity_test_constraints']);
    $this->container->get('module_handler')->resetImplementations();

    $extra_constraints = ['Test' => []];
    $this->state->set('entity_test_constraints.build', $extra_constraints);
    // Re-fetch the entity manager from the new container built after the new
    // modules were enabled.
    $this->entityManager = $this->container->get('entity.manager');
    $this->entityManager->clearCachedDefinitions();
    $entity_type = $this->entityManager->getDefinition('entity_test_constraints');
    $this->assertEqual($default_constraints + $extra_constraints, $entity_type->getConstraints());

    // Test altering constraints.
    $altered_constraints = ['Test' => [ 'some_setting' => TRUE]];
    $this->state->set('entity_test_constraints.alter', $altered_constraints);
    // Clear the cache in state instance in the Drupal container, so it can pick
    // up the modified value.
    \Drupal::state()->resetCache();
    $this->entityManager->clearCachedDefinitions();
    $entity_type = $this->entityManager->getDefinition('entity_test_constraints');
    $this->assertEqual($altered_constraints, $entity_type->getConstraints());
  }

  /**
   * Tests entity constraints are validated.
   */
  public function testConstraintValidation() {
    $entity = $this->entityManager->getStorage('entity_test_constraints')->create();
    $entity->user_id->target_id = 0;
    $violations = $entity->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passed.');
    $entity->save();
    $entity->changed->value = REQUEST_TIME - 86400;
    $violations = $entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.'));
  }

}
