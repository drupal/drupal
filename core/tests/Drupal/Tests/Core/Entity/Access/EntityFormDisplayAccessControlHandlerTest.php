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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\Entity\Entity\Access\EntityFormDisplayAccessControlHandler.
 */
#[CoversClass(EntityFormDisplayAccessControlHandler::class)]
#[Group('Entity')]
class EntityFormDisplayAccessControlHandlerTest extends UnitTestCase {

  /**
   * The field storage config access controller to test.
   *
   * @var \Drupal\field\FieldStorageConfigAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * The mock module handler.
   */
  protected ModuleHandlerInterface&Stub $moduleHandler;

  /**
   * The mock account without field storage config access.
   */
  protected AccountInterface&Stub $anon;

  /**
   * The mock account with EntityFormDisplay access.
   */
  protected AccountInterface&Stub $member;

  /**
   * The mock account with EntityFormDisplay access via parent access check.
   */
  protected AccountInterface&Stub $parentMember;

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

    $this->anon = $this->createStub(AccountInterface::class);
    $this->anon
      ->method('hasPermission')
      ->willReturn(FALSE);
    $this->anon
      ->method('id')
      ->willReturn(0);

    $this->member = $this->createStub(AccountInterface::class);
    $this->member
      ->method('hasPermission')
      ->willReturnMap([
        ['administer foobar form display', TRUE],
        ['Llama', FALSE],
      ]);
    $this->member
      ->method('id')
      ->willReturn(2);

    $this->parentMember = $this->createStub(AccountInterface::class);
    $this->parentMember
      ->method('hasPermission')
      ->willReturnMap([
        ['Llama', TRUE],
        ['administer foobar form display', FALSE],
        ['administer foobar display', FALSE],
      ]);
    $this->parentMember
      ->method('id')
      ->willReturn(3);

    $entity_form_display_entity_type = $this->createStub(ConfigEntityTypeInterface::class);
    $entity_form_display_entity_type
      ->method('getAdminPermission')
      ->willReturn('Llama');
    $entity_form_display_entity_type
      ->method('getKey')
      ->willReturnMap([
        ['langcode', 'langcode'],
      ]);
    $entity_form_display_entity_type
      ->method('entityClassImplements')
      ->willReturn(TRUE);
    $entity_form_display_entity_type
      ->method('getConfigPrefix')
      ->willReturn('');

    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $this->moduleHandler
      ->method('invokeAll')
      ->willReturn([]);

    $storage_access_control_handler = new EntityFormDisplayAccessControlHandler($entity_form_display_entity_type);
    $storage_access_control_handler->setModuleHandler($this->moduleHandler);

    $entity_type_manager = $this->createStub(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->method('getStorage')
      ->willReturnMap([
        ['entity_display', $this->createStub(EntityStorageInterface::class)],
      ]);
    $entity_type_manager
      ->method('getAccessControlHandler')
      ->willReturnMap([
        ['entity_display', $storage_access_control_handler],
      ]);
    $entity_type_manager
      ->method('getDefinition')
      ->willReturn($entity_form_display_entity_type);

    $entity_field_manager = $this->createStub(EntityFieldManagerInterface::class);
    $entity_field_manager
      ->method('getFieldDefinitions')
      ->willReturn([]);

    $container = new Container();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_field.manager', $entity_field_manager);
    $container->set('language_manager', $this->createStub(LanguageManagerInterface::class));
    $container->set('plugin.manager.field.widget', $this->prophesize(PluginManagerInterface::class));
    $container->set('plugin.manager.field.field_type', $this->createStub(FieldTypePluginManagerInterface::class));
    $container->set('plugin.manager.field.formatter', $this->prophesize(FormatterPluginManager::class));
    $container->set('uuid', $this->createStub(UuidInterface::class));
    $container->set('renderer', $this->createStub(RendererInterface::class));
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
   * Tests access.
   *
   * @see ::access
   * @see ::checkAccess
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
