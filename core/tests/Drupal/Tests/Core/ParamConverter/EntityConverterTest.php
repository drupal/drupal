<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\ParamConverter\EntityConverterTest.
 */

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\ParamConverter\EntityConverter
 * @group ParamConverter
 * @group Entity
 */
class EntityConverterTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The tested entity converter.
   *
   * @var \Drupal\Core\ParamConverter\EntityConverter
   */
  protected $entityConverter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $this->entityConverter = new EntityConverter($this->entityManager);
  }

  /**
   * Tests the applies() method.
   *
   * @dataProvider providerTestApplies
   *
   * @covers ::applies
   */
  public function testApplies(array $definition, $name, Route $route, $applies) {
    $this->entityManager->expects($this->any())
      ->method('hasDefinition')
      ->willReturnCallback(function($entity_type) {
        return 'entity_test' == $entity_type;
      });
    $this->assertEquals($applies, $this->entityConverter->applies($definition, $name, $route));
  }

  /**
   * Provides test data for testApplies()
   */
  public function providerTestApplies() {
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
  public function testConvert($value, array $definition, array $defaults, $expected_result) {
    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with('entity_test')
      ->willReturn($entity_storage);
    $entity_storage->expects($this->any())
      ->method('load')
      ->willReturnMap([
        ['valid_id', (object) ['id' => 'valid_id']],
        ['invalid_id', NULL],
      ]);

    $this->assertEquals($expected_result, $this->entityConverter->convert($value, $definition, 'foo', $defaults));
  }

  /**
   * Provides test data for testConvert
   */
  public function providerTestConvert() {
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
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testConvertWithInvalidEntityType() {
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with('invalid_id')
      ->willThrowException(new \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException('invalid_id'));

    $this->entityConverter->convert('id', ['type' => 'entity:invalid_id'], 'foo', ['foo' => 'id']);
  }

  /**
   * Tests the convert() method with an invalid dynamic entity type.
   *
   * @expectedException \Drupal\Core\ParamConverter\ParamNotConvertedException
   * @expectedExceptionMessage The "foo" parameter was not converted because the "invalid_id" parameter is missing
   */
  public function testConvertWithInvalidDynamicEntityType() {
    $this->entityConverter->convert('id', ['type' => 'entity:{invalid_id}'], 'foo', ['foo' => 'id']);
  }

}
