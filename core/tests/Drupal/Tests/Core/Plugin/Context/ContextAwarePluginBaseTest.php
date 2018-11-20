<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionTrait;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\ContextAwarePluginBase
 * @group Plugin
 */
class ContextAwarePluginBaseTest extends UnitTestCase {

  /**
   * The plugin instance under test.
   *
   * @var \Drupal\Core\Plugin\ContextAwarePluginBase
   */
  private $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->plugin = new TestContextAwarePlugin([], 'the_sisko', new TestPluginDefinition());
  }

  /**
   * @covers ::getContextDefinitions
   */
  public function testGetContextDefinitions() {
    $this->assertInternalType('array', $this->plugin->getContextDefinitions());
  }

  /**
   * @covers ::getContextDefinition
   */
  public function testGetContextDefinition() {
    // The context is not defined, so an exception will be thrown.
    $this->setExpectedException(ContextException::class, 'The person context is not a valid context.');
    $this->plugin->getContextDefinition('person');
  }

  /**
   * @covers ::setContextValue
   */
  public function testSetContextValue() {
    $typed_data_manager = $this->prophesize(TypedDataManagerInterface::class);
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typed_data_manager->reveal());
    \Drupal::setContainer($container);

    $this->plugin->getPluginDefinition()->addContextDefinition('foo', new ContextDefinition('string'));

    $this->assertFalse($this->plugin->setContextCalled);
    $this->plugin->setContextValue('foo', new StringData(new DataDefinition(), 'bar'));
    $this->assertTrue($this->plugin->setContextCalled);
  }

}

class TestPluginDefinition extends PluginDefinition implements ContextAwarePluginDefinitionInterface {

  use ContextAwarePluginDefinitionTrait;

}

class TestContextAwarePlugin extends ContextAwarePluginBase {

  /**
   * Indicates if ::setContext() has been called or not.
   *
   * @var bool
   */
  public $setContextCalled = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setContext($name, ComponentContextInterface $context) {
    parent::setContext($name, $context);
    $this->setContextCalled = TRUE;
  }

}
