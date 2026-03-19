<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigAccessControlHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the field storage config access controller.
 */
#[CoversClass(FieldStorageConfigAccessControlHandler::class)]
#[Group('field')]
class FieldStorageConfigAccessControlHandlerTest extends UnitTestCase {

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
   * The mock account with field storage config access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $member;

  /**
   * The FieldStorageConfig entity used for testing.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $entity;

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
        ['administer node fields', TRUE],
      ]);
    $this->member
      ->method('id')
      ->willReturn(2);

    $storageType = $this->createStub(ConfigEntityTypeInterface::class);
    $storageType
      ->method('getProvider')
      ->willReturn('field');
    $storageType
      ->method('getConfigPrefix')
      ->willReturn('field.storage');

    $entityType = $this->createStub(ConfigEntityTypeInterface::class);
    $entityType
      ->method('getProvider')
      ->willReturn('node');
    $entityType
      ->method('getConfigPrefix')
      ->willReturn('node');

    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $this->moduleHandler
      ->method('invokeAll')
      ->willReturn([]);

    $storage_access_control_handler = new FieldStorageConfigAccessControlHandler($storageType);
    $storage_access_control_handler->setModuleHandler($this->moduleHandler);

    $entity_type_manager = $this->createStub(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->method('getDefinition')
      ->willReturnMap([
        ['field_storage_config', TRUE, $storageType],
        ['node', TRUE, $entityType],
      ]);
    $entity_type_manager
      ->method('getStorage')
      ->willReturnMap([
        ['field_storage_config', $this->createStub(EntityStorageInterface::class)],
      ]);
    $entity_type_manager
      ->method('getAccessControlHandler')
      ->willReturnMap([
        ['field_storage_config', $storage_access_control_handler],
      ]);

    $container = new Container();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('uuid', $this->createStub(UuidInterface::class));
    $container->set('cache_contexts_manager', $this->prophesize(CacheContextsManager::class));
    \Drupal::setContainer($container);

    $this->entity = new FieldStorageConfig([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'type' => 'boolean',
      'id' => 'node.test_field',
      'uuid' => '6f2f259a-f3c7-42ea-bdd5-111ad1f85ed1',
    ]);

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
   * Ensures field storage config access is working properly.
   */
  public function testAccess(): void {
    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update', 'delete'], $this->member);

    $this->entity->setLocked(TRUE)->save();
    // Unfortunately, EntityAccessControlHandler has a static cache, which we
    // therefore must reset manually.
    $this->accessControlHandler->resetCache();

    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update'], $this->member);
  }

}
