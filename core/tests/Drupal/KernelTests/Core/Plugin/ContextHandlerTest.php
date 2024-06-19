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

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextHandler
 *
 * @group Plugin
 */
class ContextHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * @covers ::applyContextMapping
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
   * @covers ::applyContextMapping
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
