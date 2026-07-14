<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\locale\LocaleConfigManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the functionality provided by the configuration entity mapper.
 */
#[Group('config_translation')]
class ConfigEntityMapperTest extends UnitTestCase {

  /**
   * The configuration entity mapper to test.
   *
   * @var \Drupal\config_translation\ConfigEntityMapper
   */
  protected $configEntityMapper;

  /**
   * The entity type manager used for testing.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The route provider used for testing.
   */
  protected RouteProviderInterface&MockObject $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');

    $this->routeProvider = $this->createMock(RouteProviderInterface::class);

    $this->routeProvider
      ->method('getRouteByName')
      ->with('entity.configurable_language.edit_form')
      ->willReturn(new Route('/admin/config/regional/language/edit/{configurable_language}'));

    $definition = [
      'class' => '\Drupal\config_translation\ConfigEntityMapper',
      'base_route_name' => 'entity.configurable_language.edit_form',
      'title' => '@label language',
      'names' => [],
      'entity_type' => 'configurable_language',
      'route_name' => 'config_translation.item.overview.entity.configurable_language.edit_form',
    ];

    $this->configEntityMapper = new ConfigEntityMapper(
      'configurable_language',
      $definition,
      $this->getConfigFactoryStub(),
      $this->createStub(TypedConfigManagerInterface::class),
      $this->createStub(LocaleConfigManager::class),
      $this->createStub(ConfigMapperManagerInterface::class),
      $this->routeProvider,
      $this->getStringTranslationStub(),
      $this->entityTypeManager,
      $this->createStub(LanguageManagerInterface::class),
      $this->createStub(EventDispatcherInterface::class)
    );
  }

  /**
   * Tests ConfigEntityMapper::setEntity() and ConfigEntityMapper::getEntity().
   */
  public function testEntityGetterAndSetter(): void {
    $entity = $this->createMock(ConfigEntityInterface::class);
    $entity
      ->expects($this->once())
      ->method('id')
      ->with()
      ->willReturn('entity_id');

    $entity_type = $this->createStub(ConfigEntityTypeInterface::class);
    $entity_type
      ->method('getConfigPrefix')
      ->willReturn('config_prefix');
    $this->entityTypeManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->willReturn($entity_type);

    // No entity is set.
    $this->assertNull($this->configEntityMapper->getEntity());

    $result = $this->configEntityMapper->setEntity($entity);
    $this->assertTrue($result);

    // Ensure that the getter provides the entity.
    $this->assertEquals($entity, $this->configEntityMapper->getEntity());

    // Ensure that the configuration name was added to the mapper.
    $plugin_definition = $this->configEntityMapper->getPluginDefinition();
    $this->assertContains('config_prefix.entity_id', $plugin_definition['names']);

    // Make sure setEntity() returns FALSE when called a second time.
    $result = $this->configEntityMapper->setEntity($entity);
    $this->assertFalse($result);
  }

  /**
   * Tests ConfigEntityMapper::getOverviewRouteParameters().
   */
  public function testGetOverviewRouteParameters(): void {
    $entity = $this->createMock(ConfigEntityInterface::class);
    $entity->expects($this->atLeastOnce())
      ->method('id')
      ->with()
      ->willReturn('entity_id');

    $entity_type = $this->createStub(ConfigEntityTypeInterface::class);
    $this->entityTypeManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->willReturn($entity_type);
    $this->configEntityMapper->setEntity($entity);

    $result = $this->configEntityMapper->getOverviewRouteParameters();

    $this->assertSame(['configurable_language' => 'entity_id'], $result);
  }

  /**
   * Tests ConfigEntityMapper::getType().
   */
  public function testGetType(): void {
    $this->entityTypeManager->expects($this->never())
      ->method('getDefinition');
    $result = $this->configEntityMapper->getType();
    $this->assertSame('configurable_language', $result);
  }

  /**
   * Tests ConfigEntityMapper::getTypeName().
   */
  public function testGetTypeName(): void {
    $entity_type = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLabel')
      ->willReturn('test');
    $this->entityTypeManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->willReturn($entity_type);

    $result = $this->configEntityMapper->getTypeName();
    $this->assertSame('test', $result);
  }

  /**
   * Tests ConfigEntityMapper::getTypeLabel().
   */
  public function testGetTypeLabel(): void {
    $entity_type = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLabel')
      ->willReturn('test');
    $this->entityTypeManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->willReturn($entity_type);

    $result = $this->configEntityMapper->getTypeLabel();
    $this->assertSame('test', $result);
  }

  /**
   * Tests ConfigEntityMapper::getOperations().
   */
  public function testGetOperations(): void {
    $this->entityTypeManager->expects($this->never())
      ->method('getDefinition');
    $result = $this->configEntityMapper->getOperations();

    $expected = [
      'list' => [
        'title' => 'List',
        'url' => Url::fromRoute('config_translation.entity_list', ['mapper_id' => 'configurable_language']),
      ],
    ];

    $this->assertEquals($expected, $result);
  }

}
