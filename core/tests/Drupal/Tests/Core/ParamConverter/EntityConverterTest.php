<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\ParamConverter\EntityConverter
 * @group ParamConverter
 * @group Entity
 */
class EntityConverterTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked entities repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * The tested entity converter.
   *
   * @var \Drupal\Core\ParamConverter\EntityConverter
   */
  protected $entityConverter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->entityConverter = new EntityConverter($this->entityTypeManager, $this->entityRepository);
  }

  /**
   * Sets up mock services and class instances.
   *
   * @param object[] $service_map
   *   An associative array of service instances keyed by service name.
   */
  protected function setUpMocks($service_map = []): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('entity_test');
    $entity->expects($this->any())
      ->method('id')
      ->willReturn('id');
    $entity->expects($this->any())
      ->method('isTranslatable')
      ->willReturn(FALSE);
    $entity->expects($this->any())
      ->method('getLoadedRevisionId')
      ->willReturn('revision_id');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('load')
      ->with('id')
      ->willReturn($entity);
    $storage->expects($this->any())
      ->method('getLatestRevisionId')
      ->with('id')
      ->willReturn('revision_id');

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('entity_test')
      ->willReturn($storage);

    $entity_type = $this->createMock(ContentEntityTypeInterface::class);
    $entity_type->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);

    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with('entity_test')
      ->willReturn($entity_type);

    $context_definition = $this->createMock(DataDefinition::class);
    foreach (['setLabel', 'setDescription', 'setRequired', 'setConstraints'] as $method) {
      $context_definition->expects($this->any())
        ->method($method)
        ->willReturn($context_definition);
    }
    $context_definition->expects($this->any())
      ->method('getConstraints')
      ->willReturn([]);

    $typed_data_manager = $this->createMock(TypedDataManagerInterface::class);
    $typed_data_manager->expects($this->any())
      ->method('create')
      ->willReturn($this->createMock(TypedDataInterface::class));
    $typed_data_manager->expects($this->any())
      ->method('createDataDefinition')
      ->willReturn($context_definition);

    $service_map += [
      'typed_data_manager' => $typed_data_manager,
    ];

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit\Framework\MockObject\MockObject $container */
    $container = $this->createMock(ContainerInterface::class);
    $return_map = [];
    foreach ($service_map as $name => $service) {
      $return_map[] = [$name, 1, $service];
    }
    $container
      ->expects($this->any())
      ->method('get')
      ->willReturnMap($return_map);

    \Drupal::setContainer($container);
  }

  /**
   * Tests the applies() method.
   *
   * @dataProvider providerTestApplies
   *
   * @covers ::applies
   */
  public function testApplies(array $definition, $name, Route $route, $applies): void {
    $this->entityTypeManager->expects($this->any())
      ->method('hasDefinition')
      ->willReturnCallback(function ($entity_type) {
        return 'entity_test' == $entity_type;
      });
    $this->assertEquals($applies, $this->entityConverter->applies($definition, $name, $route));
  }

  /**
   * Provides test data for testApplies()
   */
  public static function providerTestApplies() {
    $data = [];
    $data[] = [['type' => 'entity:foo'], 'foo', new Route('/test/{foo}/bar'), FALSE];
    $data[] = [['type' => 'entity:entity_test'], 'foo', new Route('/test/{foo}/bar'), TRUE];
    $data[] = [['type' => 'entity:entity_test'], 'entity_test', new Route('/test/{entity_test}/bar'), TRUE];
    $data[] = [['type' => 'entity:{entity_test}'], 'entity_test', new Route('/test/{entity_test}/bar'), FALSE];
    $data[] = [['type' => 'entity:{entity_type}'], 'entity_test', new Route('/test/{entity_type}/{entity_test}/bar'), TRUE];
    $data[] = [['type' => 'foo'], 'entity_test', new Route('/test/{entity_type}/{entity_test}/bar'), FALSE];

    return $data;
  }

  /**
   * Tests the convert() method.
   *
   * @dataProvider providerTestConvert
   *
   * @covers ::convert
   */
  public function testConvert($value, array $definition, array $defaults, $expected_result): void {
    $this->setUpMocks();

    $this->entityRepository->expects($this->any())
      ->method('getCanonical')
      ->willReturnCallback(function ($entity_type_id, $entity_id) {
        return $entity_type_id === 'entity_test' && $entity_id === 'valid_id' ? (object) ['id' => 'valid_id'] : NULL;
      });

    $this->assertEquals($expected_result, $this->entityConverter->convert($value, $definition, 'foo', $defaults));
  }

  /**
   * Provides test data for testConvert.
   */
  public static function providerTestConvert() {
    $data = [];
    // Existing entity type.
    $data[] = ['valid_id', ['type' => 'entity:entity_test'], ['foo' => 'valid_id'], (object) ['id' => 'valid_id']];
    // Invalid ID.
    $data[] = ['invalid_id', ['type' => 'entity:entity_test'], ['foo' => 'invalid_id'], NULL];
    // Entity type placeholder.
    $data[] = ['valid_id', ['type' => 'entity:{entity_type}'], ['foo' => 'valid_id', 'entity_type' => 'entity_test'], (object) ['id' => 'valid_id']];

    return $data;
  }

  /**
   * Tests the convert() method with an invalid entity type.
   */
  public function testConvertWithInvalidEntityType(): void {
    $this->setUpMocks();

    $plugin_id = 'invalid_id';
    $contexts = ['operation' => 'entity_upcast'];
    $this->entityRepository->expects($this->once())
      ->method('getCanonical')
      ->with($plugin_id, 'id', $contexts)
      ->willThrowException(new PluginNotFoundException($plugin_id));

    $this->expectException(PluginNotFoundException::class);

    $this->entityConverter->convert('id', ['type' => 'entity:' . $plugin_id], 'foo', ['foo' => 'id']);
  }

  /**
   * Tests the convert() method with an invalid dynamic entity type.
   */
  public function testConvertWithInvalidDynamicEntityType(): void {
    $this->expectException(ParamNotConvertedException::class);
    $this->expectExceptionMessage('The "foo" parameter was not converted because the "invalid_id" parameter is missing.');
    $this->entityConverter->convert('id', ['type' => 'entity:{invalid_id}'], 'foo', ['foo' => 'id']);
  }

}
