<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Plugin\Context\ContextHandler.
 */
#[CoversClass(ContextHandler::class)]
#[Group('Plugin')]
#[RunTestsInSeparateProcesses]
class ContextHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * Tests apply context mapping.
   */
  public function testApplyContextMapping(): void {
    $entity = EntityTest::create([]);
    $context_definition = EntityContextDefinition::fromEntity($entity);
    $context = EntityContext::fromEntity($entity);

    $definition = ['context_definitions' => ['a_context_id' => $context_definition]];
    $plugin = new TestContextAwarePlugin([], 'test_plugin_id', $definition);
    (new ContextHandler())->applyContextMapping($plugin, ['a_context_id' => $context]);

    $result = $plugin->getContext('a_context_id');

    $this->assertInstanceOf(EntityContext::class, $result);
    $this->assertSame($context, $result);
  }

  /**
   * Tests apply context mapping already applied.
   */
  public function testApplyContextMappingAlreadyApplied(): void {
    $entity = EntityTest::create([]);
    $context_definition = EntityContextDefinition::fromEntity($entity);
    $context = EntityContext::fromEntity($entity);

    $definition = ['context_definitions' => ['a_context_id' => $context_definition]];
    $plugin = new TestContextAwarePlugin([], 'test_plugin_id', $definition);
    $plugin->setContext('a_context_id', $context);
    (new ContextHandler())->applyContextMapping($plugin, []);

    $result = $plugin->getContext('a_context_id');

    $this->assertInstanceOf(EntityContext::class, $result);
    $this->assertSame($context, $result);
  }

}

/**
 * Provides a test implementation of a context-aware plugin.
 */
class TestContextAwarePlugin extends PluginBase implements ContextAwarePluginInterface {

  use ContextAwarePluginTrait;

}
