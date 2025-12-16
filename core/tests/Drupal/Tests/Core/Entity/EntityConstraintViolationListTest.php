<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityConstraintViolationList;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Plugin\Validation\Constraint\EntityTestCompositeConstraint;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests Drupal\Core\Entity\EntityConstraintViolationList.
 */
#[CoversClass(EntityConstraintViolationList::class)]
#[Group('entity')]
class EntityConstraintViolationListTest extends UnitTestCase {

  /**
   * Tests filter by fields.
   *
   * @legacy-covers ::filterByFields
   */
  public function testFilterByFields(): void {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithoutCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFields(['name']), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertEquals(array_values(iterator_to_array($constraint_list)), [
      $violations[2],
      $violations[3],
      $violations[4],
      $violations[5],
    ]);
  }

  /**
   * Tests filter by fields with composite constraints.
   *
   * @legacy-covers ::filterByFields
   */
  public function testFilterByFieldsWithCompositeConstraints(): void {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFields(['name']), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertEquals(array_values(iterator_to_array($constraint_list)), [
      $violations[2],
      $violations[3],
      $violations[4],
      $violations[5],
    ]);
  }

  /**
   * Tests filter by field access.
   *
   * @legacy-covers ::filterByFieldAccess
   */
  public function testFilterByFieldAccess(): void {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithoutCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFieldAccess($account), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertEquals(array_values(iterator_to_array($constraint_list)), [
      $violations[2],
      $violations[3],
      $violations[4],
      $violations[5],
    ]);
  }

  /**
   * Tests filter by field access with composite constraint.
   *
   * @legacy-covers ::filterByFieldAccess
   */
  public function testFilterByFieldAccessWithCompositeConstraint(): void {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $this->assertSame($constraint_list->filterByFieldAccess($account), $constraint_list);
    $this->assertCount(4, $constraint_list);
    $this->assertEquals(array_values(iterator_to_array($constraint_list)), [
      $violations[2],
      $violations[3],
      $violations[4],
      $violations[5],
    ]);
  }

  /**
   * Tests find by codes.
   *
   * @legacy-covers ::findByCodes
   */
  public function testFindByCodes(): void {
    $account = $this->prophesize('\Drupal\Core\Session\AccountInterface')->reveal();
    $entity = $this->setupEntity($account);

    $constraint_list = $this->setupConstraintListWithoutCompositeConstraint($entity);
    $violations = iterator_to_array($constraint_list);

    $codes = ['test-code-violation-name', 'test-code-violation2-name'];
    $actual = $constraint_list->findByCodes($codes);
    $this->assertCount(2, $actual);
    $this->assertEquals(iterator_to_array($actual), [$violations[0], $violations[1]]);
  }

  /**
   * Builds the entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   A fieldable entity.
   */
  protected function setupEntity(AccountInterface $account): FieldableEntityInterface {
    $name_field_item_list = $this->prophesize(FieldItemListInterface::class);
    $name_field_item_list->access('edit', $account)
      ->willReturn(FALSE);

    $type_field_item_list = $this->prophesize(FieldItemListInterface::class);
    $type_field_item_list->access('edit', $account)
      ->willReturn(TRUE);

    $prophecy = $this->prophesize(FieldableEntityInterface::class);
    $prophecy->hasField('name')
      ->willReturn(TRUE);
    $prophecy->hasField('type')
      ->willReturn(TRUE);
    $prophecy->get('name')
      ->willReturn($name_field_item_list->reveal());
    $prophecy->get('type')
      ->willReturn($type_field_item_list->reveal());

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
  protected function setupConstraintListWithoutCompositeConstraint(FieldableEntityInterface $entity): EntityConstraintViolationList {
    $violations = [];

    // Add two violations to two specific fields.
    $violations[] = new ConstraintViolation('test name violation', '', [], '', 'name', 'invalid', NULL, 'test-code-violation-name');
    $violations[] = new ConstraintViolation('test name violation2', '', [], '', 'name', 'invalid', NULL, 'test-code-violation2-name');

    $violations[] = new ConstraintViolation('test type violation', '', [], '', 'type', 'invalid', NULL, 'test-code-violation-type');
    $violations[] = new ConstraintViolation('test type violation2', '', [], '', 'type', 'invalid', NULL, 'test-code-violation2-type');

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
  protected function setupConstraintListWithCompositeConstraint(FieldableEntityInterface $entity): EntityConstraintViolationList {
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
