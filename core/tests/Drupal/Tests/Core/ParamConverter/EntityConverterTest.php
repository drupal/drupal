<?php

namespace Drupal\Tests\Core\ParamConverter;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
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
      ->willReturnCallback(function ($entity_type) {
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
   */
  public function testConvertWithInvalidEntityType() {
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->with('invalid_id')
      ->willThrowException(new InvalidPluginDefinitionException('invalid_id'));

    $this->setExpectedException(InvalidPluginDefinitionException::class);
    $this->entityConverter->convert('id', ['type' => 'entity:invalid_id'], 'foo', ['foo' => 'id']);
  }

  /**
   * Tests the convert() method with an invalid dynamic entity type.
   */
  public function testConvertWithInvalidDynamicEntityType() {
    $this->setExpectedException(ParamNotConvertedException::class, 'The "foo" parameter was not converted because the "invalid_id" parameter is missing');
    $this->entityConverter->convert('id', ['type' => 'entity:{invalid_id}'], 'foo', ['foo' => 'id']);
  }

  /**
   * Tests that omitting the language manager triggers a deprecation error.
   *
   * @group legacy
   *
   * @expectedDeprecation The language manager parameter has been added to EntityConverter since version 8.5.0 and will be made required in version 9.0.0 when requesting the latest translation-affected revision of an entity.
   */
  public function testDeprecatedOptionalLanguageManager() {
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

    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('entity_test')
      ->willReturn($storage);

    $entity_type = $this->createMock(ContentEntityTypeInterface::class);
    $entity_type->expects($this->any())
      ->method('isRevisionable')
      ->willReturn(TRUE);

    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with('entity_test')
      ->willReturn($entity_type);

    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->willReturn('en');

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_CONTENT)
      ->willReturn($language);

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->any())
      ->method('get')
      ->with('language_manager')
      ->willReturn($language_manager);

    \Drupal::setContainer($container);
    $definition = ['type' => 'entity:entity_test', 'load_latest_revision' => TRUE];
    $this->entityConverter->convert('id', $definition, 'foo', []);
  }

}
