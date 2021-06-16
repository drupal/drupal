<?php

namespace Drupal\KernelTests\Core\Plugin\Annotation;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Annotation\ContextDefinition
 * @group Plugin
 */
class ContextDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_test'];

  /**
   * Tests adding constraints via annotations.
   */
  public function testConstraints() {
    $definition = $this->container->get('plugin.manager.block')->getDefinition('test_context_aware');
    $this->assertArrayHasKey('context_definitions', $definition);
    $this->assertArrayHasKey('user', $definition['context_definitions']);
    $this->assertInstanceOf(ContextDefinition::class, $definition['context_definitions']['user']);
    $this->assertEquals(['NotNull' => []], $definition['context_definitions']['user']->getConstraints());
    $this->assertEquals("User Context", $definition['context_definitions']['user']->getLabel());
  }

}
