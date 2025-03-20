<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin\Context;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Context\ContextInterface as ComponentContextInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionTrait;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\KernelTests\KernelTestBase;

// cspell:ignore sisko

/**
 * @coversDefaultClass \Drupal\Core\Plugin\ContextAwarePluginTrait
 *
 * @group Plugin
 */
class ContextAwarePluginTraitTest extends KernelTestBase {

  /**
   * The plugin instance under test.
   *
   * @var \Drupal\KernelTests\Core\Plugin\Context\TestContextAwarePlugin
   */
  private $plugin;

  /**
   * The configurable plugin instance under test.
   *
   * @var \Drupal\KernelTests\Core\Plugin\Context\TestConfigurableContextAwarePlugin
   */
  private $configurablePlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $plugin_definition = new TestContextAwarePluginDefinition();
    $plugin_definition->addContextDefinition('nato_letter', ContextDefinition::create('string'));
    $this->plugin = new TestContextAwarePlugin([], 'the_sisko', $plugin_definition);
    $this->configurablePlugin = new TestConfigurableContextAwarePlugin([], 'the_sisko', $plugin_definition);
  }

  /**
   * @covers ::getContextDefinitions
   */
  public function testGetContextDefinitions(): void {
    $this->assertIsArray($this->plugin->getContextDefinitions());
  }

  /**
   * @covers ::getContextDefinition
   */
  public function testGetContextDefinition(): void {
    // The context is not defined, so an exception will be thrown.
    $this->expectException(ContextException::class);
    $this->expectExceptionMessage('The person context is not a valid context.');
    $this->plugin->getContextDefinition('person');
  }

  /**
   * @covers ::getContextValue
   */
  public function testGetContextValue(): void {
    $this->plugin->setContextValue('nato_letter', 'Alpha');
    $this->assertSame('Alpha', $this->plugin->getContextValue('nato_letter'));
  }

  /**
   * @covers ::setContextValue
   */
  public function testSetContextValue(): void {
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

/**
 * A plugin definition test class.
 */
class TestContextAwarePluginDefinition extends PluginDefinition implements ContextAwarePluginDefinitionInterface {

  use ContextAwarePluginDefinitionTrait;

}

/**
 * Context aware plugin test class.
 */
class TestContextAwarePlugin extends PluginBase implements ContextAwarePluginInterface {

  use ContextAwarePluginTrait {
    setContext as setContextTrait;
  }

  /**
   * Indicates if ::setContext() has been called or not.
   *
   * @var bool
   */
  public $setContextCalled = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setContext($name, ComponentContextInterface $context): void {
    $this->setContextTrait($name, $context);
    $this->setContextCalled = TRUE;
  }

}

/**
 * Configurable context aware plugin test class.
 */
class TestConfigurableContextAwarePlugin extends PluginBase implements ConfigurableInterface, ContextAwarePluginInterface {

  use ContextAwarePluginTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'context' => [
        'nato_letter' => 'Alpha',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
  }

}
