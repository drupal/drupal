<?php

namespace Drupal\Tests\Core\Entity\Access;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\Entity\Access\EntityFormDisplayAccessControlHandler;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManager;
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
  protected $parent_member;

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
        ['administer foobar form display', TRUE],
      ]));
    $this->member
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(2));

    $this->parent_member = $this->getMock(AccountInterface::class);
    $this->parent_member
      ->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
        ['Llama', TRUE],
      ]));
    $this->parent_member
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue(3));

    $entity_form_display_entity_type = $this->getMock(ConfigEntityTypeInterface::class);
    $entity_form_display_entity_type->expects($this->any())
      ->method('getAdminPermission')
      ->will($this->returnValue('Llama'));
    $entity_form_display_entity_type
      ->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap([
        ['langcode', 'langcode'],
      ]));
    $entity_form_display_entity_type->expects($this->any())
      ->method('entityClassImplements')
      ->will($this->returnValue(TRUE));
    $entity_form_display_entity_type->expects($this->any())
      ->method('getConfigPrefix')
      ->willReturn('');

    $this->moduleHandler = $this->getMock(ModuleHandlerInterface::class);
    $this->moduleHandler
      ->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue([]));
    $this->moduleHandler
      ->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValue([]));

    $storage_access_control_handler = new EntityFormDisplayAccessControlHandler($entity_form_display_entity_type);
    $storage_access_control_handler->setModuleHandler($this->moduleHandler);

    $entity_type_manager = $this->getMock(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        ['entity_display', $this->getMock(EntityStorageInterface::class)],
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
      ->will($this->returnValue($entity_form_display_entity_type));

    $entity_field_manager = $this->getMock(EntityFieldManagerInterface::class);
    $entity_field_manager->expects($this->any())
      ->method('getFieldDefinitions')
      ->will($this->returnValue([]));

    $entity_manager = new EntityManager();
    $container = new Container();
    $container->set('entity.manager', $entity_manager);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_field.manager', $entity_field_manager);
    $container->set('language_manager', $this->getMock(LanguageManagerInterface::class));
    $container->set('plugin.manager.field.widget', $this->prophesize(PluginManagerInterface::class));
    $container->set('plugin.manager.field.field_type', $this->getMock(FieldTypePluginManagerInterface::class));
    $container->set('plugin.manager.field.formatter', $this->prophesize(FormatterPluginManager::class));
    $container->set('uuid', $this->getMock(UuidInterface::class));
    $container->set('renderer', $this->getMock(RendererInterface::class));
    $container->set('cache_contexts_manager', $this->prophesize(CacheContextsManager::class));
    // Inject the container into entity.manager so it can defer to
    // entity_type.manager.
    $entity_manager->setContainer($container);
    \Drupal::setContainer($container);

    $this->entity = new EntityFormDisplay([
      'targetEntityType' => 'foobar',
      'bundle' => 'bazqux',
      'mode' => 'default',
      'id' => 'foobar.bazqux.default',
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
   */
  public function assertAllowOperations(array $allow_operations, AccountInterface $user) {
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
  public function testAccess() {
    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update', 'delete'], $this->member);
    $this->assertAllowOperations(['view', 'update', 'delete'], $this->parent_member);

    $this->entity->enforceIsNew(TRUE)->save();
    // Unfortunately, EntityAccessControlHandler has a static cache, which we
    // therefore must reset manually.
    $this->accessControlHandler->resetCache();

    $this->assertAllowOperations([], $this->anon);
    $this->assertAllowOperations(['view', 'update'], $this->member);
    $this->assertAllowOperations(['view', 'update'], $this->parent_member);
  }

}
