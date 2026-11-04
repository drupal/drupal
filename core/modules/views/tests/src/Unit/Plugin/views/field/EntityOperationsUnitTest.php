<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\user\Entity\Role;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\field\EntityOperations.
 */
#[CoversClass(EntityOperations::class)]
#[Group('Views')]
class EntityOperationsUnitTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $languageManager;

  /**
   * The plugin under test.
   *
   * @var \Drupal\views\Plugin\views\field\EntityOperations
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   *
   * @see ::__construct
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createStub(EntityTypeManagerInterface::class);
    $this->entityRepository = $this->createStub(EntityRepositoryInterface::class);
    $this->languageManager = $this->createStub(LanguageManagerInterface::class);

    $configuration = ['entity_type' => 'foo', 'entity field' => 'bar'];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [
      'title' => $this->randomMachineName(),
    ];
    $this->plugin = new EntityOperations($configuration, $plugin_id, $plugin_definition, $this->entityTypeManager, $this->languageManager, $this->entityRepository);

    $redirect_service = $this->createStub(RedirectDestinationInterface::class);
    $redirect_service
      ->method('getAsArray')
      ->willReturn(['destination' => 'foobar']);
    $this->plugin->setRedirectDestination($redirect_service);

    $view = $this->createStub(ViewExecutable::class);
    $display = $this->createStub(DisplayPluginBase::class);
    $view->display_handler = $display;
    $this->plugin->init($view, $display);
  }

  /**
   * Reinitializes the entity type manager as a mock object.
   */
  protected function setUpMockEntityTypeManager(): void {
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $reflection = new \ReflectionProperty($this->plugin, 'entityTypeManager');
    $reflection->setValue($this->plugin, $this->entityTypeManager);
  }

  /**
   * Tests uses group by.
   */
  public function testUsesGroupBy(): void {
    $this->assertFalse($this->plugin->usesGroupBy());
  }

  /**
   * Tests define options.
   */
  public function testDefineOptions(): void {
    $options = $this->plugin->defineOptions();
    $this->assertIsArray($options);
    $this->assertArrayHasKey('destination', $options);
  }

  /**
   * Tests render with destination.
   */
  public function testRenderWithDestination(): void {
    $this->setUpMockEntityTypeManager();
    $entity_type_id = $this->randomMachineName();
    $entity = $this->createStub(Role::class);
    $entity
      ->method('getEntityTypeId')
      ->willReturn($entity_type_id);

    $operations = [
      'foo' => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $list_builder = $this->createMock('\Drupal\Core\Entity\EntityListBuilderInterface');
    $list_builder->expects($this->once())
      ->method('getOperations')
      ->with($entity)
      ->willReturn($operations);

    $this->entityTypeManager->expects($this->once())
      ->method('getListBuilder')
      ->with($entity_type_id)
      ->willReturn($list_builder);

    $this->plugin->options['destination'] = TRUE;

    $result = new ResultRow();
    $result->_entity = $entity;

    $expected_build = [
      '#type' => 'operations',
      '#links' => $operations,
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];
    $expected_build['#links']['foo']['query'] = ['destination' => 'foobar'];
    $build = $this->plugin->render($result);
    $this->assertSame($expected_build, $build);
  }

  /**
   * Tests render without destination.
   */
  public function testRenderWithoutDestination(): void {
    $this->setUpMockEntityTypeManager();
    $entity_type_id = $this->randomMachineName();
    $entity = $this->createStub(Role::class);
    $entity
      ->method('getEntityTypeId')
      ->willReturn($entity_type_id);

    $operations = [
      'foo' => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $list_builder = $this->createMock('\Drupal\Core\Entity\EntityListBuilderInterface');
    $list_builder->expects($this->once())
      ->method('getOperations')
      ->with($entity)
      ->willReturn($operations);

    $this->entityTypeManager->expects($this->once())
      ->method('getListBuilder')
      ->with($entity_type_id)
      ->willReturn($list_builder);

    $this->plugin->options['destination'] = FALSE;

    $result = new ResultRow();
    $result->_entity = $entity;

    $expected_build = [
      '#type' => 'operations',
      '#links' => $operations,
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];
    $build = $this->plugin->render($result);
    $this->assertSame($expected_build, $build);
  }

  /**
   * Tests render without entity.
   */
  public function testRenderWithoutEntity(): void {
    $this->setUpMockLoggerWithMissingEntity();

    $entity = NULL;

    $result = new ResultRow();
    $result->_entity = $entity;

    $expected_build = '';
    $build = $this->plugin->render($result);
    $this->assertSame($expected_build, $build);
  }

}
