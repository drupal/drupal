<?php

namespace Drupal\Tests\field\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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
   * The mocked test field storage config.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * The main entity used for testing.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->anon = $this->getMock(AccountInterface::class);
    $this->anon
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValue(FALSE));
    $this->anon
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(0));

    $this->member = $this->getMock(AccountInterface::class);
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

    $storageType = $this->getMock(ConfigEntityTypeInterface::class);
    $storageType
      ->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('field'));
    $storageType
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->will($this->returnValue('field.storage'));

    $entityType = $this->getMock(ConfigEntityTypeInterface::class);
    $entityType
      ->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('node'));
    $entityType
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->willReturn('node');

    $this->moduleHandler = $this->getMock(ModuleHandlerInterface::class);
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

    $entityManager = $this->getMock(EntityManagerInterface::class);
    $entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
        ['field_storage_config', TRUE, $storageType],
        ['node', TRUE, $entityType],
      ]);
    $entityManager
      ->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        ['field_storage_config', $this->getMock(EntityStorageInterface::class)],
      ]);
    $entityManager
      ->expects($this->any())
      ->method('getAccessControlHandler')
      ->willReturnMap([
        ['field_storage_config', $storage_access_control_handler],
      ]);

    $container = new Container();
    $container->set('entity.manager', $entityManager);
    $container->set('uuid', $this->getMock(UuidInterface::class));
    $container->set('cache_contexts_manager', $this->prophesize(CacheContextsManager::class));
    \Drupal::setContainer($container);

    $this->fieldStorage = new FieldStorageConfig([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'type' => 'boolean',
      'id' => 'node.test_field',
      'uuid' => '6f2f259a-f3c7-42ea-bdd5-111ad1f85ed1',
    ]);

    $this->entity = $this->fieldStorage;
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

    $this->fieldStorage->setLocked(TRUE)->save();
    // Unfortunately, EntityAccessControlHandler has a static cache, which we
    // therefore must reset manually.
    $this->accessControlHandler->resetCache();

    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update'], $this->member);
  }

}
