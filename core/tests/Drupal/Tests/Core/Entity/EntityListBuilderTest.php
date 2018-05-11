<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityListBuilderTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\entity_test\EntityTestListBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_test\EntityTestListBuilder
 * @group Entity
 */
class EntityListBuilderTest extends UnitTestCase {

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The module handler used for testing.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\user\RoleStorageInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \Drupal\user\RoleInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $role;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit_Framework_MockObject_MockObject
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
  protected function setUp() {
    parent::setUp();

    $this->role = $this->getMock('Drupal\user\RoleInterface');
    $this->roleStorage = $this->getMock('\Drupal\user\RoleStorageInterface');
    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->translationManager = $this->getMock('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->entityListBuilder = new TestEntityListBuilder($this->entityType, $this->roleStorage);
    $this->redirectDestination = $this->getMock(RedirectDestinationInterface::class);
    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers ::getOperations
   */
  public function testGetOperations() {
    $operation_name = $this->randomMachineName();
    $operations = [
      $operation_name => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('entity_operation', [$this->role])
      ->will($this->returnValue($operations));
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('entity_operation');

    $this->container->set('module_handler', $this->moduleHandler);

    $this->role->expects($this->any())
      ->method('access')
      ->will($this->returnValue(AccessResult::allowed()));
    $this->role->expects($this->any())
      ->method('hasLinkTemplate')
      ->will($this->returnValue(TRUE));
    $url = $this->getMockBuilder('\Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();
    $url->expects($this->atLeastOnce())
      ->method('mergeOptions')
      ->with(['query' => ['destination' => '/foo/bar']]);
    $this->role->expects($this->any())
      ->method('toUrl')
      ->will($this->returnValue($url));

    $this->redirectDestination->expects($this->atLeastOnce())
      ->method('getAsArray')
      ->willReturn(['destination' => '/foo/bar']);

    $list = new EntityListBuilder($this->entityType, $this->roleStorage);
    $list->setStringTranslation($this->translationManager);
    $list->setRedirectDestination($this->redirectDestination);

    $operations = $list->getOperations($this->role);
    $this->assertInternalType('array', $operations);
    $this->assertArrayHasKey('edit', $operations);
    $this->assertInternalType('array', $operations['edit']);
    $this->assertArrayHasKey('title', $operations['edit']);
    $this->assertArrayHasKey('delete', $operations);
    $this->assertInternalType('array', $operations['delete']);
    $this->assertArrayHasKey('title', $operations['delete']);
    $this->assertArrayHasKey($operation_name, $operations);
    $this->assertInternalType('array', $operations[$operation_name]);
    $this->assertArrayHasKey('title', $operations[$operation_name]);
  }

}

class TestEntityListBuilder extends EntityTestListBuilder {

  public function buildOperations(EntityInterface $entity) {
    return [];
  }

}
