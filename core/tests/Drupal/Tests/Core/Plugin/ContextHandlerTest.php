<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\ContextHandlerTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandler;
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
    $requirement_specific->setConstraints(array('bar' => 'baz'));

    $context_constraint_mismatch = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_constraint_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('foo')));
    $context_datatype_mismatch = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_datatype_mismatch->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('fuzzy')));

    $context_definition_specific = new ContextDefinition('specific');
    $context_definition_specific->setConstraints(array('bar' => 'baz'));
    $context_specific = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_specific->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue($context_definition_specific));

    $data = array();
    $data[] = array(array(), array(), TRUE);
    $data[] = array(array(), array($requirement_any), FALSE);
    $data[] = array(array(), array($requirement_optional), TRUE);
    $data[] = array(array(), array($requirement_any, $requirement_optional), FALSE);
    $data[] = array(array($context_any), array($requirement_any), TRUE);
    $data[] = array(array($context_constraint_mismatch), array($requirement_specific), FALSE);
    $data[] = array(array($context_datatype_mismatch), array($requirement_specific), FALSE);
    $data[] = array(array($context_specific), array($requirement_specific), TRUE);

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
    $requirement_specific->setConstraints(array('bar' => 'baz'));

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
    $context_definition_specific->setConstraints(array('bar' => 'baz'));
    $context_specific = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_specific->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue($context_definition_specific));

    $data = array();
    // No context will return no valid contexts.
    $data[] = array(array(), $requirement_any);
    // A context with a generic matching requirement is valid.
    $data[] = array(array($context_any), $requirement_any);
    // A context with a specific matching requirement is valid.
    $data[] = array(array($context_specific), $requirement_specific);

    // A context with a mismatched constraint is invalid.
    $data[] = array(array($context_constraint_mismatch), $requirement_specific, array());
    // A context with a mismatched datatype is invalid.
    $data[] = array(array($context_datatype_mismatch), $requirement_specific, array());

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
      $expected_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(array('expected_constraint_name' => 'expected_constraint_value'));
      $context->expects($this->atLeastOnce())
        ->method('getContextDefinition')
        ->will($this->returnValue($expected_context_definition));
      $contexts = array($context);
    }
    else {
      $contexts = array();
    }

    $this->assertSame($expected, $this->contextHandler->filterPluginDefinitionsByContexts($contexts, $definitions));
  }

  /**
   * Provides data for testFilterPluginDefinitionsByContexts().
   */
  public function providerTestFilterPluginDefinitionsByContexts() {
    $data = array();

    $plugins = array();
    // No context and no plugins, no plugins available.
    $data[] = array(FALSE, $plugins, array());

    $plugins = array('expected_plugin' => array());
    // No context, all plugins available.
    $data[] = array(FALSE, $plugins, $plugins);

    $plugins = array('expected_plugin' => array('context' => array()));
    // No context, all plugins available.
    $data[] = array(FALSE, $plugins, $plugins);

    $plugins = array('expected_plugin' => array('context' => array('context1' => new ContextDefinition('expected_data_type'))));
    // Missing context, no plugins available.
    $data[] = array(FALSE, $plugins, array());
    // Satisfied context, all plugins available.
    $data[] = array(TRUE, $plugins, $plugins);

    $mismatched_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(array('mismatched_constraint_name' => 'mismatched_constraint_value'));
    $plugins = array('expected_plugin' => array('context' => array('context1' => $mismatched_context_definition)));
    // Mismatched constraints, no plugins available.
    $data[] = array(TRUE, $plugins, array());

    $optional_mismatched_context_definition = clone $mismatched_context_definition;
    $optional_mismatched_context_definition->setRequired(FALSE);
    $plugins = array('expected_plugin' => array('context' => array('context1' => $optional_mismatched_context_definition)));
    // Optional mismatched constraint, all plugins available.
    $data[] = array(FALSE, $plugins, $plugins);

    $expected_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(array('expected_constraint_name' => 'expected_constraint_value'));
    $plugins = array('expected_plugin' => array('context' => array('context1' => $expected_context_definition)));
    // Satisfied context with constraint, all plugins available.
    $data[] = array(TRUE, $plugins, $plugins);

    $optional_expected_context_definition = clone $expected_context_definition;
    $optional_expected_context_definition->setRequired(FALSE);
    $plugins = array('expected_plugin' => array('context' => array('context1' => $optional_expected_context_definition)));
    // Optional unsatisfied context, all plugins available.
    $data[] = array(FALSE, $plugins, $plugins);

    $unexpected_context_definition = (new ContextDefinition('unexpected_data_type'))->setConstraints(array('mismatched_constraint_name' => 'mismatched_constraint_value'));
    $plugins = array(
      'unexpected_plugin' => array('context' => array('context1' => $unexpected_context_definition)),
      'expected_plugin' => array('context' => array('context2' => new ContextDefinition('expected_data_type'))),
    );
    // Context only satisfies one plugin.
    $data[] = array(TRUE, $plugins, array('expected_plugin' => $plugins['expected_plugin']));

    return $data;
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMapping() {
    $context_hit = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_hit->expects($this->atLeastOnce())
      ->method('getContextValue')
      ->will($this->returnValue(array('foo')));
    $context_miss = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_miss->expects($this->never())
      ->method('getContextValue');

    $contexts = array(
      'hit' => $context_hit,
      'miss' => $context_miss,
    );

    $plugin = $this->getMock('Drupal\Component\Plugin\ContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(array('hit' => 'hit')));
    $plugin->expects($this->once())
      ->method('setContextValue')
      ->with('hit', array('foo'));

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingConfigurable() {
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = array(
      'name' => $context,
    );

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(array('hit' => 'hit')));
    $plugin->expects($this->never())
      ->method('setContextValue');

    $this->contextHandler->applyContextMapping($plugin, $contexts);
  }

  /**
   * @covers ::applyContextMapping
   */
  public function testApplyContextMappingConfigurableAssigned() {
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->atLeastOnce())
      ->method('getContextValue')
      ->will($this->returnValue(array('foo')));

    $contexts = array(
      'name' => $context,
    );

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(array('hit' => 'hit')));
    $plugin->expects($this->once())
      ->method('setContextValue')
      ->with('hit', array('foo'));

    $this->contextHandler->applyContextMapping($plugin, $contexts, array('name' => 'hit'));
  }

  /**
   * @covers ::applyContextMapping
   *
   * @expectedException \Drupal\Component\Plugin\Exception\ContextException
   * @expectedExceptionMessage Assigned contexts were not satisfied: miss
   */
  public function testApplyContextMappingConfigurableAssignedMiss() {
    $context = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context->expects($this->never())
      ->method('getContextValue');

    $contexts = array(
      'name' => $context,
    );

    $plugin = $this->getMock('Drupal\Tests\Core\Plugin\TestConfigurableContextAwarePluginInterface');
    $plugin->expects($this->once())
      ->method('getContextMapping')
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getContextDefinitions')
      ->will($this->returnValue(array('hit' => 'hit')));
    $plugin->expects($this->never())
      ->method('setContextValue');

    $this->contextHandler->applyContextMapping($plugin, $contexts, array('name' => 'miss'));
  }

}

interface TestConfigurableContextAwarePluginInterface extends ContextAwarePluginInterface, ConfigurablePluginInterface {
}
