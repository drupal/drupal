<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\CategorizingPluginManagerTraitTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\CategorizingPluginManagerTrait
 * @group Plugin
 */
class CategorizingPluginManagerTraitTest extends UnitTestCase {

  /**
   * The plugin manager to test.
   *
   * @var \Drupal\Component\Plugin\CategorizingPluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->any())
      ->method('getModuleList')
      ->willReturn(['node' => []]);
    $module_handler->expects($this->any())
      ->method('getName')
      ->with('node')
      ->willReturn('Node');

    $this->pluginManager = new CategorizingPluginManager($module_handler);
    $this->pluginManager->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::getCategories
   */
  public function testGetCategories() {
    $this->assertSame(array_values($this->pluginManager->getCategories()), [
        'fruits',
        'vegetables',
      ]);
  }

  /**
   * @covers ::getSortedDefinitions
   */
  public function testGetSortedDefinitions() {
    $sorted = $this->pluginManager->getSortedDefinitions();
    $this->assertSame(array_keys($sorted), ['apple', 'mango', 'cucumber']);
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitions() {
    $grouped = $this->pluginManager->getGroupedDefinitions();
    $this->assertSame(array_keys($grouped), ['fruits', 'vegetables']);
    $this->assertSame(array_keys($grouped['fruits']), ['apple', 'mango']);
    $this->assertSame(array_keys($grouped['vegetables']), ['cucumber']);
  }

  /**
   * @covers ::processDefinitionCategory
   */
  public function testProcessDefinitionCategory() {
    // Existing category.
    $definition = [
      'label' => 'some',
      'provider' => 'core',
      'category' => 'bag',
    ];
    $this->pluginManager->processDefinition($definition, 'some');
    $this->assertSame($definition['category'], 'bag');

    // No category, provider without label.
    $definition = [
      'label' => 'some',
      'provider' => 'core',
    ];
    $this->pluginManager->processDefinition($definition, 'some');
    $this->assertSame($definition['category'], 'core');

    // No category, provider is module with label.
    $definition = [
      'label' => 'some',
      'provider' => 'node',
    ];
    $this->pluginManager->processDefinition($definition, 'some');
    $this->assertSame($definition['category'], 'Node');
  }

}

/**
 * Class that allows testing the trait.
 */
class CategorizingPluginManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * Replace the constructor so we can instantiate a stub.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   *
   * Provides some test definitions to the trait.
   */
  public function getDefinitions() {
    return [
      'cucumber' => [
        'label' => 'cucumber',
        'category' => 'vegetables',
      ],
      'apple' => [
        'label' => 'apple',
        'category' => 'fruits',
      ],
      'mango' => [
        'label' => 'mango',
        'category' => 'fruits',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

}
