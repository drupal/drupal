<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Cache\MemoryCache\MemoryCache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Drupal\entity_test_deprecated_storage\Storage\DeprecatedEntityStorage;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityStorageBase
 * @group Entity
 * @group legacy
 *
 * @todo Remove this in Drupal 10.
 * @see https://www.drupal.org/project/drupal/issues/3244802
 */
class EntityStorageDeprecationTest extends UnitTestCase {

  /**
   * The content entity database storage used in this test.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity type used in this test.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * An array of field definitions used for this test, keyed by field name.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]|\PHPUnit\Framework\MockObject\MockObject[]
   */
  protected $fieldDefinitions = [];

  /**
   * The mocked entity type manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked entity type bundle info used in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeBundleInfo;

  /**
   * The mocked entity field manager used in this test.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityType = $this->createMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('id')
      ->will($this->returnValue($this->entityTypeId));
    $this->entityType->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue('bogus_class'));

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    $this->entityTypeManager = $this->createMock(EntityTypeManager::class);
    $this->entityTypeBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManager::class);
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->will($this->returnValue(new Language(['langcode' => 'en'])));
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->container->set('entity_type.manager', $this->entityTypeManager);
    $this->container->set('entity_field.manager', $this->entityFieldManager);
  }

  /**
   * Sets up the content entity storage.
   */
  protected function setUpEntityStorage() {
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($this->entityType));

    $this->entityTypeManager->expects($this->any())
      ->method('getActiveDefinition')
      ->will($this->returnValue($this->entityType));

    $this->entityFieldManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityFieldManager->expects($this->any())
      ->method('getActiveFieldStorageDefinitions')
      ->will($this->returnValue($this->fieldDefinitions));

    $this->entityStorage = new DeprecatedEntityStorage($this->entityType, $this->connection, $this->entityFieldManager, $this->cache, $this->languageManager, new MemoryCache(), $this->entityTypeBundleInfo, $this->entityTypeManager);
    $this->entityStorage->setModuleHandler($this->moduleHandler);
  }

  /**
   * Tests the deprecation when accessing entityClass directly.
   *
   * @group legacy
   */
  public function testGetEntityClass(): void {
    $this->setUpEntityStorage();
    $this->expectDeprecation('Accessing the entityClass property directly is deprecated in drupal:9.3.0. Use ::getEntityClass() instead. See https://www.drupal.org/node/3191609');
    $entity_class = $this->entityStorage->getCurrentEntityClass();
    $this->assertEquals('bogus_class', $entity_class);
  }

  /**
   * Tests the deprecation when setting entityClass directly.
   *
   * @group legacy
   */
  public function testSetEntityClass(): void {
    $this->setUpEntityStorage();
    $this->expectDeprecation('Setting the entityClass property directly is deprecated in drupal:9.3.0 and has no effect in drupal:10.0.0. See https://www.drupal.org/node/3191609');
    $this->entityStorage->setEntityClass('entity_class');
    $this->assertEquals('entity_class', $this->entityStorage->getEntityClass());
  }

}
