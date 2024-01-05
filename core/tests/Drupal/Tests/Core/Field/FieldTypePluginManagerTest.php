<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Field\FieldTypePluginManager
 * @group Field
 */
class FieldTypePluginManagerTest extends UnitTestCase {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager
   */
  protected FieldTypePluginManager $fieldTypeManager;

  /**
   * A mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * A mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fieldTypeCategoryManager;

  /**
   * A mocked plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $current_user = $this->prophesize(AccountInterface::class);
    $container->set('current_user', $current_user->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->alter('field_info', Argument::any())->willReturn(NULL);
    $typed_data_manager = $this->prophesize(TypedDataManager::class);
    $this->fieldTypeCategoryManager = $this->prophesize(FieldTypeCategoryManagerInterface::class);

    $this->fieldTypeManager = new FieldTypePluginManager(new \ArrayObject(), $cache_backend->reveal(), $this->moduleHandler->reveal(), $typed_data_manager->reveal(), $this->fieldTypeCategoryManager->reveal());
    $this->fieldTypeManager->setStringTranslation($this->getStringTranslationStub());

    $this->discovery = $this->prophesize(DiscoveryInterface::class);
    $property = new \ReflectionProperty(FieldTypePluginManager::class, 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->fieldTypeManager, $this->discovery->reveal());
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitions() {
    $this->discovery->getDefinitions()->willReturn([
      'telephone' => [
        'category' => 'general',
        'label' => 'Telephone',
        'id' => 'telephone',
      ],
      'string' => [
        'category' => 'text',
        'label' => 'Text (plain)',
        'id' => 'string',
      ],
      'integer' => [
        'category' => 'number',
        'label' => 'Number (integer)',
        'id' => 'integer',
      ],
      'float' => [
        'id' => 'float',
        'label' => 'Number (float)',
        'category' => 'number',
      ],
    ]);

    $this->fieldTypeCategoryManager->getDefinitions()->willReturn([
      'general' => [
        'label' => 'General',
        'id' => 'general',
      ],
      'number' => [
        'label' => 'Number 🦥',
        'id' => 'number',
      ],
      'text' => [
        'label' => 'Text 🐈',
        'id' => 'text',
      ],
      'empty_group' => [
        'label' => 'Empty 🦗',
        'id' => 'empty_group',
      ],
    ]);

    $grouped_definitions = $this->fieldTypeManager->getGroupedDefinitions();
    $this->assertEquals(['General', 'Number 🦥', 'Text 🐈'], array_keys($grouped_definitions));

    $grouped_definitions = $this->fieldTypeManager->getGroupedDefinitions(NULL, 'label', 'id');
    $this->assertEquals(['general', 'number', 'text'], array_keys($grouped_definitions));
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitionsInvalid() {
    $this->discovery->getDefinitions()->willReturn([
      'string' => [
        'category' => 'text',
        'label' => 'Text (plain)',
        'id' => 'string',
      ],
    ]);

    $this->fieldTypeCategoryManager->getDefinitions()->willReturn([
      'general' => [
        'label' => 'General',
        'id' => 'general',
      ],
    ]);

    $zend_assertions_default = ini_get('zend.assertions');

    // Test behavior when assertions are not enabled.
    ini_set('zend.assertions', 0);
    $grouped_definitions = $this->fieldTypeManager->getGroupedDefinitions();
    $this->assertEquals(['General'], array_keys($grouped_definitions));

    // Test behavior when assertions are enabled.
    ini_set('zend.assertions', 1);
    $this->expectException(\AssertionError::class);
    try {
      $this->fieldTypeManager->getGroupedDefinitions();
    }
    catch (\Exception $e) {
      // Reset the original assert values.
      ini_set('zend.assertions', $zend_assertions_default);

      throw $e;
    }
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitionsEmpty() {
    $this->fieldTypeCategoryManager->getDefinitions()->willReturn([]);
    $this->assertEquals([], $this->fieldTypeManager->getGroupedDefinitions([]));
  }

}
