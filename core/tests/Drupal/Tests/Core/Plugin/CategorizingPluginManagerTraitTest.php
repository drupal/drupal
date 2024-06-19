<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\CategorizingPluginManagerTrait
 * @group Plugin
 * @runTestsInSeparateProcesses
 */
class CategorizingPluginManagerTraitTest extends UnitTestCase {

  /**
   * The plugin manager to test.
   *
   * @var \Drupal\Component\Plugin\CategorizingPluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->any())
      ->method('getModuleList')
      ->willReturn(['node' => []]);
    $module_extension_list = $this->createMock(ModuleExtensionList::class);
    $module_extension_list->expects($this->any())
      ->method('getName')
      ->willReturnCallback(function ($argument) {
        if ($argument == 'node') {
          return 'Node';
        }
        throw new UnknownExtensionException();
      });
    $this->pluginManager = new CategorizingPluginManager($module_handler, $module_extension_list);
    $this->pluginManager->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::getCategories
   */
  public function testGetCategories(): void {
    $this->assertSame([
      'fruits',
      'vegetables',
    ], array_values($this->pluginManager->getCategories()));
  }

  /**
   * @covers ::getSortedDefinitions
   */
  public function testGetSortedDefinitions(): void {
    $sorted = $this->pluginManager->getSortedDefinitions();
    $this->assertSame(['apple', 'mango', 'cucumber'], array_keys($sorted));
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitions(): void {
    $grouped = $this->pluginManager->getGroupedDefinitions();
    $this->assertSame(['fruits', 'vegetables'], array_keys($grouped));
    $this->assertSame(['apple', 'mango'], array_keys($grouped['fruits']));
    $this->assertSame(['cucumber'], array_keys($grouped['vegetables']));
  }

  /**
   * @covers ::processDefinitionCategory
   */
  public function testProcessDefinitionCategory(): void {
    // Existing category.
    $definition = [
      'label' => 'some',
      'provider' => 'core',
      'category' => 'bag',
    ];
    $this->pluginManager->processDefinition($definition, 'some');
    $this->assertSame('bag', $definition['category']);

    // No category, provider without label.
    $definition = [
      'label' => 'some',
      'provider' => 'core',
    ];
    $this->pluginManager->processDefinition($definition, 'some');
    $this->assertSame('core', $definition['category']);

    // No category, provider is module with label.
    $definition = [
      'label' => 'some',
      'provider' => 'node',
    ];
    $this->pluginManager->processDefinition($definition, 'some');
    $this->assertSame('Node', $definition['category']);
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ModuleExtensionList $module_extension_list) {
    $this->moduleHandler = $module_handler;
    $this->moduleExtensionList = $module_extension_list;
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
