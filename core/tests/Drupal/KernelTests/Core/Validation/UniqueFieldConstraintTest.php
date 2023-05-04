<?php

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestStringId;
use Drupal\KernelTests\KernelTestBase;
use Drupal\TestTools\Random;

/**
 * Tests the unique field value validation constraint.
 *
 * @coversDefaultClass \Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator
 *
 * @group Validation
 */
class UniqueFieldConstraintTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'unique_field_constraint_test',
    'user',
  ];

  /**
   * Tests cases where the validation passes for entities with string IDs.
   *
   * @covers ::validate
   */
  public function testEntityWithStringId() {
    $this->installEntitySchema('entity_test_string_id');

    EntityTestStringId::create([
      'id' => 'foo',
      'name' => $this->randomString(),
    ])->save();

    // Reload the entity.
    $entity = EntityTestStringId::load('foo');

    // Check that an existing entity validates when the value is preserved.
    $violations = $entity->name->validate();
    $this->assertCount(0, $violations);

    // Create a new entity with a different ID and a different field value.
    EntityTestStringId::create([
      'id' => 'bar',
      'name' => $this->randomString(),
    ]);

    // Check that a new entity with a different field value validates.
    $violations = $entity->name->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests cases when validation raises violations for entities with string IDs.
   *
   * @param string|int|null $id
   *   The entity ID.
   *
   * @covers ::validate
   *
   * @dataProvider providerTestEntityWithStringIdWithViolation
   */
  public function testEntityWithStringIdWithViolation($id) {
    $this->installEntitySchema('entity_test_string_id');

    $value = $this->randomString();

    EntityTestStringId::create([
      'id' => 'first_entity',
      'name' => $value,
    ])->save();

    $entity = EntityTestStringId::create([
      'id' => $id,
      'name' => $value,
    ]);
    /** @var \Symfony\Component\Validator\ConstraintViolationList $violations */
    $violations = $entity->get('name')->validate();

    $message = new FormattableMarkup('A @entity_type with @field_name %value already exists.', [
      '%value' => $value,
      '@entity_type' => $entity->getEntityType()->getSingularLabel(),
      '@field_name' => 'Name',
    ]);

    // Check that the validation has created the appropriate violation.
    $this->assertCount(1, $violations);
    $this->assertEquals($message, $violations[0]->getMessage());
  }

  /**
   * Data provider for ::testEntityWithStringIdWithViolation().
   *
   * @return array
   *   An array of test cases.
   *
   * @see self::testEntityWithStringIdWithViolation()
   */
  public static function providerTestEntityWithStringIdWithViolation() {
    return [
      'without an id' => [NULL],
      'zero as integer' => [0],
      'zero as string' => ["0"],
      'non-zero as integer' => [mt_rand(1, 127)],
      'non-zero as string' => [(string) mt_rand(1, 127)],
      'alphanumeric' => [Random::machineName()],
    ];
  }

  /**
   * Tests validating inaccessible entities.
   *
   * The unique_field_constraint_test_entity_test_access() function
   * forbids 'view' access to entity_test entities.
   *
   * @covers ::validate
   */
  public function testViolationDespiteNoAccess() {
    $this->installEntitySchema('entity_test');

    // Create and save an entity with a given field value in the field that has
    // the unique constraint.
    EntityTest::create([
      'name' => 'A totally unique entity name',
    ])->save();

    // Prepare a second entity with the same value in the unique field.
    $entity = EntityTest::create([
      'name' => 'A totally unique entity name',
    ]);
    /** @var \Symfony\Component\Validator\ConstraintViolationList $violations */
    $violations = $entity->get('name')->validate();

    $message = new FormattableMarkup('A @entity_type with @field_name %value already exists.', [
      '%value' => 'A totally unique entity name',
      '@entity_type' => $entity->getEntityType()->getSingularLabel(),
      '@field_name' => 'Name',
    ]);

    // Check that the validation has created the appropriate violation.
    $this->assertCount(1, $violations);
    $this->assertEquals($message, $violations[0]->getMessage());
  }

}
