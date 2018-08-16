<?php

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextDefinition
 * @group Plugin
 */
class ContextDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'user'];

  /**
   * @covers ::isSatisfiedBy
   */
  public function testIsSatisfiedBy() {
    $this->installEntitySchema('user');

    $value = EntityTest::create([]);
    // Assert that the entity has at least one violation.
    $this->assertNotEmpty($value->validate());
    // Assert that these violations do not prevent it from satisfying the
    // requirements of another object.
    $requirement = new ContextDefinition('any');
    $context = EntityContext::fromEntity($value);
    $this->assertTrue($requirement->isSatisfiedBy($context));
  }

}
