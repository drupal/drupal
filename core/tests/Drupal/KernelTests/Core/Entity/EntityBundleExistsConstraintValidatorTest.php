<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @group Entity
 * @group Validation
 *
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\EntityBundleExistsConstraint
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\EntityBundleExistsConstraintValidator
 */
class EntityBundleExistsConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    EntityTestBundle::create([
      'id' => 'foo',
      'label' => 'Test',
    ])->save();
  }

  /**
   * Tests that the constraint validator will only work with strings.
   */
  public function testValueMustBeAString(): void {
    $definition = DataDefinition::create('any')
      ->addConstraint('EntityBundleExists', 'entity_test_with_bundle');

    $this->expectException(UnexpectedTypeException::class);
    $this->expectExceptionMessage('Expected argument of type "string", "int" given');
    $this->container->get('typed_data_manager')
      ->create($definition, 39)
      ->validate();
  }

  /**
   * Tests validating a bundle of a known (static) entity type ID.
   */
  public function testEntityTypeIdIsStatic(): void {
    $definition = DataDefinition::create('string')
      ->addConstraint('EntityBundleExists', 'entity_test_with_bundle');

    $violations = $this->container->get('typed_data_manager')
      ->create($definition, 'bar')
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'bar' bundle does not exist on the 'entity_test_with_bundle' entity type.", (string) $violations->get(0)->getMessage());
    $this->assertSame('', $violations->get(0)->getPropertyPath());
  }

  /**
   * Tests getting the entity type ID.
   *
   * @param string $constraint_value
   *   The entity type ID to supply to the validation constraint. Must be a
   *   dynamic token starting with %.
   * @param string $resolved_entity_type_id
   *   The actual entity type ID which should be checked for the existence of
   *   a bundle.
   *
   * @testWith ["%parent.entity_type_id", "entity_test_with_bundle"]
   *   ["%key", "bundle"]
   */
  public function testDynamicEntityType(string $constraint_value, string $resolved_entity_type_id): void {
    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager */
    $typed_data_manager = $this->container->get('typed_data_manager');

    $this->assertStringStartsWith('%', $constraint_value);
    $value_definition = DataDefinition::create('string')
      ->addConstraint('EntityBundleExists', $constraint_value);

    $parent_definition = MapDataDefinition::create()
      ->setPropertyDefinition('entity_type_id', DataDefinition::create('string'))
      ->setPropertyDefinition('bundle', $value_definition);

    $violations = $typed_data_manager->create($parent_definition, [
      'entity_type_id' => 'entity_test_with_bundle',
      'bundle' => 'bar',
    ])->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'bar' bundle does not exist on the '$resolved_entity_type_id' entity type.", (string) $violations->get(0)->getMessage());
    $this->assertSame('bundle', $violations->get(0)->getPropertyPath());
  }

  /**
   * Tests getting the entity type ID from a deeply nested property path.
   */
  public function testEntityTypeIdFromMultipleParents(): void {
    $tree_definition = MapDataDefinition::create()
      ->setPropertyDefinition('info', MapDataDefinition::create()
        ->setPropertyDefinition('entity_type_id', DataDefinition::create('string'))
      )
      ->setPropertyDefinition('info2', MapDataDefinition::create()
        ->setPropertyDefinition('bundle', DataDefinition::create('string')
          ->addConstraint('EntityBundleExists', '%parent.%parent.info.entity_type_id')
        )
      );

    $violations = $this->container->get('typed_data_manager')
      ->create($tree_definition, [
        'info' => [
          'entity_type_id' => 'entity_test_with_bundle',
        ],
        'info2' => [
          'bundle' => 'bar',
        ],
      ])
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'bar' bundle does not exist on the 'entity_test_with_bundle' entity type.", (string) $violations->get(0)->getMessage());
    $this->assertSame('info2.bundle', $violations->get(0)->getPropertyPath());
  }

  /**
   * Tests when the constraint's entityTypeId value is not valid.
   */
  public function testInvalidEntityTypeId(): void {
    $entity_type_id = $this->randomMachineName();
    $definition = DataDefinition::create('string')
      ->addConstraint('EntityBundleExists', $entity_type_id);

    $violations = $this->container->get('typed_data_manager')
      ->create($definition, 'bar')
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'bar' bundle does not exist on the '$entity_type_id' entity type.", (string) $violations->get(0)->getMessage());
    $this->assertSame('', $violations->get(0)->getPropertyPath());
  }

}
