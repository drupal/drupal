<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\argument_validator;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\Core\Entity\StubEntityBase;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument_validator\Entity;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\argument_validator\Entity.
 */
#[CoversClass(Entity::class)]
#[Group('views')]
class EntityTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $executable;

  /**
   * The view display.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  protected $display;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityTypeManager;

  /**
   * The mocked entity type bundle info used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityTypeBundleInfo;

  /**
   * The tested argument validator.
   *
   * @var \Drupal\views\Plugin\views\argument_validator\Entity
   */
  protected $argumentValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createStub(EntityTypeManagerInterface::class);
    $this->entityTypeBundleInfo = $this->createStub(EntityTypeBundleInfoInterface::class);

    $mock_entity = $this->createStub(StubEntityBase::class);
    $mock_entity
      ->method('bundle')
      ->willReturn('test_bundle');
    $mock_entity
      ->method('access')
      ->willReturnMap([
        ['test_op', NULL, TRUE, AccessResult::allowed()],
        ['test_op_2', NULL, TRUE, AccessResult::neutral()],
        ['test_op_3', NULL, TRUE, AccessResult::allowed()],
      ]);

    $mock_entity_bundle_2 = $this->createStub(StubEntityBase::class);
    $mock_entity_bundle_2
      ->method('bundle')
      ->willReturn('test_bundle_2');
    $mock_entity_bundle_2
      ->method('access')
      ->willReturnMap([
        ['test_op', NULL, TRUE, AccessResult::neutral()],
        ['test_op_2', NULL, TRUE, AccessResult::neutral()],
        ['test_op_3', NULL, TRUE, AccessResult::allowed()],
      ]);

    foreach ([$mock_entity, $mock_entity_bundle_2] as $entity) {
      $entity->method('getCacheContexts')->willReturn([]);
      $entity->method('getCacheTags')->willReturn([]);
      $entity->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);
    }

    $entity_type = $this->createStub(EntityTypeInterface::class);
    $entity_type
      ->method('getListCacheTags')
      ->willReturn(['entity_test_list']);

    $storage = $this->createStub(EntityStorageInterface::class);
    $storage
      ->method('getEntityType')
      ->willReturn($entity_type);

    // Setup values for IDs passed as strings or numbers.
    $value_map = [
      [[], []],
      [[1], [1 => $mock_entity]],
      [['1'], [1 => $mock_entity]],
      [[1, 2], [1 => $mock_entity, 2 => $mock_entity_bundle_2]],
      [['1', '2'], [1 => $mock_entity, 2 => $mock_entity_bundle_2]],
      [[2], [2 => $mock_entity_bundle_2]],
      [['2'], [2 => $mock_entity_bundle_2]],
    ];
    $storage
      ->method('loadMultiple')
      ->willReturnMap($value_map);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturn($storage);

    $this->executable = $this->createStub(ViewExecutable::class);
    $this->display = $this->createStub(DisplayPluginBase::class);

    $definition = [
      'entity_type' => 'entity_test',
    ];

    $this->argumentValidator = new Entity([], 'entity_test', $definition, $this->entityTypeManager, $this->entityTypeBundleInfo);
  }

  /**
   * Tests the validate argument method with no access and bundles.
   *
   * @see \Drupal\views\Plugin\views\argument_validator\Entity::validateArgument()
   */
  public function testValidateArgumentNoAccess(): void {
    $options = [];
    $options['access'] = FALSE;
    $options['bundles'] = [];
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertFalse($this->argumentValidator->validateArgument(3));
    $this->assertFalse($this->argumentValidator->validateArgument(''));
    $this->assertFalse($this->argumentValidator->validateArgument(NULL));

    $this->assertTrue($this->argumentValidator->validateArgument(1));
    $this->assertTrue($this->argumentValidator->validateArgument(2));
    $this->assertFalse($this->argumentValidator->validateArgument('1,2'));
  }

  /**
   * Tests the validate argument method with access and no bundles.
   *
   * @see \Drupal\views\Plugin\views\argument_validator\Entity::validateArgument()
   */
  public function testValidateArgumentAccess(): void {
    $options = [];
    $options['access'] = TRUE;
    $options['bundles'] = [];
    $options['operation'] = 'test_op';
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertFalse($this->argumentValidator->validateArgument(3));
    $this->assertFalse($this->argumentValidator->validateArgument(''));

    $this->assertTrue($this->argumentValidator->validateArgument(1));

    $options = [];
    $options['access'] = TRUE;
    $options['bundles'] = [];
    $options['operation'] = 'test_op_2';
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertFalse($this->argumentValidator->validateArgument(3));
    $this->assertFalse($this->argumentValidator->validateArgument(''));

    $this->assertFalse($this->argumentValidator->validateArgument(1));
    $this->assertFalse($this->argumentValidator->validateArgument(2));
  }

  /**
   * Tests the validate argument method with bundle checking.
   */
  public function testValidateArgumentBundle(): void {
    $options = [];
    $options['access'] = FALSE;
    $options['bundles'] = ['test_bundle' => 1];
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertTrue($this->argumentValidator->validateArgument(1));
    $this->assertFalse($this->argumentValidator->validateArgument(2));

    $options['bundles'] = NULL;
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertTrue($this->argumentValidator->validateArgument(1));
    $this->assertTrue($this->argumentValidator->validateArgument(2));
  }

  /**
   * Tests calculate dependencies.
   */
  public function testCalculateDependencies(): void {
    // Create an entity type manager, storage, entity type, and entity to mock
    // the loading of entities providing bundles.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $mock_entity = $this->createMock('Drupal\Core\Entity\EntityInterface');

    $mock_entity->expects($this->once())
      ->method('getConfigDependencyKey')
      ->willReturn('config');
    $mock_entity->expects($this->once())
      ->method('getConfigDependencyName')
      ->willReturn('test_bundle');
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test_bundle'])
      ->willReturn(['test_bundle' => $mock_entity]);

    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->willReturn('entity_test_bundle');
    $entity_type_manager->expects($this->once())
      ->method('getDefinition')
      ->with('entity_test')
      ->willReturn($entity_type);
    $entity_type_manager->expects($this->once())
      ->method('hasHandler')
      ->with('entity_test_bundle', 'storage')
      ->willReturn(TRUE);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('entity_test_bundle')
      ->willReturn($storage);

    // Set up the argument validator.
    $argumentValidator = new Entity([], 'entity_test', ['entity_type' => 'entity_test'], $entity_type_manager, $this->entityTypeBundleInfo);
    $options = [];
    $options['access'] = FALSE;
    $options['bundles'] = ['test_bundle' => 1];
    $argumentValidator->init($this->executable, $this->display, $options);

    $this->assertEquals(['config' => ['test_bundle']], $argumentValidator->calculateDependencies());
  }

  /**
   * Tests the validate argument method with multiple argument splitting.
   */
  public function testValidateArgumentMultiple(): void {
    $options = [];
    $options['access'] = TRUE;
    $options['bundles'] = [];
    $options['operation'] = 'test_op';
    $options['multiple'] = TRUE;
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertTrue($this->argumentValidator->validateArgument('1'));
    $this->assertFalse($this->argumentValidator->validateArgument('2'));

    $this->assertFalse($this->argumentValidator->validateArgument('1,2'));
    $this->assertFalse($this->argumentValidator->validateArgument('1+2'));

    $this->assertFalse($this->argumentValidator->validateArgument(NULL));

    $options = [];
    $options['access'] = TRUE;
    $options['bundles'] = [];
    $options['operation'] = 'test_op_3';
    $options['multiple'] = TRUE;
    $this->argumentValidator->init($this->executable, $this->display, $options);

    $this->assertTrue($this->argumentValidator->validateArgument('1,2'));
    $this->assertTrue($this->argumentValidator->validateArgument('1+2'));
  }

}
