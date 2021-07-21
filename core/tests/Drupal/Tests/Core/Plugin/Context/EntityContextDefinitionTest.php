<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Plugin\Context\EntityContextDefinition;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\EntityContextDefinition
 * @group Plugin
 */
class EntityContextDefinitionTest extends UnitTestCase {

  /**
   * Test that non-NULL default values for EntityContextDefinition are invalid.
   */
  public function testDefaultValueAssertions(): void {
    $entity_type = new EntityType(['id' => 'test_content']);

    $definition = new EntityContextDefinition('entity:test_content');
    $this->assertSame(NULL, $definition->getDefaultValue());

    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('EntityContextDefinition cannot have a default value');
    $definition = new EntityContextDefinition('entity:test_content', NULL, TRUE, FALSE, NULL, 'test');

    $definition = new EntityContextDefinition('entity:test_content');
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('EntityContextDefinition cannot have a default value');
    $definition->setDefaultValue('test');
  }

}
