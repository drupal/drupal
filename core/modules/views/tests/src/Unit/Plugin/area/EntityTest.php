<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\area;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\area\Entity;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests Drupal\views\Plugin\views\area\Entity.
 */
#[CoversClass(Entity::class)]
#[Group('Entity')]
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityDisplayRepository;

  /**
   * The mocked entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The mocked entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityViewBuilder;

  /**
   * The mocked view executable.
   */
  protected ViewExecutable&MockObject $executable;

  /**
   * The mocked display.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  protected $display;

  /**
   * The mocked style plugin.
   */
  protected StylePluginBase&MockObject $stylePlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createStub(EntityTypeManagerInterface::class);
    $this->entityRepository = $this->createStub(EntityRepositoryInterface::class);
    $this->entityDisplayRepository = $this->createStub(EntityDisplayRepositoryInterface::class);
    $this->entityStorage = $this->createStub(EntityStorageInterface::class);
    $this->entityViewBuilder = $this->createStub(EntityViewBuilderInterface::class);

    $this->display = $this->createStub(DisplayPluginBase::class);

    $this->entityHandler = new Entity([], 'entity', ['entity_type' => 'entity_test'], $this->entityTypeManager, $this->entityRepository, $this->entityDisplayRepository);

    $token = $this->createStub(Token::class);
    $token
      ->method('replace')
      ->willReturnArgument(0);
    $container = new ContainerBuilder();
    $container->set('token', $token);
    \Drupal::setContainer($container);
  }

  /**
   * Reinitializes the entity repository as a mock object.
   */
  protected function setUpMockEntityRepository(): void {
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);
    $reflection = new \ReflectionProperty($this->entityHandler, 'entityRepository');
    $reflection->setValue($this->entityHandler, $this->entityRepository);
  }

  /**
   * Ensures that the entity type manager returns an entity storage.
   */
  protected function setupEntityTypeManager(): void {
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->with('entity_test')
      ->willReturn($this->entityStorage);
    $this->entityTypeManager
      ->method('getViewBuilder')
      ->with('entity_test')
      ->willReturn($this->entityViewBuilder);
    $reflection = new \ReflectionProperty($this->entityHandler, 'entityTypeManager');
    $reflection->setValue($this->entityHandler, $this->entityTypeManager);
  }

  /**
   * Reinitializes the style plugin as a mock object.
   */
  protected function setUpMockStylePlugin(): void {
    $this->stylePlugin = $this->createMock(StylePluginBase::class);

    $this->display = $this->createMock(DisplayPluginBase::class);
    $this->display
      ->method('getPlugin')
      ->with('style')
      ->willReturn($this->stylePlugin);

    $this->executable = $this->createMock(ViewExecutable::class);
    $this->executable->style_plugin = $this->stylePlugin;
    $this->executable->expects($this->atLeastOnce())
      ->method('getStyle')
      ->willReturn($this->stylePlugin);
  }

  /**
   * Data provider for testing different types of tokens.
   *
   * @return array
   *   An array of test data.
   */
  public static function providerTestTokens(): array {
    return [
      ['{{ raw_arguments.test1 }}', 5],
      ['{{ arguments.test2 }}', 6],
      ['{{ test_render_token }}', 7],
      ['{{ test:global_token }}', 8],
    ];
  }

  /**
   * Tests render with id.
   *
   * @legacy-covers ::render
   * @legacy-covers ::defineOptions
   * @legacy-covers ::init
   */
  public function testRenderWithId(): void {
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityViewBuilder = $this->createMock(EntityViewBuilderInterface::class);
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
    $this->entityRepository
      ->method('loadEntityByConfigTarget')
      ->willReturn($entity);
    $this->entityViewBuilder
      ->method('view')
      ->with($entity, 'default')
      ->willReturn(['#markup' => 'hallo']);

    $this->entityHandler->init($this->createStub(ViewExecutable::class), $this->display, $options);

    $result = $this->entityHandler->render();
    $this->assertEquals(['#markup' => 'hallo'], $result);
  }

  /**
   * Tests render with id and token.
   *
   * @legacy-covers ::render
   * @legacy-covers ::defineOptions
   * @legacy-covers ::init
   */
  #[DataProvider('providerTestTokens')]
  public function testRenderWithIdAndToken(string $token, int $id): void {
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityViewBuilder = $this->createMock(EntityViewBuilderInterface::class);
    $this->setupEntityTypeManager();
    $this->setUpMockStylePlugin();
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
   * Tests render with uuid.
   *
   * @legacy-covers ::render
   * @legacy-covers ::defineOptions
   * @legacy-covers ::init
   */
  public function testRenderWithUuid(): void {
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityViewBuilder = $this->createMock(EntityViewBuilderInterface::class);
    $this->setupEntityTypeManager();
    $this->setUpMockEntityRepository();
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

    $this->entityHandler->init($this->createStub(ViewExecutable::class), $this->display, $options);

    $result = $this->entityHandler->render();
    $this->assertEquals(['#markup' => 'hallo'], $result);
  }

  /**
   * Tests calculate dependencies with placeholder.
   */
  #[DataProvider('providerTestTokens')]
  public function testCalculateDependenciesWithPlaceholder(string $token, int $id): void {
    $this->setupEntityTypeManager();

    $options = [
      'target' => $token,
    ];
    $this->entityHandler->init($this->createStub(ViewExecutable::class), $this->display, $options);

    $this->assertEquals([], $this->entityHandler->calculateDependencies());
  }

  /**
   * Tests calculate dependencies with uuid.
   */
  public function testCalculateDependenciesWithUuid(): void {
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->setupEntityTypeManager();
    $this->setUpMockEntityRepository();

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
    $this->entityTypeManager
      ->method('getDefinition')
      ->willReturn($entity_type);

    $options = [
      'target' => $uuid,
    ];
    $this->entityHandler->init($this->createStub(ViewExecutable::class), $this->display, $options);

    $this->assertEquals(['content' => ['entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4']], $this->entityHandler->calculateDependencies());
  }

  /**
   * Tests calculate dependencies with entity id.
   */
  public function testCalculateDependenciesWithEntityId(): void {
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);
    $this->setupEntityTypeManager();

    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getConfigDependencyName')
      ->willReturn('entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4');
    $this->entityRepository
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
    $this->entityHandler->init($this->createStub(ViewExecutable::class), $this->display, $options);

    $this->assertEquals(['content' => ['entity_test:test-bundle:1d52762e-b9d8-4177-908f-572d1a5845a4']], $this->entityHandler->calculateDependencies());
  }

}
