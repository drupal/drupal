<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin\Annotation;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Annotation\ContextDefinition.
 */
#[CoversClass(\Drupal\Core\Annotation\ContextDefinition::class)]
#[Group('Plugin')]
#[RunTestsInSeparateProcesses]
class ContextDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_test'];

  /**
   * Tests adding constraints via annotations.
   */
  public function testConstraints(): void {
    $definition = $this->container->get('plugin.manager.block')->getDefinition('test_context_aware');
    $this->assertArrayHasKey('context_definitions', $definition);
    $this->assertArrayHasKey('user', $definition['context_definitions']);
    $this->assertInstanceOf(ContextDefinition::class, $definition['context_definitions']['user']);
    $this->assertEquals(['NotNull' => []], $definition['context_definitions']['user']->getConstraints());
    $this->assertEquals("User Context", $definition['context_definitions']['user']->getLabel());
  }

}
