<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Plugin\Validation\Constraint\EntityTestCompositeConstraint;
use Drupal\Core\Validation\ConstraintViolation;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityConstraintViolationList
 * @group entity
 */
class EntityConstraintViolationListTest extends UnitTestCase {

  /**
   * @covers ::filterByFields
   */
  public function testFilterByFields() {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithoutCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFields(['name']), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertArrayEquals(array_values(iterator_to_array($constraint_list)), [$violations[2], $violations[3], $violations[4], $violations[5]]);
  }

  /**
   * @covers ::filterByFields
   */
  public function testFilterByFieldsWithCompositeConstraints() {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFields(['name']), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertArrayEquals(array_values(iterator_to_array($constraint_list)), [$violations[2], $violations[3], $violations[4], $violations[5]]);
  }

  /**
   * @covers ::filterByFieldAccess
   */
  public function testFilterByFieldAccess() {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithoutCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFieldAccess($account), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertArrayEquals(array_values(iterator_to_array($constraint_list)), [$violations[2], $violations[3], $violations[4], $violations[5]]);
  }

  /**
   * @covers ::filterByFieldAccess
   */
  public function testFilterByFieldAccessWithCompositeConstraint() {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFieldAccess($account), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertArrayEquals(array_values(iterator_to_array($constraint_list)), [$violations[2], $violations[3], $violations[4], $violations[5]]);
  }

  /**
   * Builds the entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   A fieldable entity.
   */
  protected function setupEntity(AccountInterface $account) {
    $prophecy = $this->prophesize('\Drupal\Core\Field\FieldItemListInterface');
    $prophecy->access('edit', $account)
      ->willReturn(FALSE);
    $name_field_item_list = $prophecy->reveal();

    $prophecy = $this->prophesize('\Drupal\Core\Field\FieldItemListInterface');
    $prophecy->access('edit', $account)
      ->willReturn(TRUE);
    $type_field_item_list = $prophecy->reveal();

    $prophecy = $this->prophesize('\Drupal\Core\Entity\FieldableEntityInterface');
    $prophecy->hasField('name')
      ->willReturn(TRUE);
    $prophecy->hasField('type')
      ->willReturn(TRUE);
    $prophecy->get('name')
      ->willReturn($name_field_item_list);
    $prophecy->get('type')
      ->willReturn($type_field_item_list);

    return $prophecy->reveal();
  }

  /**
   * Builds an entity constraint violation list without composite constraints.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A fieldable entity.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationList
   *   The entity constraint violation list.
   */
  protected function setupConstraintListWithoutCompositeConstraint(FieldableEntityInterface $entity) {
    $violations = [];

    // Add two violations to two specific fields.
    $violations[] = new ConstraintViolation('test name violation', '', [], '', 'name', 'invalid');
    $violations[] = new ConstraintViolation('test name violation2', '', [], '', 'name', 'invalid');

    $violations[] = new ConstraintViolation('test type violation', '', [], '', 'type', 'invalid');
    $violations[] = new ConstraintViolation('test type violation2', '', [], '', 'type', 'invalid');

    // Add two entity level specific violations.
    $violations[] = new ConstraintViolation('test entity violation', '', [], '', '', 'invalid');
    $violations[] = new ConstraintViolation('test entity violation2', '', [], '', '', 'invalid');

    return new EntityConstraintViolationList($entity, $violations);
  }

  /**
   * Builds an entity constraint violation list with composite constraints.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A fieldable entity.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationList
   *   The entity constraint violation list.
   */
  protected function setupConstraintListWithCompositeConstraint(FieldableEntityInterface $entity) {
    $violations = [];

    // Add two violations to two specific fields.
    $violations[] = new ConstraintViolation('test name violation', '', [], '', 'name', 'invalid');
    $violations[] = new ConstraintViolation('test name violation2', '', [], '', 'name', 'invalid');

    $violations[] = new ConstraintViolation('test type violation', '', [], '', 'type', 'invalid');
    $violations[] = new ConstraintViolation('test type violation2', '', [], '', 'type', 'invalid');

    // Add two entity level specific violations with a compound constraint.
    $composite_constraint = new EntityTestCompositeConstraint();
    $violations[] = new ConstraintViolation('test composite violation', '', [], '', '', 'invalid', NULL, NULL, $composite_constraint);
    $violations[] = new ConstraintViolation('test composite violation2', '', [], '', '', 'invalid', NULL, NULL, $composite_constraint);
    return new EntityConstraintViolationList($entity, $violations);
  }

}
