<?php

namespace Drupal\KernelTests\Core\Plugin\Context;

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
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\ContextAwarePluginBase
 *
 * @group Plugin
 * @group legacy
 */
class ContextAwarePluginBaseTest extends KernelTestBase {

  /**
   * The plugin instance under test.
   *
   * @var \Drupal\Core\Plugin\ContextAwarePluginBase
   */
  private $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $configuration = [
      'context' => [
        'nato_letter' => 'Alpha',
      ],
    ];
    $plugin_definition = new TestPluginDefinition();
    $plugin_definition->addContextDefinition('nato_letter', ContextDefinition::create('string'));
    $this->plugin = $this->getMockBuilder(ContextAwarePluginBase::class)
      ->setConstructorArgs([$configuration, 'the_sisko', $plugin_definition])
      ->onlyMethods(['setContext'])
      ->getMockForAbstractClass();
  }

  /**
   * @covers ::getContextDefinitions
   */
  public function testGetContextDefinitions() {
    $this->assertIsArray($this->plugin->getContextDefinitions());
  }

  /**
   * @covers ::getContextDefinition
   */
  public function testGetContextDefinition() {
    // The context is not defined, so an exception will be thrown.
    $this->expectException(ContextException::class);
    $this->expectExceptionMessage('The person context is not a valid context.');
    $this->plugin->getContextDefinition('person');
  }

  /**
   * @covers ::getContextValue
   */
  public function testGetContextValue() {
    // Assert that the context value passed in the plugin configuration is
    // available.
    $this->assertSame('Alpha', $this->plugin->getContextValue('nato_letter'));
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

    $this->plugin->expects($this->exactly(1))->method('setContext');
    $this->plugin->setContextValue('foo', new StringData(new DataDefinition(), 'bar'));
  }

}

class TestPluginDefinition extends PluginDefinition implements ContextAwarePluginDefinitionInterface {

  use ContextAwarePluginDefinitionTrait;

}
