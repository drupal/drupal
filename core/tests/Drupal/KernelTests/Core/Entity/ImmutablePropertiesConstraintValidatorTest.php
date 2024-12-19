<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @group Entity
 * @group Validation
 *
 * @covers \Drupal\Core\Entity\Plugin\Validation\Constraint\ImmutablePropertiesConstraint
 * @covers \Drupal\Core\Entity\Plugin\Validation\Constraint\ImmutablePropertiesConstraintValidator
 */
class ImmutablePropertiesConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content'];

  /**
   * Tests that only config entities are accepted by the validator.
   */
  public function testValidatorRequiresAConfigEntity(): void {
    $definition = DataDefinition::createFromDataType('any')
      ->addConstraint('ImmutableProperties', ['read_only']);
    $data = $this->container->get(TypedDataManagerInterface::class)
      ->create($definition, 39);
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Expected argument of type "' . ConfigEntityInterface::class . '", "int" given');
    $data->validate();
  }

  /**
   * Tests that the validator throws an exception for non-existent properties.
   */
  public function testValidatorRejectsANonExistentProperty(): void {
    /** @var \Drupal\block_content\BlockContentTypeInterface $entity */
    $entity = BlockContentType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $entity->save();
    $this->assertFalse(property_exists($entity, 'non_existent'));

    $definition = DataDefinition::createFromDataType('entity:block_content_type')
      ->addConstraint('ImmutableProperties', ['non_existent']);

    $this->expectException(LogicException::class);
    $this->expectExceptionMessage("The entity does not have a 'non_existent' property.");
    $this->container->get(TypedDataManagerInterface::class)
      ->create($definition, $entity)
      ->validate();
  }

  /**
   * Tests that entities without an ID will raise an exception.
   */
  public function testValidatedEntityMustHaveAnId(): void {
    $entity = $this->prophesize(ConfigEntityInterface::class);
    $entity->isNew()->willReturn(FALSE)->shouldBeCalled();
    $entity->getOriginalId()->shouldBeCalled();
    $entity->id()->shouldBeCalled();

    $definition = DataDefinition::createFromDataType('any')
      ->addConstraint('ImmutableProperties', ['read_only']);
    $data = $this->container->get(TypedDataManagerInterface::class)
      ->create($definition, $entity->reveal());
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage('The entity does not have an ID.');
    $data->validate();
  }

  /**
   * Tests that changing a config entity's immutable property raises an error.
   */
  public function testImmutablePropertyCannotBeChanged(): void {
    /** @var \Drupal\block_content\BlockContentTypeInterface $entity */
    $entity = BlockContentType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $entity->save();

    $definition = DataDefinition::createFromDataType('entity:block_content_type')
      ->addConstraint('ImmutableProperties', ['id', 'description']);

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager */
    $typed_data_manager = $this->container->get(TypedDataManagerInterface::class);

    // Try changing one immutable property, and one mutable property.
    $entity->set('id', 'foo')->set('label', 'Testing!');
    $violations = $typed_data_manager->create($definition, $entity)->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'id' property cannot be changed.", (string) $violations[0]->getMessage());

    // Ensure we get multiple violations if more than one immutable property is
    // changed.
    $entity->set('description', "From hell's heart, I describe thee!");
    $violations = $typed_data_manager->create($definition, $entity)->validate();
    $this->assertCount(2, $violations);
    $this->assertSame("The 'id' property cannot be changed.", (string) $violations[0]->getMessage());
    $this->assertSame("The 'description' property cannot be changed.", (string) $violations[1]->getMessage());
  }

}
