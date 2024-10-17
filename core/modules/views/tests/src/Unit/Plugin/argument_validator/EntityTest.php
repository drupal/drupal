<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\argument_validator;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\Core\Entity\StubEntityBase;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\argument_validator\Entity
 * @group views
 */
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
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mocked entity type bundle info used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
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

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);

    $mock_entity = $this->getMockBuilder(StubEntityBase::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['bundle', 'access'])
      ->getMock();
    $mock_entity->expects($this->any())
      ->method('bundle')
      ->willReturn('test_bundle');
    $mock_entity->expects($this->any())
      ->method('access')
      ->willReturnMap([
        ['test_op', NULL, FALSE, TRUE],
        ['test_op_2', NULL, FALSE, FALSE],
        ['test_op_3', NULL, FALSE, TRUE],
      ]);

    $mock_entity_bundle_2 = $this->getMockBuilder(StubEntityBase::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['bundle', 'access'])
      ->getMock();
    $mock_entity_bundle_2->expects($this->any())
      ->method('bundle')
      ->willReturn('test_bundle_2');
    $mock_entity_bundle_2->expects($this->any())
      ->method('access')
      ->willReturnMap([
        ['test_op', NULL, FALSE, FALSE],
        ['test_op_2', NULL, FALSE, FALSE],
        ['test_op_3', NULL, FALSE, TRUE],
      ]);

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');

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
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturnMap($value_map);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('entity_test')
      ->willReturn($storage);

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

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
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies(): void {
    // Create an entity type manager, storage, entity type, and entity to mock the
    // loading of entities providing bundles.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_type = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
    $mock_entity = $this->createMock('Drupal\Core\Entity\EntityInterface');

    $mock_entity->expects($this->any())
      ->method('getConfigDependencyKey')
      ->willReturn('config');
    $mock_entity->expects($this->any())
      ->method('getConfigDependencyName')
      ->willReturn('test_bundle');
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->with(['test_bundle'])
      ->willReturn(['test_bundle' => $mock_entity]);

    $entity_type->expects($this->any())
      ->method('getBundleEntityType')
      ->willReturn('entity_test_bundle');
    $entity_type_manager->expects($this->any())
      ->method('getDefinition')
      ->with('entity_test')
      ->willReturn($entity_type);
    $entity_type_manager->expects($this->any())
      ->method('hasHandler')
      ->with('entity_test_bundle', 'storage')
      ->willReturn(TRUE);
    $entity_type_manager->expects($this->any())
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
