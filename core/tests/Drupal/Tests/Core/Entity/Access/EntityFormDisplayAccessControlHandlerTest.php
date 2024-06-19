<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\Access;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\Entity\Access\EntityFormDisplayAccessControlHandler;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity\Access\EntityFormDisplayAccessControlHandler
 * @group Entity
 */
class EntityFormDisplayAccessControlHandlerTest extends UnitTestCase {

  /**
   * The field storage config access controller to test.
   *
   * @var \Drupal\field\FieldStorageConfigAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * The mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The mock account without field storage config access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anon;

  /**
   * The mock account with EntityFormDisplay access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $member;

  /**
   * The mock account with EntityFormDisplay access via parent access check.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $parentMember;

  /**
   * The EntityFormDisplay entity used for testing.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entity;

  /**
   * Returns a mock Entity Type Manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The mocked entity type manager.
   */
  protected function getEntityTypeManager() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    return $entity_type_manager->reveal();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->anon = $this->createMock(AccountInterface::class);
    $this->anon
      ->expects($this->any())
      ->method('hasPermission')
      ->willReturn(FALSE);
    $this->anon
      ->expects($this->any())
      ->method('id')
      ->willReturn(0);

    $this->member = $this->createMock(AccountInterface::class);
    $this->member
      ->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap([
        ['administer foobar form display', TRUE],
      ]);
    $this->member
      ->expects($this->any())
      ->method('id')
      ->willReturn(2);

    $this->parentMember = $this->createMock(AccountInterface::class);
    $this->parentMember
      ->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap([
        ['Llama', TRUE],
      ]);
    $this->parentMember
      ->expects($this->any())
      ->method('id')
      ->willReturn(3);

    $entity_form_display_entity_type = $this->createMock(ConfigEntityTypeInterface::class);
    $entity_form_display_entity_type->expects($this->any())
      ->method('getAdminPermission')
      ->willReturn('Llama');
    $entity_form_display_entity_type
      ->expects($this->any())
      ->method('getKey')
      ->willReturnMap([
        ['langcode', 'langcode'],
      ]);
    $entity_form_display_entity_type->expects($this->any())
      ->method('entityClassImplements')
      ->willReturn(TRUE);
    $entity_form_display_entity_type->expects($this->any())
      ->method('getConfigPrefix')
      ->willReturn('');

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler
      ->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);

    $storage_access_control_handler = new EntityFormDisplayAccessControlHandler($entity_form_display_entity_type);
    $storage_access_control_handler->setModuleHandler($this->moduleHandler);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        ['entity_display', $this->createMock(EntityStorageInterface::class)],
      ]);
    $entity_type_manager
      ->expects($this->any())
      ->method('getAccessControlHandler')
      ->willReturnMap([
        ['entity_display', $storage_access_control_handler],
      ]);
    $entity_type_manager
      ->expects($this->any())
      ->method('getDefinition')
      ->willReturn($entity_form_display_entity_type);

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_field_manager->expects($this->any())
      ->method('getFieldDefinitions')
      ->willReturn([]);

    $container = new Container();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_field.manager', $entity_field_manager);
    $container->set('language_manager', $this->createMock(LanguageManagerInterface::class));
    $container->set('plugin.manager.field.widget', $this->prophesize(PluginManagerInterface::class));
    $container->set('plugin.manager.field.field_type', $this->createMock(FieldTypePluginManagerInterface::class));
    $container->set('plugin.manager.field.formatter', $this->prophesize(FormatterPluginManager::class));
    $container->set('uuid', $this->createMock(UuidInterface::class));
    $container->set('renderer', $this->createMock(RendererInterface::class));
    $container->set('cache_contexts_manager', $this->prophesize(CacheContextsManager::class));
    \Drupal::setContainer($container);

    $this->entity = new EntityFormDisplay([
      'targetEntityType' => 'foobar',
      'bundle' => 'new_bundle',
      'mode' => 'default',
      'id' => 'foobar.new_bundle.default',
      'uuid' => '6f2f259a-f3c7-42ea-bdd5-111ad1f85ed1',
    ], 'entity_display');

    $this->accessControlHandler = $storage_access_control_handler;
  }

  /**
   * Assert method to verify the access by operations.
   *
   * @param array $allow_operations
   *   A list of allowed operations.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account to use for get access.
   *
   * @internal
   */
  public function assertAllowOperations(array $allow_operations, AccountInterface $user): void {
    foreach (['view', 'update', 'delete'] as $operation) {
      $expected = in_array($operation, $allow_operations);
      $actual = $this->accessControlHandler->access($this->entity, $operation, $user);
      $this->assertSame($expected, $actual, "Access problem with '$operation' operation.");
    }
  }

  /**
   * @covers ::access
   * @covers ::checkAccess
   */
  public function testAccess(): void {
    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update', 'delete'], $this->member);
    $this->assertAllowOperations(['view', 'update', 'delete'], $this->parentMember);

    $this->entity->enforceIsNew(TRUE)->save();
    // Unfortunately, EntityAccessControlHandler has a static cache, which we
    // therefore must reset manually.
    $this->accessControlHandler->resetCache();

    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update'], $this->member);
    $this->assertAllowOperations(['view', 'update'], $this->parentMember);
  }

}
