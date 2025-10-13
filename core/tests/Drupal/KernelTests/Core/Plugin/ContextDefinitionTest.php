<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Plugin\Context\ContextDefinition.
 */
#[CoversClass(ContextDefinition::class)]
#[Group('Plugin')]
#[RunTestsInSeparateProcesses]
class ContextDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * Tests is satisfied by.
   *
   * @legacy-covers ::isSatisfiedBy
   */
  public function testIsSatisfiedBy(): void {
    $this->installEntitySchema('user');

    $value = EntityTest::create([]);
    // Assert that the entity has at least one violation.
    $this->assertNotEmpty($value->validate());
    // Assert that these violations do not prevent it from satisfying the
    // requirements of another object.
    $requirement = new ContextDefinition('any');
    $context = EntityContext::fromEntity($value);
    $this->assertTrue($requirement->isSatisfiedBy($context));

    // Test with multiple values.
    $definition = EntityContextDefinition::create('entity_test');
    $definition->setMultiple();
    $entities = [
      EntityTest::create([]),
      EntityTest::create([]),
    ];
    $context = new Context($definition, $entities);
    $this->assertTrue($definition->isSatisfiedBy($context));
  }

  /**
   * Tests entity context definition assert.
   *
   * @legacy-covers ::__construct
   */
  public function testEntityContextDefinitionAssert(): void {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('assert(!str_starts_with($data_type, \'entity:\') || $this instanceof EntityContextDefinition)');
    new ContextDefinition('entity:entity_test');
  }

  /**
   * Tests create with entity data type.
   *
   * @legacy-covers ::create
   */
  public function testCreateWithEntityDataType(): void {
    $this->assertInstanceOf(EntityContextDefinition::class, ContextDefinition::create('entity:user'));
  }

}
