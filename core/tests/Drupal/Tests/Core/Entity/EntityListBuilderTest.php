<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\entity_test\EntityTestListBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityListBuilder
 * @group Entity
 */
class EntityListBuilderTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The module handler used for testing.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The translation manager used for testing.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The role storage used for testing.
   *
   * @var \Drupal\user\RoleStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $roleStorage;

  /**
   * The service container used for testing.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The entity used to construct the EntityListBuilder.
   *
   * @var \Drupal\user\RoleInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $role;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $redirectDestination;

  /**
   * The EntityListBuilder object to test.
   *
   * @var \Drupal\Core\Entity\EntityListBuilder
   */
  protected $entityListBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->role = $this->createMock('Drupal\user\RoleInterface');
    $this->roleStorage = $this->createMock('\Drupal\user\RoleStorageInterface');
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->translationManager = $this->createMock('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->entityListBuilder = new TestEntityListBuilder($this->entityType, $this->roleStorage);
    $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);
    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers ::getOperations
   */
  public function testGetOperations(): void {
    $operation_name = $this->randomMachineName();
    $operations = [
      $operation_name => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('entity_operation', [$this->role])
      ->willReturn($operations);
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('entity_operation');

    $this->container->set('module_handler', $this->moduleHandler);

    $this->role->expects($this->any())
      ->method('access')
      ->willReturn(AccessResult::allowed());
    $this->role->expects($this->any())
      ->method('hasLinkTemplate')
      ->willReturn(TRUE);
    $url = Url::fromRoute('entity.user_role.collection');
    $this->role->expects($this->any())
      ->method('toUrl')
      ->willReturn($url);

    $this->redirectDestination->expects($this->atLeastOnce())
      ->method('getAsArray')
      ->willReturn(['destination' => '/foo/bar']);

    $list = new EntityListBuilder($this->entityType, $this->roleStorage);
    $list->setStringTranslation($this->translationManager);
    $list->setRedirectDestination($this->redirectDestination);

    $operations = $list->getOperations($this->role);
    $this->assertIsArray($operations);
    $this->assertArrayHasKey('edit', $operations);
    $this->assertIsArray($operations['edit']);
    $this->assertArrayHasKey('title', $operations['edit']);
    $this->assertArrayHasKey('delete', $operations);
    $this->assertIsArray($operations['delete']);
    $this->assertArrayHasKey('title', $operations['delete']);
    $this->assertArrayHasKey($operation_name, $operations);
    $this->assertIsArray($operations[$operation_name]);
    $this->assertArrayHasKey('title', $operations[$operation_name]);
  }

}

class TestEntityListBuilder extends EntityTestListBuilder {

  public function buildOperations(EntityInterface $entity) {
    return [];
  }

}
