<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTestUniqueConstraint;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the unique field value validation constraint.
 *
 * @coversDefaultClass \Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator
 *
 * @group Validation
 */
class UniqueValuesConstraintValidatorTest extends KernelTestBase {
  use UserCreationTrait;

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
  protected function setUp(): void {
    parent::setUp();
    $this->setUpCurrentUser();
    $this->installEntitySchema('entity_test_unique_constraint');
  }

  /**
   * Tests the UniqueField validation constraint validator.
   *
   * Case 1. Try to create another entity with existing value for unique field.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @covers ::validate
   */
  public function testValidation(): void {
    // Create entity with two values for the testing field.
    $definition = [
      'id' => (int) rand(0, getrandmax()),
      'user_id' => 0,
      'field_test_text' => [
        'text1',
        'text2',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
    $entity->save();
    $violations = $entity->validate();
    $this->assertCount(0, $violations);

    // Create another entity with two values for the testing field.
    $definition = [
      'id' => (int) rand(0, getrandmax()),
      'user_id' => 0,
      'field_test_text' => [
        'text3',
        'text4',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
    $entity->save();
    $violations = $entity->validate();
    $this->assertCount(0, $violations);

    // Add existing value.
    $value = 'text1';
    $entity->get('field_test_text')->appendItem($value);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_text.2', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $value), $violations[0]->getMessage());

    // Create another entity with two values, but one value is existing.
    $definition = [
      'id' => (int) rand(0, getrandmax()),
      'user_id' => 0,
      'field_test_text' => [
        'text5',
        'text1',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_text.1', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][1]), $violations[0]->getMessage());

  }

  /**
   * Tests the UniqueField validation constraint validator for entity reference fields.
   *
   * Case 2. Try to create another entity with existing reference for unique field.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @covers ::validate
   */
  public function testValidationReference(): void {

    $users = [];
    for ($i = 0; $i <= 5; $i++) {
      $users[$i] = $this->createUser();
    }

    // Create new entity with two identical references.
    $definition = [
      'user_id' => 0,
      'field_test_reference' => [
        $users[0]->id(),
        $users[0]->id(),
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_reference.1', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_reference_test %s already exists.', $definition['field_test_reference'][1]), $violations[0]->getMessage());

    // Create entity with two references for the testing field.
    $definition = [
      'user_id' => 0,
      'field_test_reference' => [
        $users[1]->id(),
        $users[2]->id(),
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
    $entity->save();
    $violations = $entity->validate();
    $this->assertCount(0, $violations);

    // Create another entity with two references for the testing field.
    $definition = [
      'user_id' => 0,
      'field_test_reference' => [
        $users[3]->id(),
        $users[4]->id(),
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
    $entity->save();
    $violations = $entity->validate();
    $this->assertCount(0, $violations);

    // Create another entity with two references, but one reference is existing.
    $definition = [
      'user_id' => 0,
      'field_test_reference' => [
        $users[5]->id(),
        $users[1]->id(),
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_reference.1', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_reference_test %s already exists.', $definition['field_test_reference'][1]), $violations[0]->getMessage());

  }

  /**
   * Tests the UniqueField validation constraint validator for existing value in the same entity.
   *
   * Case 3. Try to add existing value for unique field in the same entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @covers ::validate
   */
  public function testValidationOwn(): void {
    // Create new entity with two identical values for the testing field.
    $definition = [
      'user_id' => 0,
      'field_test_text' => [
        'text0',
        'text0',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_text.1', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][1]), $violations[0]->getMessage());

    // Create entity with two different values for the testing field.
    $definition = [
      'user_id' => 0,
      'field_test_text' => [
        'text1',
        'text2',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
    $entity->save();
    $violations = $entity->validate();
    $this->assertCount(0, $violations);

    // Add existing value.
    $entity->get('field_test_text')->appendItem($definition['field_test_text'][0]);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_text.2', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][0]), $violations[0]->getMessage());

  }

  /**
   * Tests the UniqueField validation constraint validator for multiple violations.
   *
   * Case 4. Try to add multiple existing values for unique field in the same entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @covers ::validate
   */
  public function testValidationMultiple(): void {
    // Create entity with two different values for the testing field.
    $definition = [
      'user_id' => 0,
      'field_test_text' => [
        'multi0',
        'multi1',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
    $entity->save();
    $violations = $entity->validate();
    $this->assertCount(0, $violations);

    // Create new entity with three identical values in unique field.
    $definition = [
      'user_id' => 0,
      'field_test_text' => [
        'multi2',
        'multi2',
        'multi2',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals('field_test_text.1', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][1]), $violations[0]->getMessage());
    $this->assertEquals('field_test_text.2', $violations[1]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][2]), $violations[1]->getMessage());

    // Create new entity with two identical values and one existing value in unique field.
    $definition = [
      'user_id' => 0,
      'field_test_text' => [
        'multi3',
        'multi1',
        'multi3',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals('field_test_text.1', $violations[0]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][1]), $violations[0]->getMessage());
    $this->assertEquals('field_test_text.2', $violations[1]->getPropertyPath());
    $this->assertEquals(sprintf('A unique field entity with unique_field_test %s already exists.', $definition['field_test_text'][2]), $violations[1]->getMessage());

  }

  /**
   * Tests the UniqueField validation constraint validator with regards to case-insensitivity.
   *
   * Case 5. Try to create another entity with existing value for unique field with different capitalization.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @covers ::validate
   */
  public function testValidationCaseInsensitive(): void {
    // Create entity with two values for the testing field.
    $definition = [
      'id' => (int) rand(0, getrandmax()),
      'user_id' => 0,
      'field_test_text' => [
        'text1',
        'text2',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $entity->save();

    // Create another entity with two values for the testing field, one identical
    // to other value, but with different capitalization which should still trigger a validation error.
    $definition = [
      'id' => (int) rand(0, getrandmax()),
      'user_id' => 0,
      'field_test_text' => [
        'Text1',
        'text3',
      ],
    ];
    $entity = EntityTestUniqueConstraint::create($definition);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('field_test_text.0', $violations[0]->getPropertyPath());
  }

}
