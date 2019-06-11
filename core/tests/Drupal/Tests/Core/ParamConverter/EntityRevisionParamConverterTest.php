<?php

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\EntityRevisionParamConverter;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\ParamConverter\EntityRevisionParamConverter
 * @group entity
 */
class EntityRevisionParamConverterTest extends UnitTestCase {

  /**
   * The tested entity revision param converter.
   *
   * @var \Drupal\Core\ParamConverter\EntityRevisionParamConverter
   */
  protected $converter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->converter = new EntityRevisionParamConverter(
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
      $this->prophesize(EntityRepositoryInterface::class)->reveal()
    );
  }

  protected function getTestRoute() {
    $route = new Route('/test/{test_revision}');
    $route->setOption('parameters', [
      'test_revision' => [
        'type' => 'entity_revision:test',
      ],
    ]);
    return $route;
  }

  /**
   * @covers ::applies
   */
  public function testNonApplyingRoute() {
    $route = new Route('/test');
    $this->assertFalse($this->converter->applies([], 'test_revision', $route));
  }

  /**
   * @covers ::applies
   */
  public function testApplyingRoute() {
    $route = $this->getTestRoute();
    $this->assertTrue($this->converter->applies($route->getOption('parameters')['test_revision'], 'test_revision', $route));
  }

  /**
   * Tests the convert() method.
   *
   * @dataProvider providerTestConvert
   *
   * @covers ::convert
   */
  public function testConvert($value, array $definition, array $defaults, $expected_result) {
    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->loadRevision('valid_id')->willReturn((object) ['revision_id' => 'valid_id']);
    $storage->loadRevision('invalid_id')->willReturn(NULL);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('entity_test')->willReturn($storage->reveal());
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class);
    $converter = new EntityRevisionParamConverter($entity_type_manager->reveal(), $entity_repository->reveal());

    $result = $converter->convert($value, $definition, 'test_revision', $defaults);
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Provides test data for testConvert
   */
  public function providerTestConvert() {
    $data = [];
    // Existing entity type.
    $data[] = ['valid_id', ['type' => 'entity_revision:entity_test'], ['test_revision' => 'valid_id'], (object) ['revision_id' => 'valid_id']];
    // Invalid ID.
    $data[] = ['invalid_id', ['type' => 'entity_revision:entity_test'], ['test_revision' => 'invalid_id'], NULL];
    // Entity type placeholder.
    $data[] = ['valid_id', ['type' => 'entity_revision:{entity_type}'], ['test_revision' => 'valid_id', 'entity_type' => 'entity_test'], (object) ['revision_id' => 'valid_id']];

    return $data;
  }

  /**
   * Tests the convert() method with an invalid entity type ID.
   *
   * @covers ::convert
   */
  public function testConvertWithInvalidEntityType() {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('invalid_entity_type_id')->willThrow(new InvalidPluginDefinitionException('invalid_entity_type_id'));
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class);
    $converter = new EntityRevisionParamConverter($entity_type_manager->reveal(), $entity_repository->reveal());

    $this->expectException(InvalidPluginDefinitionException::class);
    $converter->convert('valid_id', ['type' => 'entity_revision:invalid_entity_type_id'], 'foo', ['foo' => 'valid_id']);
  }

  /**
   * Tests the convert() method with an invalid dynamic entity type ID.
   *
   * @covers ::convert
   */
  public function testConvertWithInvalidType() {
    $this->expectException(ParamNotConvertedException::class);
    $this->expectExceptionMessage('The type definition "entity_revision_{entity_type_id}" is invalid. The expected format is "entity_revision:<entity_type_id>".');
    $this->converter->convert('valid_id', ['type' => 'entity_revision_{entity_type_id}'], 'foo', ['foo' => 'valid_id']);
  }

  /**
   * Tests the convert() method with an invalid dynamic entity type ID.
   *
   * @covers ::convert
   */
  public function testConvertWithInvalidDynamicEntityType() {
    $this->expectException(ParamNotConvertedException::class);
    $this->expectExceptionMessage('The "foo" parameter was not converted because the "invalid_entity_type_id" parameter is missing.');
    $this->converter->convert('valid_id', ['type' => 'entity_revision:{invalid_entity_type_id}'], 'foo', ['foo' => 'valid_id']);
  }

}
