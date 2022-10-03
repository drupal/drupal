<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\ContextHandlerTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionTrait;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Test\TestKernel;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextHandler
 * @group Plugin
 */
class ContextHandlerTest extends UnitTestCase {

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandler
   */
  protected $contextHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contextHandler = new ContextHandler();

    $namespaces = new \ArrayObject([
      'Drupal\\Core\\TypedData' => $this->root . '/core/lib/Drupal/Core/TypedData',
      'Drupal\\Core\\Validation' => $this->root . '/core/lib/Drupal/Core/Validation',
    ]);
    $cache_backend = new NullBackend('cache');
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $class_resolver->getInstanceFromDefinition(Argument::type('string'))->will(function ($arguments) {
      $class_name = $arguments[0];
      return new $class_name();
    });
    $type_data_manager = new TypedDataManager($namespaces, $cache_backend, $module_handler->reveal(), $class_resolver->reveal());
    $type_data_manager->setValidationConstraintManager(
      new ConstraintManager($namespaces, $cache_backend, $module_handler->reveal())
    );

    $container = TestKernel::setContainerWithKernel();
    $container->set('typed_data_manager', $type_data_manager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::checkRequirements
   *
   * @dataProvider providerTestCheckRequirements
   */
  public function testCheckRequirements($contexts, $requirements, $expected) {
    $this->assertSame($expected, $this->contextHandler->checkRequirements($contexts, $requirements));
  }

  /**
   * Provides data for testCheckRequirements().
   */
  public function providerTestCheckRequirements() {
    $requirement_optional = new ContextDefinition();
    $requirement_optional->setRequired(FALSE);

    $requirement_any = new ContextDefinition();
    $requirement_any->setRequired(TRUE);

    $context_any = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_any->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn(new ContextDefinition('any'));

    $requirement_specific = new ContextDefinition('string');
    $requirement_specific->setConstraints(['Blank' => []]);

    $context_constraint_mismatch = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_constraint_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn(new ContextDefinition('foo'));
    $context_datatype_mismatch = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_datatype_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn(new ContextDefinition('fuzzy'));

    $context_definition_specific = new ContextDefinition('string');
    $context_definition_specific->setConstraints(['Blank' => []]);
    $context_specific = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_specific->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn($context_definition_specific);

    $data = [];
    $data[] = [[], [], TRUE];
    $data[] = [[], [$requirement_any], FALSE];
    $data[] = [[], [$requirement_optional], TRUE];
    $data[] = [[], [$requirement_any, $requirement_optional], FALSE];
    $data[] = [[$context_any], [$requirement_any], TRUE];
    $data[] = [[$context_constraint_mismatch], [$requirement_specific], FALSE];
    $data[] = [[$context_datatype_mismatch], [$requirement_specific], FALSE];
    $data[] = [[$context_specific], [$requirement_specific], TRUE];

    return $data;
  }

  /**
   * @covers ::getMatchingContexts
   *
   * @dataProvider providerTestGetMatchingContexts
   */
  public function testGetMatchingContexts($contexts, $requirement, $expected = NULL) {
    if (is_null($expected)) {
      $expected = $contexts;
    }
    $this->assertSame($expected, $this->contextHandler->getMatchingContexts($contexts, $requirement));
  }

  /**
   * Provides data for testGetMatchingContexts().
   */
  public function providerTestGetMatchingContexts() {
    $requirement_any = new ContextDefinition();

    $requirement_specific = new ContextDefinition('string');
    $requirement_specific->setConstraints(['Blank' => []]);

    $context_any = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_any->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn(new ContextDefinition('any'));
    $context_constraint_mismatch = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_constraint_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn(new ContextDefinition('foo'));
    $context_datatype_mismatch = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_datatype_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn(new ContextDefinition('fuzzy'));
    $context_definition_specific = new ContextDefinition('string');
    $context_definition_specific->setConstraints(['Blank' => []]);
    $context_specific = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_specific->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->willReturn($context_definition_specific);

    $data = [];
    // No context will return no valid contexts.
    $data[] = [[], $requirement_any];
    // A context with a generic matching requirement is valid.
    $data[] = [[$context_any], $requirement_any];
    // A context with a specific matching requirement is valid.
    $data[] = [[$context_specific], $requirement_specific];

    // A context with a mismatched constraint is invalid.
    $data[] = [[$context_constraint_mismatch], $requirement_specific, []];
    // A context with a mismatched datatype is invalid.
    $data[] = [[$context_datatype_mismatch], $requirement_specific, []];

    return $data;
  }

  /**
   * @covers ::filterPluginDefinitionsByContexts
   *
   * @dataProvider providerTestFilterPluginDefinitionsByContexts
   */
  public function testFilterPluginDefinitionsByContexts($has_context, $definitions, $expected) {
    if ($has_context) {
      $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
      $expected_context_definition = (new ContextDefinition('string'))->setConstraints(['Blank' => []]);
      $context->expects($this->atLeastOnce())
        ->method('getContextDefinition')
        ->willReturn($expected_context_definition);
      $contexts = [$context];
    }
    else {
      $contexts = [];
    }

    $this->assertSame($expected, $this->contextHandler->filterPluginDefinitionsByContexts($contexts, $definitions));
  }

  /**
   * Provides data for testFilterPluginDefinitionsByContexts().
   */
  public function providerTestFilterPluginDefinitionsByContexts() {
    $data = [];

    $plugins = [];
    // No context and no plugins, no plugins available.
    $data[] = [FALSE, $plugins, []];

    $plugins = [
      'expected_array_plugin' => [],
      'expected_object_plugin' => new ContextAwarePluginDefinition(),
    ];
    // No context, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $plugins = [
      'expected_array_plugin' => ['context_definitions' => []],
      'expected_object_plugin' => new ContextAwarePluginDefinition(),
    ];
    // No context, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $plugins = [
      'expected_array_plugin' => [
        'context_definitions' => ['context1' => new ContextDefinition('string')],
      ],
      'expected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context1', new ContextDefinition('string')),
    ];
    // Missing context, no plugins available.
    $data[] = [FALSE, $plugins, []];
    // Satisfied context, all plugins available.
    $data[] = [TRUE, $plugins, $plugins];

    $mismatched_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(['mismatched_constraint_name' => 'mismatched_constraint_value']);
    $plugins = [
      'expected_array_plugin' => [
        'context_definitions' => ['context1' => $mismatched_context_definition],
      ],
      'expected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context1', $mismatched_context_definition),
    ];
    // Mismatched constraints, no plugins available.
    $data[] = [TRUE, $plugins, []];

    $optional_mismatched_context_definition = clone $mismatched_context_definition;
    $optional_mismatched_context_definition->setRequired(FALSE);
    $plugins = [
      'expected_array_plugin' => [
        'context_definitions' => ['context1' => $optional_mismatched_context_definition],
      ],
      'expected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context1', $optional_mismatched_context_definition),
    ];
    // Optional mismatched constraint, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $expected_context_definition = (new ContextDefinition('string'))->setConstraints(['Blank' => []]);
    $plugins = [
      'expected_array_plugin' => [
        'context_definitions' => ['context1' => $expected_context_definition],
      ],
      'expected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context1', $expected_context_definition),
    ];
    // Satisfied context with constraint, all plugins available.
    $data[] = [TRUE, $plugins, $plugins];

    $optional_expected_context_definition = clone $expected_context_definition;
    $optional_expected_context_definition->setRequired(FALSE);
    $plugins = [
      'expected_array_plugin' => [
        'context_definitions' => ['context1' => $optional_expected_context_definition],
      ],
      'expected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context1', $optional_expected_context_definition),
    ];
    // Optional unsatisfied context, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $unexpected_context_definition = (new ContextDefinition('unexpected_data_type'))->setConstraints(['mismatched_constraint_name' => 'mismatched_constraint_value']);
    $plugins = [
      'unexpected_array_plugin' => [
        'context_definitions' => ['context1' => $unexpected_context_definition],
      ],
      'expected_array_plugin' => [
        'context_definitions' => ['context2' => new ContextDefinition('string')],
      ],
      'unexpected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context1', $unexpected_context_definition),
      'expected_object_plugin' => (new ContextAwarePluginDefinition())
        ->addContextDefinition('context2', new ContextDefinition('string')),
    ];
    // Context only satisfies two plugins.
    $data[] = [
      TRUE,
      $plugins,
      [
        'expected_array_plugin' => $plugins['expected_array_plugin'],
        'expected_object_plugin' => $plugins['expected_object_plugin'],
      ],
    ];

    return $data;
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMapping() {
    $context_hit = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_hit->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(TRUE);
    $context_miss = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');

    $contexts = [
      'hit' => $context_hit,
      'miss' => $context_miss,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $plugin = $this->createMock('Drupal\Core\Plugin\ContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['hit' => $context_definition]);
    $plugin->expects($this->once())
      ->method('setContext')
      ->with('hit', $context_hit);

    // Make sure that the cacheability metadata is passed to the plugin context.
    $plugin_context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $plugin_context->expects($this->once())
      ->method('addCacheableDependency')
      ->with($context_hit);
    $plugin->expects($this->once())
      ->method('getContext')
      ->with('hit')
      ->willReturn($plugin_context);

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingMissingRequired() {
    $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(TRUE);

    $plugin = $this->createMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['hit' => $context_definition]);
    $plugin->expects($this->never())
      ->method('setContext');

    // No context, so no cacheability metadata can be passed along.
    $plugin->expects($this->any())
      ->method('getContext')
      ->willThrowException(new ContextException());

    $this->expectException(MissingValueContextException::class);
    $this->expectExceptionMessage('Required contexts without a value: hit');
    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingMissingNotRequired() {
    $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(FALSE);

    $plugin = $this->createMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn(['optional' => 'missing']);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['optional' => $context_definition]);
    $plugin->expects($this->never())
      ->method('setContext');

    // No context, so no cacheability metadata can be passed along.
    $plugin->expects($this->any())
      ->method('getContext')
      ->willThrowException(new ContextException());

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingNoValueRequired() {
    $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');
    $context->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(FALSE);

    $contexts = [
      'hit' => $context,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(TRUE);

    $plugin = $this->createMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['hit' => $context_definition]);
    $plugin->expects($this->never())
      ->method('setContext');

    $this->expectException(MissingValueContextException::class);
    $this->expectExceptionMessage('Required contexts without a value: hit');
    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingNoValueNonRequired() {
    $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');
    $context->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(FALSE);

    $contexts = [
      'hit' => $context,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(FALSE);

    $plugin = $this->createMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['hit' => $context_definition]);
    $plugin->expects($this->never())
      ->method('setContext');

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingConfigurableAssigned() {
    $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(TRUE);

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $plugin = $this->createMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['hit' => $context_definition]);
    $plugin->expects($this->once())
      ->method('setContext')
      ->with('hit', $context);

    // Make sure that the cacheability metadata is passed to the plugin context.
    $plugin_context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $plugin_context->expects($this->once())
      ->method('addCacheableDependency')
      ->with($context);
    $plugin->expects($this->once())
      ->method('getContext')
      ->with('hit')
      ->willReturn($plugin_context);

    $this->contextHandler->applyContextMapping($plugin, $contexts, ['hit' => 'name']);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingConfigurableAssignedMiss() {
    $context = $this->createMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->createMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $plugin = $this->createMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->willReturn(['hit' => $context_definition]);
    $plugin->expects($this->never())
      ->method('setContext');

    $this->expectException(ContextException::class);
    $this->expectExceptionMessage('Assigned contexts were not satisfied: miss');
    $this->contextHandler->applyContextMapping($plugin, $contexts, ['miss' => 'name']);
  }

}

interface TestConfigurableContextAwarePluginInterface extends ContextAwarePluginInterface, ConfigurableInterface, DependentPluginInterface {

}

class ContextAwarePluginDefinition extends PluginDefinition implements ContextAwarePluginDefinitionInterface {
  use ContextAwarePluginDefinitionTrait;

}
