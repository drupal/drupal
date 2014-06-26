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
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ContextHandler class.
 *
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextHandler
 *
 * @group Drupal
 * @group Plugin
 * @group Context
 */
class ContextHandlerTest extends UnitTestCase {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedDataManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandler
   */
  protected $contextHandler;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'ContextHandler',
      'description' => 'Tests the ContextHandler',
      'group' => 'Plugin API',
    );
  }

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();

    $this->typedDataManager = $this->getMockBuilder('Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->typedDataManager->expects($this->any())
      ->method('getDefaultConstraints')
      ->will($this->returnValue(array()));
    $this->typedDataManager->expects($this->any())
      ->method('createDataDefinition')
      ->will($this->returnValueMap(array(
        array('expected_data_type', new DataDefinition(array('type' => 'expected_data_type'))),
        array('mismatched_data_type', new DataDefinition(array('type' => 'mismatched_data_type'))),
        array('unexpected_data_type', new DataDefinition(array('type' => 'unexpected_data_type'))),
        array('empty', new DataDefinition(array())),
        array('foo', new DataDefinition(array('type' => 'foo'))),
        array('fuzzy', new DataDefinition(array('type' => 'fuzzy'))),
        array('specific', new DataDefinition(array('type' => 'foo'))),
      )));
    $this->contextHandler = new ContextHandler($this->typedDataManager);

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
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
    $requirement_optional = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $requirement_optional->expects($this->atLeastOnce())
      ->method('isRequired')
      ->will($this->returnValue(FALSE));

    $requirement_any = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $requirement_any->expects($this->atLeastOnce())
      ->method('isRequired')
      ->will($this->returnValue(TRUE));
    $requirement_any->expects($this->atLeastOnce())
      ->method('getDataType')
      ->will($this->returnValue('any'));
    $requirement_any->expects($this->atLeastOnce())
      ->method('getConstraints')
      ->will($this->returnValue(array()));

    $context_any = $this->getMock('Drupal\Core\Plugin\Context\ContextInterface');
    $context_any->expects($this->atLeastOnce())
      ->method('getContextDefinition')
      ->will($this->returnValue(new ContextDefinition('empty')));

    $requirement_specific = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $requirement_specific->expects($this->atLeastOnce())
      ->method('isRequired')
      ->will($this->returnValue(TRUE));
    $requirement_specific->expects($this->atLeastOnce())
      ->method('getDataType')
      ->will($this->returnValue('foo'));
    $requirement_specific->expects($this->atLeastOnce())
      ->method('getConstraints')
      ->will($this->returnValue(array('bar' => 'baz')));

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
    $requirement_any = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $requirement_any->expects($this->atLeastOnce())
      ->method('isRequired')
      ->will($this->returnValue(TRUE));
    $requirement_any->expects($this->atLeastOnce())
      ->method('getDataType')
      ->will($this->returnValue('any'));
    $requirement_any->expects($this->atLeastOnce())
      ->method('getConstraints')
      ->will($this->returnValue(array()));
    $requirement_specific = $this->getMock('Drupal\Core\TypedData\DataDefinitionInterface');
    $requirement_specific->expects($this->atLeastOnce())
      ->method('isRequired')
      ->will($this->returnValue(TRUE));
    $requirement_specific->expects($this->atLeastOnce())
      ->method('getDataType')
      ->will($this->returnValue('foo'));
    $requirement_specific->expects($this->atLeastOnce())
      ->method('getConstraints')
      ->will($this->returnValue(array('bar' => 'baz')));

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
  public function testFilterPluginDefinitionsByContexts($has_context, $definitions, $expected, $typed_data_definition = NULL) {
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

    if ($typed_data_definition) {
      $this->typedDataManager->expects($this->atLeastOnce())
        ->method('getDefinition')
        ->will($this->returnValueMap($typed_data_definition));
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
    $data[] = array(array(), $plugins, array());
    // Satisfied context, all plugins available.
    $data[] = array(TRUE, $plugins, $plugins);

    $mismatched_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(array('mismatched_constraint_name' => 'mismatched_constraint_value'));
    $plugins = array('expected_plugin' => array('context' => array('context1' => $mismatched_context_definition)));
    // Mismatched constraints, no plugins available.
    $data[] = array(TRUE, $plugins, array());

    $expected_context_definition = (new ContextDefinition('expected_data_type'))->setConstraints(array('expected_constraint_name' => 'expected_constraint_value'));
    $plugins = array('expected_plugin' => array('context' => array('context1' => $expected_context_definition)));
    // Satisfied context with constraint, all plugins available.
    $data[] = array(TRUE, $plugins, $plugins);

    $typed_data = array(array('expected_data_type', TRUE, array('required' => FALSE)));
    // Optional unsatisfied context from TypedData, all plugins available.
    $data[] = array(FALSE, $plugins, $plugins, $typed_data);

    $typed_data = array(array('expected_data_type', TRUE, array('required' => TRUE)));
    // Required unsatisfied context from TypedData, no plugins available.
    $data[] = array(FALSE, $plugins, array(), $typed_data);

    $typed_data = array(array('expected_data_type', TRUE, array('constraints' => array('mismatched_constraint_name' => 'mismatched_constraint_value'), 'required' => FALSE)));
    // Optional mismatched constraint from TypedData, all plugins available.
    $data[] = array(FALSE, $plugins, $plugins, $typed_data);

    $typed_data = array(array('expected_data_type', TRUE, array('constraints' => array('mismatched_constraint_name' => 'mismatched_constraint_value'), 'required' => TRUE)));
    // Required mismatched constraint from TypedData, no plugins available.
    $data[] = array(FALSE, $plugins, array(), $typed_data);

    $typed_data = array(array('expected_data_type', TRUE, array('constraints' => array('expected_constraint_name' => 'expected_constraint_value'))));
    // Satisfied constraint from TypedData, all plugins available.
    $data[] = array(TRUE, $plugins, $plugins, $typed_data);

    $unexpected_context_definition = (new ContextDefinition('unexpected_data_type'))->setConstraints(array('mismatched_constraint_name' => 'mismatched_constraint_value'));
    $plugins = array(
      'unexpected_plugin' => array('context' => array('context1' => $unexpected_context_definition)),
      'expected_plugin' => array('context' => array('context2' => new ContextDefinition('expected_data_type'))),
    );
    $typed_data = array(
      array('unexpected_data_type', TRUE, array()),
      array('expected_data_type', TRUE, array('constraints' => array('expected_constraint_name' => 'expected_constraint_value'))),
    );
    // Context only satisfies one plugin.
    $data[] = array(TRUE, $plugins, array('expected_plugin' => $plugins['expected_plugin']), $typed_data);

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
      ->method('getContextDefinitions')
      ->will($this->returnValue(array('hit' => 'hit')));
    $plugin->expects($this->never())
      ->method('setContextValue');

    $this->contextHandler->applyContextMapping($plugin, $contexts, array('name' => 'miss'));
  }

}

interface TestConfigurableContextAwarePluginInterface extends ContextAwarePluginInterface, ConfigurablePluginInterface {
}
