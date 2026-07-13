<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin\Context;

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore sisko
/**
 * Tests Drupal\Core\Plugin\ContextAwarePluginTrait.
 */
#[CoversClass(ContextAwarePluginTrait::class)]
#[Group('Plugin')]
#[RunTestsInSeparateProcesses]
class ContextAwarePluginTraitTest extends KernelTestBase {

  /**
   * The plugin instance under test.
   */
  private TestContextAwarePlugin $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $plugin_definition = new TestContextAwarePluginDefinition();
    $plugin_definition->addContextDefinition('nato_letter', ContextDefinition::create('string'));
    $this->plugin = new TestContextAwarePlugin([], 'the_sisko', $plugin_definition);
  }

  /**
   * Tests get context definitions.
   */
  public function testGetContextDefinitions(): void {
    $this->assertIsArray($this->plugin->getContextDefinitions());
  }

  /**
   * Tests get context definition.
   */
  public function testGetContextDefinition(): void {
    // The context is not defined, so an exception will be thrown.
    $this->expectException(ContextException::class);
    $this->expectExceptionMessageIs('The person context is not a valid context.');
    $this->plugin->getContextDefinition('person');
  }

  /**
   * Tests get context value.
   */
  public function testGetContextValue(): void {
    $this->plugin->setContextValue('nato_letter', 'Alpha');
    $this->assertSame('Alpha', $this->plugin->getContextValue('nato_letter'));
  }

  /**
   * Tests set context value.
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
