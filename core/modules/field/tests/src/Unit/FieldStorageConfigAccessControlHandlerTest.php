<?php

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

/**
 * Tests the field storage config access controller.
 *
 * @group field
 *
 * @coversDefaultClass \Drupal\field\FieldStorageConfigAccessControlHandler
 */
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

    $this->anon = $this->createMock(AccountInterface::class);
    $this->anon
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValue(FALSE));
    $this->anon
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(0));

    $this->member = $this->createMock(AccountInterface::class);
    $this->member
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
        ['administer node fields', TRUE],
      ]));
    $this->member
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $storageType = $this->createMock(ConfigEntityTypeInterface::class);
    $storageType
      ->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('field'));
    $storageType
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->will($this->returnValue('field.storage'));

    $entityType = $this->createMock(ConfigEntityTypeInterface::class);
    $entityType
      ->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('node'));
    $entityType
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->willReturn('node');

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler
      ->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue([]));
    $this->moduleHandler
      ->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValue([]));

    $storage_access_control_handler = new FieldStorageConfigAccessControlHandler($storageType);
    $storage_access_control_handler->setModuleHandler($this->moduleHandler);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
        ['field_storage_config', TRUE, $storageType],
        ['node', TRUE, $entityType],
      ]);
    $entity_type_manager
      ->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        ['field_storage_config', $this->createMock(EntityStorageInterface::class)],
      ]);
    $entity_type_manager
      ->expects($this->any())
      ->method('getAccessControlHandler')
      ->willReturnMap([
        ['field_storage_config', $storage_access_control_handler],
      ]);

    $container = new Container();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('uuid', $this->createMock(UuidInterface::class));
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
   */
  public function assertAllowOperations(array $allow_operations, AccountInterface $user) {
    foreach (['view', 'update', 'delete'] as $operation) {
      $expected = in_array($operation, $allow_operations);
      $actual = $this->accessControlHandler->access($this->entity, $operation, $user);
      $this->assertSame($expected, $actual, "Access problem with '$operation' operation.");
    }
  }

  /**
   * Ensures field storage config access is working properly.
   */
  public function testAccess() {
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
