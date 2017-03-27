<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\ContextHandlerTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Tests\UnitTestCase;

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
  protected function setUp() {
    parent::setUp();

    $this->contextHandler = new ContextHandler();
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

    $context_any = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_any->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('empty')));

    $requirement_specific = new ContextDefinition('specific');
    $requirement_specific->setConstraints(['bar' => 'baz']);

    $context_constraint_mismatch = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_constraint_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('foo')));
    $context_datatype_mismatch = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_datatype_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('fuzzy')));

    $context_definition_specific = new ContextDefinition('specific');
    $context_definition_specific->setConstraints(['bar' => 'baz']);
    $context_specific = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_specific->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue($context_definition_specific));

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

    $requirement_specific = new ContextDefinition('specific');
    $requirement_specific->setConstraints(['bar' => 'baz']);

    $context_any = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_any->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('empty')));
    $context_constraint_mismatch = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_constraint_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('foo')));
    $context_datatype_mismatch = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_datatype_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('fuzzy')));
    $context_definition_specific = new ContextDefinition('specific');
    $context_definition_specific->setConstraints(['bar' => 'baz']);
    $context_specific = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_specific->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue($context_definition_specific));

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
      $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
      $expected_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(['expected_constraint_name' => 'expected_constraint_value']);
      $context->expects($this->atLeastOnce())
        ->method('getContextDefinition')
        ->will($this->returnValue($expected_context_definition));
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

    $plugins = ['expected_plugin' => []];
    // No context, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $plugins = ['expected_plugin' => ['context' => []]];
    // No context, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $plugins = ['expected_plugin' => ['context' => ['context1' => new ContextDefinition('expected_data_type')]]];
    // Missing context, no plugins available.
    $data[] = [FALSE, $plugins, []];
    // Satisfied context, all plugins available.
    $data[] = [TRUE, $plugins, $plugins];

    $mismatched_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(['mismatched_constraint_name' => 'mismatched_constraint_value']);
    $plugins = ['expected_plugin' => ['context' => ['context1' => $mismatched_context_definition]]];
    // Mismatched constraints, no plugins available.
    $data[] = [TRUE, $plugins, []];

    $optional_mismatched_context_definition = clone $mismatched_context_definition;
    $optional_mismatched_context_definition->setRequired(FALSE);
    $plugins = ['expected_plugin' => ['context' => ['context1' => $optional_mismatched_context_definition]]];
    // Optional mismatched constraint, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $expected_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(['expected_constraint_name' => 'expected_constraint_value']);
    $plugins = ['expected_plugin' => ['context' => ['context1' => $expected_context_definition]]];
    // Satisfied context with constraint, all plugins available.
    $data[] = [TRUE, $plugins, $plugins];

    $optional_expected_context_definition = clone $expected_context_definition;
    $optional_expected_context_definition->setRequired(FALSE);
    $plugins = ['expected_plugin' => ['context' => ['context1' => $optional_expected_context_definition]]];
    // Optional unsatisfied context, all plugins available.
    $data[] = [FALSE, $plugins, $plugins];

    $unexpected_context_definition = (new ContextDefinition('unexpected_data_type'))->setConstraints(['mismatched_constraint_name' => 'mismatched_constraint_value']);
    $plugins = [
      'unexpected_plugin' => ['context' => ['context1' => $unexpected_context_definition]],
      'expected_plugin' => ['context' => ['context2' => new ContextDefinition('expected_data_type')]],
    ];
    // Context only satisfies one plugin.
    $data[] = [TRUE, $plugins, ['expected_plugin' => $plugins['expected_plugin']]];

    return $data;
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMapping() {
    $context_hit_data = StringData::createInstance(DataDefinition::create('string'));
    $context_hit_data->setValue('foo');
    $context_hit = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_hit->expects($this->atLeastOnce())
      ->method('getContextData')
      ->will($this->returnValue($context_hit_data));
    $context_miss_data = StringData::createInstance(DataDefinition::create('string'));
    $context_miss_data->setValue('bar');
    $context_hit->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(TRUE);
    $context_miss = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_miss->expects($this->never())
      ->method('getContextData');

    $contexts = [
      'hit' => $context_hit,
      'miss' => $context_miss,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $plugin = $this->getMock('Drupal\Core\Plugin\ContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['hit' => $context_definition]));
    $plugin->expects($this->once())
      ->method('setContextValue')
      ->with('hit', $context_hit_data);

    // Make sure that the cacheability metadata is passed to the plugin context.
    $plugin_context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
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
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(TRUE);

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['hit' => $context_definition]));
    $plugin->expects($this->never())
      ->method('setContextValue');

    // No context, so no cacheability metadata can be passed along.
    $plugin->expects($this->never())
      ->method('getContext');

    $this->setExpectedException(ContextException::class, 'Required contexts without a value: hit.');
    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingMissingNotRequired() {
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(FALSE);

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn(['optional' => 'missing']);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['optional' => $context_definition]));
    $plugin->expects($this->never())
      ->method('setContextValue');

    // No context, so no cacheability metadata can be passed along.
    $plugin->expects($this->never())
      ->method('getContext');

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingNoValueRequired() {
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');
    $context->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(FALSE);

    $contexts = [
      'hit' => $context,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(TRUE);

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['hit' => $context_definition]));
    $plugin->expects($this->never())
      ->method('setContextValue');

    $this->setExpectedException(ContextException::class, 'Required contexts without a value: hit.');
    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }


  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingNoValueNonRequired() {
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');
    $context->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(FALSE);

    $contexts = [
      'hit' => $context,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');
    $context_definition->expects($this->atLeastOnce())
      ->method('isRequired')
      ->willReturn(FALSE);

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['hit' => $context_definition]));
    $plugin->expects($this->never())
      ->method('setContextValue');

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingConfigurableAssigned() {
    $context_data = StringData::createInstance(DataDefinition::create('string'));
    $context_data->setValue('foo');
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->atLeastOnce())
      ->method('getContextData')
      ->will($this->returnValue($context_data));
    $context->expects($this->atLeastOnce())
      ->method('hasContextValue')
      ->willReturn(TRUE);

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['hit' => $context_definition]));
    $plugin->expects($this->once())
      ->method('setContextValue')
      ->with('hit', $context_data);

    // Make sure that the cacheability metadata is passed to the plugin context.
    $plugin_context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
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
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = [
      'name' => $context,
    ];

    $context_definition = $this->getMock('Drupal\Core\Plugin\Context\ContextDefinitionInterface');

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(['hit' => $context_definition]));
    $plugin->expects($this->never())
      ->method('setContextValue');

    $this->setExpectedException(ContextException::class, 'Assigned contexts were not satisfied: miss');
    $this->contextHandler->applyContextMapping($plugin, $contexts, ['miss' => 'name']);
  }

}

interface TestConfigurableContextAwarePluginInterface extends ContextAwarePluginInterface, ConfigurablePluginInterface {
}
