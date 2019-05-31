<?php

namespace Drupal\Tests\views\Unit\Plugin\area;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\area\Entity;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\area\Entity
 * @group Entity
 */
class EntityTest extends UnitTestCase {

  /**
   * The tested entity area handler.
   *
   * @var \Drupal\views\Plugin\views\area\Entity
   */
  protected $entityHandler;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityDisplayRepository;

  /**
   * The mocked entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityViewBuilder;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executable;

  /**
   * The mocked display.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $display;

  /**
   * The mocked style plugin.
   *
   * @var \Drupal\views\Plugin\views\style\StylePluginBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stylePlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);
    $this->entityDisplayRepository = $this->createMock(EntityDisplayRepositoryInterface::class);
    $this->entityStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entityViewBuilder = $this->createMock('Drupal\Core\Entity\EntityViewBuilderInterface');

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $this->stylePlugin = $this->getMockBuilder('Drupal\views\Plugin\views\style\StylePluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executable->style_plugin = $this->stylePlugin;

    $this->entityHandler = new Entity([], 'entity', ['entity_type' => 'entity_test'], $this->entityTypeManager, $this->entityRepository, $this->entityDisplayRepository);

    $this->display->expects($this->any())
      ->method('getPlugin')
      ->with('style')
      ->willReturn($this->stylePlugin);
    $this->executable->expects($this->any())
      ->method('getStyle')
      ->willReturn($this->stylePlugin);

    $token = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $token->expects($this->any())
      ->method('replace')
      ->willReturnArgument(0);
    $container = new ContainerBuilder();
    $container->set('token', $token);
    \Drupal::setContainer($container);
  }

  /**
   * Ensures that the entity manager returns an entity storage.
   */
  protected function setupEntityTypeManager() {
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('entity_test')
      ->willReturn($this->entityStorage);
    $this->entityTypeManager->expects($this->any())
      ->method('getViewBuilder')
      ->with('entity_test')
      ->willReturn($this->entityViewBuilder);
  }

  /**
   * Data provider for testing different types of tokens.
   *
   * @return array
   */
  public function providerTestTokens() {
    return [
      ['{{ raw_arguments.test1 }}', 5],
      ['{{ arguments.test2 }}', 6],
      ['{{ test_render_token }}', 7],
      ['{{ test:global_token }}', 8],
    ];
  }

  /**
   * @covers ::render
   * @covers ::defineOptions
   * @covers ::init
   */
  public function testRenderWithId() {
    $this->setupEntityTypeManager();
    $options = [
      'target' => 1,
      'tokenize' => FALSE,
    ];

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('access')
      ->willReturn(TRUE);

    $this->entityStorage->expects($this->never())
      ->method('loadByProperties');
    $this->entityRepository->expects($this->any())
      ->method('loadEntityByConfigTarget')
      ->willReturn($entity);
    $this->entityViewBuilder->expects($this->once())
      ->method('view')
      ->with($entity, 'default')
      ->willReturn(['#markup' => 'hallo']);

    $this->entityHandler->init($this->executable, $this->display, $options);

    $result = $this->entityHandler->render();
    $this->assertEquals(['#markup' => 'hallo'], $result);
  }

  /**
   * @covers ::render
   * @covers ::defineOptions
   * @covers ::init
   *
   * @dataProvider providerTestTokens
   */
  public function testRenderWithIdAndToken($token, $id) {
    $this->setupEntityTypeManager();
    $options = [
      'target' => $token,
      'tokenize' => TRUE,
    ];

    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('access')
      ->willReturn(TRUE);

    $this->stylePlugin->expects($this->once())
      ->method('tokenizeValue')
      ->with($token, 0)
      ->willReturn($id);

    $this->entityStorage->expects($this->never())
      ->method('loadByProperties');
    $this->entityStorage->expects($this->once())
      ->method('load')
      ->with($id)
      ->willReturn($entity);
    $this->entityViewBuilder->expects($this->once())
      ->method('view')
      ->with($entity, 'default')
      ->willReturn(['#markup' => 'hallo']);

    $this->entityHandler->init($this->executable, $this->display, $options);

    $result = $this->entityHandler->render();
    $this->assertEquals(['#markup' => 'hallo'], $result);
  }

  /**
   * @covers ::render
   * @covers ::defineOptions
   * @covers ::init
   */
  public function testRenderWithUuid() {
    $this->setupEntityTypeManager();
    $uuid = '1d52762e-b9d8-4177-908f-572d1a5845a4';
    $options = [
      'target' => $uuid,
      'tokenize' => FALSE,
    ];
    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('access')
      ->willReturn(TRUE);

    $this->entityStorage->expects($this->never())
      ->method('load');
    $this->entityRepository->expects($this->once())
      ->method('loadEntityByConfigTarget')
      ->willReturn($entity);
    $this->entityViewBuilder->expects($this->once())
      ->method('view')
      ->with($entity, 'default')
      ->willReturn(['#markup' => 'hallo']);

    $this->entityHandler->init($this->executable, $this->display, $options);

    $result = $this->entityHandler->render();
    $this->assertEquals(['#markup' => 'hallo'], $result);
  }

  /**
   * @covers ::calculateDependencies
   *
   * @dataProvider providerTestTokens
   */
  public function testCalculateDependenciesWithPlaceholder($token, $id) {
    $this->setupEntityTypeManager();

    $options = [
      'target' => $token,
    ];
    $this->entityHandler->init($this->executable, $this->display, $options);

    $this->assertEquals([], $this->entityHandler->calculateDependencies());
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithUuid() {
    $this->setupEntityTypeManager();

    $uuid = '1d52762e-b9d8-4177-908f-572d1a5845a4';
    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getConfigDependencyName')
      ->willReturn('entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4');
    $this->entityStorage->expects($this->never())
      ->method('load');
    $this->entityRepository->expects($this->once())
      ->method('loadEntityByConfigTarget')
      ->willReturn($entity);
    $entity_type->expects($this->once())
      ->method('getConfigDependencyKey')
      ->willReturn('content');
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->willReturn($entity_type);

    $options = [
      'target' => $uuid,
    ];
    $this->entityHandler->init($this->executable, $this->display, $options);

    $this->assertEquals(['content' => ['entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4']], $this->entityHandler->calculateDependencies());
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithEntityId() {
    $this->setupEntityTypeManager();

    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getConfigDependencyName')
      ->willReturn('entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4');
    $this->entityRepository->expects($this->once())
      ->method('loadEntityByConfigTarget')
      ->willReturn($entity);
    $this->entityStorage->expects($this->never())
      ->method('loadByProperties');
    $entity_type->expects($this->once())
      ->method('getConfigDependencyKey')
      ->willReturn('content');
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->willReturn($entity_type);

    $options = [
      'target' => 1,
    ];
    $this->entityHandler->init($this->executable, $this->display, $options);

    $this->assertEquals(['content' => ['entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4']], $this->entityHandler->calculateDependencies());
  }

}
