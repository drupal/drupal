<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityManagerTest.
 */

namespace Drupal\Tests\Core\Entity {

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityManager
 * @group Entity
 */
class EntityManagerTest extends UnitTestCase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Tests\Core\Entity\TestEntityManager
   */
  protected $entityManager;

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * An instance of the test entity.
   *
   * @var \Drupal\Tests\Core\Entity\EntityManagerTestEntity|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $discovery;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheBackend;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The string translationManager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedDataManager;

  /**
   * The keyvalue collection for tracking installed definitions.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $installedDefinitions;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->with('entity_type_build')
      ->will($this->returnValue(array()));

    $this->cacheBackend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->cacheTagsInvalidator = $this->getMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $language = $this->getMock('Drupal\Core\Language\LanguageInterface');
    $language->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));
    $this->languageManager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue(array('en' => (object) array('id' => 'en'))));

    $this->translationManager = $this->getStringTranslationStub();

    $this->formBuilder = $this->getMock('Drupal\Core\Form\FormBuilderInterface');
    $this->controllerResolver = $this->getClassResolverStub();

    $this->discovery = $this->getMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');

    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

    $map = [
      ['field_item:boolean', TRUE, ['class' => 'Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem']],
    ];

    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap($map);

    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $this->installedDefinitions = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreInterface');

    $this->container = $this->getContainerWithCacheTagsInvalidator($this->cacheTagsInvalidator);
    $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    \Drupal::setContainer($this->container);

    $field_type_manager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');
    $field_type_manager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->willReturn(array());
    $field_type_manager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->willReturn(array());

    $string_translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');

    $map = [
      ['cache_tags.invalidator', 1, $this->cacheTagsInvalidator],
      ['plugin.manager.field.field_type', 1, $field_type_manager],
      ['string_translation', 1, $string_translation],
      ['typed_data_manager', 1, $this->typedDataManager],
    ];

    $this->container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);
  }

  /**
   * Sets up the entity manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\PHPUnit_Framework_MockObject_MockObject[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityManager($definitions = array()) {
    $class = $this->getMockClass('Drupal\Core\Entity\EntityInterface');
    foreach ($definitions as $entity_type) {
      $entity_type->expects($this->any())
        ->method('getClass')
        ->will($this->returnValue($class));
    }
    $this->discovery->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnCallback(function ($entity_type_id, $exception_on_invalid = FALSE) use ($definitions) {
        if (isset($definitions[$entity_type_id])) {
          return $definitions[$entity_type_id];
        }
        elseif (!$exception_on_invalid) {
          return NULL;
        }
        else throw new PluginNotFoundException($entity_type_id);
      }));
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->entityManager = new TestEntityManager(new \ArrayObject(), $this->moduleHandler, $this->cacheBackend, $this->languageManager, $this->translationManager, $this->getClassResolverStub(), $this->typedDataManager, $this->installedDefinitions, $this->eventDispatcher);
    $this->entityManager->setContainer($this->container);
    $this->entityManager->setDiscovery($this->discovery);
  }

  /**
   * Tests the clearCachedDefinitions() method.
   *
   * @covers ::clearCachedDefinitions
   *
   */
  public function testClearCachedDefinitions() {
    $this->setUpEntityManager();
    $this->cacheTagsInvalidator->expects($this->at(0))
      ->method('invalidateTags')
      ->with(array('entity_types'));
    $this->cacheTagsInvalidator->expects($this->at(1))
      ->method('invalidateTags')
      ->with(array('entity_bundles'));
    $this->cacheTagsInvalidator->expects($this->at(2))
      ->method('invalidateTags')
      ->with(array('entity_field_info'));

    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Tests the getDefinition() method.
   *
   * @covers ::getDefinition
   *
   * @dataProvider providerTestGetDefinition
   */
  public function testGetDefinition($entity_type_id, $expected) {
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $this->setUpEntityManager(array(
      'apple' => $entity,
      'banana' => $entity,
    ));

    $entity_type = $this->entityManager->getDefinition($entity_type_id, FALSE);
    if ($expected) {
      $this->assertInstanceOf('Drupal\Core\Entity\EntityTypeInterface', $entity_type);
    }
    else {
      $this->assertNull($entity_type);
    }
  }

  /**
   * Provides test data for testGetDefinition().
   *
   * @return array
   *   Test data.
   */
  public function providerTestGetDefinition() {
    return array(
      array('apple', TRUE),
      array('banana', TRUE),
      array('pear', FALSE),
    );
  }

  /**
   * Tests the getDefinition() method with an invalid definition.
   *
   * @covers ::getDefinition
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage The "pear" entity type does not exist.
   */
  public function testGetDefinitionInvalidException() {
    $this->setUpEntityManager();

    $this->entityManager->getDefinition('pear', TRUE);
  }

  /**
   * Tests the hasHandler() method.
   *
   * @covers ::hasHandler
   *
   * @dataProvider providerTestHasHandler
   */
  public function testHasHandler($entity_type_id, $expected) {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->any())
      ->method('hasHandlerClass')
      ->with('storage')
      ->will($this->returnValue(TRUE));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->any())
      ->method('hasHandlerClass')
      ->with('storage')
      ->will($this->returnValue(FALSE));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $entity_type = $this->entityManager->hasHandler($entity_type_id, 'storage');
    $this->assertSame($expected, $entity_type);
  }

  /**
   * Provides test data for testHasHandler().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHasHandler() {
    return array(
      array('apple', TRUE),
      array('banana', FALSE),
      array('pear', FALSE),
    );
  }

  /**
   * Tests the getStorage() method.
   *
   * @covers ::getStorage
   */
  public function testGetStorage() {
    $class = $this->getTestHandlerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getHandlerClass')
      ->with('storage')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getStorage('test_entity_type'));
  }

  /**
   * Tests the getListBuilder() method.
   *
   * @covers ::getListBuilder
   */
  public function testGetListBuilder() {
    $class = $this->getTestHandlerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getHandlerClass')
      ->with('list_builder')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getListBuilder('test_entity_type'));
  }

  /**
   * Tests the getViewBuilder() method.
   *
   * @covers ::getViewBuilder
   */
  public function testGetViewBuilder() {
    $class = $this->getTestHandlerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getHandlerClass')
      ->with('view_builder')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getViewBuilder('test_entity_type'));
  }

  /**
   * Tests the getAccessControlHandler() method.
   *
   * @covers ::getAccessControlHandler
   */
  public function testGetAccessControlHandler() {
    $class = $this->getTestHandlerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getHandlerClass')
      ->with('access')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getAccessControlHandler('test_entity_type'));
  }

  /**
   * Tests the getFormObject() method.
   *
   * @covers ::getFormObject
   */
  public function testGetFormObject() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getFormClass')
      ->with('default')
      ->will($this->returnValue('Drupal\Tests\Core\Entity\TestEntityForm'));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getFormClass')
      ->with('default')
      ->will($this->returnValue('Drupal\Tests\Core\Entity\TestEntityFormInjected'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $apple_form = $this->entityManager->getFormObject('apple', 'default');
    $this->assertInstanceOf('Drupal\Tests\Core\Entity\TestEntityForm', $apple_form);
    $this->assertAttributeInstanceOf('Drupal\Core\Extension\ModuleHandlerInterface', 'moduleHandler', $apple_form);
    $this->assertAttributeInstanceOf('Drupal\Core\StringTranslation\TranslationInterface', 'stringTranslation', $apple_form);

    $banana_form = $this->entityManager->getFormObject('banana', 'default');
    $this->assertInstanceOf('Drupal\Tests\Core\Entity\TestEntityFormInjected', $banana_form);
    $this->assertAttributeEquals('yellow', 'color', $banana_form);

  }

  /**
   * Tests the getFormObject() method with an invalid operation.
   *
   * @covers ::getFormObject
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetFormObjectInvalidOperation() {
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getFormClass')
      ->with('edit')
      ->will($this->returnValue(''));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->entityManager->getFormObject('test_entity_type', 'edit');
  }

  /**
   * Tests the getHandler() method.
   *
   * @covers ::getHandler
   */
  public function testGetHandler() {
    $class = $this->getTestHandlerClass();
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getHandlerClass')
      ->with('storage')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array(
      'apple' => $apple,
    ));

    $apple_controller = $this->entityManager->getHandler('apple', 'storage');
    $this->assertInstanceOf($class, $apple_controller);
    $this->assertAttributeInstanceOf('Drupal\Core\Extension\ModuleHandlerInterface', 'moduleHandler', $apple_controller);
    $this->assertAttributeInstanceOf('Drupal\Core\StringTranslation\TranslationInterface', 'stringTranslation', $apple_controller);
  }

  /**
   * Tests the getHandler() method when no controller is defined.
   *
   * @covers ::getHandler
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetHandlerMissingHandler() {
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getHandlerClass')
      ->with('storage')
      ->will($this->returnValue(''));
    $this->setUpEntityManager(array('test_entity_type' => $entity));
    $this->entityManager->getHandler('test_entity_type', 'storage');
  }

  /**
   * Tests the getBaseFieldDefinitions() method.
   *
   * @covers ::getBaseFieldDefinitions
   * @covers ::buildBaseFieldDefinitions
   */
  public function testGetBaseFieldDefinitions() {
    $field_definition = $this->setUpEntityWithFieldDefinition();

    $expected = array('id' => $field_definition);
    $this->assertSame($expected, $this->entityManager->getBaseFieldDefinitions('test_entity_type'));
  }

  /**
   * Tests the getFieldDefinitions() method.
   *
   * @covers ::getFieldDefinitions
   * @covers ::buildBundleFieldDefinitions
   */
  public function testGetFieldDefinitions() {
    $field_definition = $this->setUpEntityWithFieldDefinition();

    $expected = array('id' => $field_definition);
    $this->assertSame($expected, $this->entityManager->getFieldDefinitions('test_entity_type', 'test_entity_bundle'));
  }

  /**
   * Tests the getFieldStorageDefinitions() method.
   *
   * @covers ::getFieldStorageDefinitions
   * @covers ::buildFieldStorageDefinitions
   */
  public function testGetFieldStorageDefinitions() {
    $field_definition = $this->setUpEntityWithFieldDefinition(TRUE);
    $field_storage_definition = $this->getMock('\Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field_storage_definition->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('field_storage'));

    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValueMap(array(
        array('entity_type_build', array()),
        array('entity_base_field_info', array()),
        array('entity_field_storage_info', array('example_module')),
      )));

    $this->moduleHandler->expects($this->any())
      ->method('invoke')
      ->with('example_module', 'entity_field_storage_info')
      ->will($this->returnValue(array('field_storage' => $field_storage_definition)));

    $expected = array(
      'id' => $field_definition,
      'field_storage' => $field_storage_definition,
    );
    $this->assertSame($expected, $this->entityManager->getFieldStorageDefinitions('test_entity_type'));
  }

  /**
   * Tests the getBaseFieldDefinitions() method with a translatable entity type.
   *
   * @covers ::getBaseFieldDefinitions
   * @covers ::buildBaseFieldDefinitions
   *
   * @dataProvider providerTestGetBaseFieldDefinitionsTranslatableEntityTypeDefaultLangcode
   */
  public function testGetBaseFieldDefinitionsTranslatableEntityTypeDefaultLangcode($default_langcode_key) {
    $this->setUpEntityWithFieldDefinition(FALSE, 'id', array('langcode' => 'langcode', 'default_langcode' => $default_langcode_key));

    $field_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $field_definition->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->willReturn(TRUE);

    $entity_class = get_class($this->entity);
    $entity_class::$baseFieldDefinitions += array('langcode' => $field_definition);

    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->willReturn(TRUE);

    $definitions = $this->entityManager->getBaseFieldDefinitions('test_entity_type');

    $this->assertTrue(isset($definitions[$default_langcode_key]));
  }

  /**
   * Provides test data for testGetBaseFieldDefinitionsTranslatableEntityTypeDefaultLangcode().
   *
   * @return array
   *   Test data.
   */
  public function providerTestGetBaseFieldDefinitionsTranslatableEntityTypeDefaultLangcode() {
    return [
      ['default_langcode'],
      ['custom_default_langcode_key'],
    ];
  }

  /**
   * Tests the getBaseFieldDefinitions() method with a translatable entity type.
   *
   * @covers ::getBaseFieldDefinitions
   * @covers ::buildBaseFieldDefinitions
   *
   * @expectedException \LogicException
   * @expectedExceptionMessage The Test entity type cannot be translatable as it does not define a translatable "langcode" field.
   *
   * @dataProvider providerTestGetBaseFieldDefinitionsTranslatableEntityTypeLangcode
   */
  public function testGetBaseFieldDefinitionsTranslatableEntityTypeLangcode($provide_key, $provide_field, $translatable) {
    $keys = $provide_key ? array('langcode' => 'langcode') : array();
    $this->setUpEntityWithFieldDefinition(FALSE, 'id', $keys);

    if ($provide_field) {
      $field_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
        ->disableOriginalConstructor()
        ->getMock();
      $field_definition->expects($this->any())
        ->method('isTranslatable')
        ->willReturn($translatable);

      $entity_class = get_class($this->entity);
      $entity_class::$baseFieldDefinitions += array('langcode' => $field_definition);
    }

    $this->entityType->expects($this->atLeastOnce())
      ->method('isTranslatable')
      ->willReturn(TRUE);
    $this->entityType->expects($this->atLeastOnce())
      ->method('getLabel')
      ->willReturn('Test');

    $this->entityManager->getBaseFieldDefinitions('test_entity_type');
  }

  /**
   * Provides test data for testGetBaseFieldDefinitionsTranslatableEntityTypeLangcode().
   *
   * @return array
   *   Test data.
   */
  public function providerTestGetBaseFieldDefinitionsTranslatableEntityTypeLangcode() {
    return [
      [FALSE, TRUE, TRUE],
      [TRUE, FALSE, TRUE],
      [TRUE, TRUE, FALSE],
    ];
  }

  /**
   * Tests the getBaseFieldDefinitions() method with caching.
   *
   * @covers ::getBaseFieldDefinitions
   */
  public function testGetBaseFieldDefinitionsWithCaching() {
    $field_definition = $this->setUpEntityWithFieldDefinition();

    $expected = array('id' => $field_definition);

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('entity_type', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(2))
      ->method('set')
      ->with('entity_type');
    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with('entity_base_field_definitions:test_entity_type:en');
    $this->cacheBackend->expects($this->at(4))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));

    $this->assertSame($expected, $this->entityManager->getBaseFieldDefinitions('test_entity_type'));
    $this->entityManager->testClearEntityFieldInfo();
    $this->assertSame($expected, $this->entityManager->getBaseFieldDefinitions('test_entity_type'));
  }

  /**
   * Tests the getFieldDefinitions() method with caching.
   *
   * @covers ::getFieldDefinitions
   */
  public function testGetFieldDefinitionsWithCaching() {
    $field_definition = $this->setUpEntityWithFieldDefinition(FALSE, 'id');

    $expected = array('id' => $field_definition);

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('entity_bundle_field_definitions:test_entity_type:test_bundle:en', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(2))
      ->method('get')
      ->with('entity_type', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(3))
      ->method('set');
    $this->cacheBackend->expects($this->at(4))
      ->method('set');
    $this->cacheBackend->expects($this->at(5))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));
    $this->cacheBackend->expects($this->at(6))
      ->method('get')
      ->with('entity_bundle_field_definitions:test_entity_type:test_bundle:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));

    $this->assertSame($expected, $this->entityManager->getFieldDefinitions('test_entity_type', 'test_bundle'));
    $this->entityManager->testClearEntityFieldInfo();
    $this->assertSame($expected, $this->entityManager->getFieldDefinitions('test_entity_type', 'test_bundle'));
  }

  /**
   * Tests the getFieldStorageDefinitions() method with caching.
   *
   * @covers ::getFieldStorageDefinitions
   */
  public function testGetFieldStorageDefinitionsWithCaching() {
    $field_definition = $this->setUpEntityWithFieldDefinition(TRUE, 'id');
    $field_storage_definition = $this->getMock('\Drupal\Core\Field\FieldStorageDefinitionInterface');
    $field_storage_definition->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('field_storage'));

    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValueMap(array(
        array('entity_field_storage_info', array('example_module')),
        array('entity_type_build', array())
      )));

    $this->moduleHandler->expects($this->once())
      ->method('invoke')
      ->with('example_module')
      ->will($this->returnValue(array('field_storage' => $field_storage_definition)));

    $expected = array(
      'id' => $field_definition,
      'field_storage' => $field_storage_definition,
    );

    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => array('id' => $expected['id']))));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with('entity_field_storage_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(2))
      ->method('get')
      ->with('entity_type', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with('entity_type');
    $this->cacheBackend->expects($this->at(4))
      ->method('set')
      ->with('entity_field_storage_definitions:test_entity_type:en');
    $this->cacheBackend->expects($this->at(5))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => array('id' => $expected['id']))));
    $this->cacheBackend->expects($this->at(6))
      ->method('get')
      ->with('entity_field_storage_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));

    $this->assertSame($expected, $this->entityManager->getFieldStorageDefinitions('test_entity_type'));
    $this->entityManager->testClearEntityFieldInfo();
    $this->assertSame($expected, $this->entityManager->getFieldStorageDefinitions('test_entity_type'));
  }

  /**
   * Tests the getBaseFieldDefinitions() method with an invalid definition.
   *
   * @covers ::getBaseFieldDefinitions
   * @covers ::buildBaseFieldDefinitions
   *
   * @expectedException \LogicException
   */
  public function testGetBaseFieldDefinitionsInvalidDefinition() {
    $langcode_definition = $this->setUpEntityWithFieldDefinition(FALSE, 'langcode', array('langcode' => 'langcode'));
    $langcode_definition->expects($this->once())
      ->method('isTranslatable')
      ->will($this->returnValue(FALSE));

    $this->entityType->expects($this->any())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));

    $this->entityManager->getBaseFieldDefinitions('test_entity_type');
  }

  /**
   * Tests that getFieldDefinitions() method sets the 'provider' definition key.
   *
   * @covers ::getFieldDefinitions
   * @covers ::buildBundleFieldDefinitions
   */
  public function testGetFieldDefinitionsProvider() {
    $this->setUpEntityWithFieldDefinition(TRUE);

    $module = 'entity_manager_test_module';

    // @todo Mock FieldDefinitionInterface once it exposes a proper provider
    //   setter. See https://drupal.org/node/2225961.
    $field_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();

    // We expect two calls as the field definition will be returned from both
    // base and bundle entity field info hook implementations.
    $field_definition
      ->expects($this->exactly(2))
      ->method('setProvider')
      ->with($this->matches($module));

    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue(array($module)));

    $this->moduleHandler->expects($this->any())
      ->method('invoke')
      ->with($this->matches($module))
      ->will($this->returnValue(array($field_definition)));

    $this->entityManager->getFieldDefinitions('test_entity_type', 'test_bundle');
  }

  /**
   * Prepares an entity that defines a field definition.
   *
   * @param bool $custom_invoke_all
   *   (optional) Whether the test will set up its own
   *   ModuleHandlerInterface::invokeAll() implementation. Defaults to FALSE.
   * @param string $field_definition_id
   *   (optional) The ID to use for the field definition. Defaults to 'id'.
   * @param array $entity_keys
   *   (optional) An array of entity keys for the mocked entity type. Defaults
   *   to an empty array.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition|\PHPUnit_Framework_MockObject_MockObject
   *   A field definition object.
   */
  protected function setUpEntityWithFieldDefinition($custom_invoke_all = FALSE, $field_definition_id = 'id', $entity_keys = array()) {
    $this->entityType = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');

    $this->entity = $this->getMockBuilder('Drupal\Tests\Core\Entity\EntityManagerTestEntity')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $entity_class = get_class($this->entity);

    $this->entityType->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue($entity_class));
    $this->entityType->expects($this->any())
      ->method('getKeys')
      ->will($this->returnValue($entity_keys + array('default_langcode' => 'default_langcode')));
    $this->entityType->expects($this->any())
      ->method('isSubclassOf')
      ->with($this->equalTo('\Drupal\Core\Entity\FieldableEntityInterface'))
      ->will($this->returnValue(TRUE));
    $field_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_class::$baseFieldDefinitions = array(
      $field_definition_id => $field_definition,
    );
    $entity_class::$bundleFieldDefinitions = array();

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('alter');
    if (!$custom_invoke_all) {
      $this->moduleHandler->expects($this->any())
        ->method('getImplementations')
        ->will($this->returnValue(array()));
    }

    // Mock the base field definition override.
    $override_entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $override_entity_type->expects($this->any())
       ->method('getClass')
       ->will($this->returnValue(get_class($this->entity)));

    $override_entity_type->expects($this->any())
      ->method('getHandlerClass')
      ->with('storage')
      ->will($this->returnValue('\Drupal\Tests\Core\Entity\TestConfigEntityStorage'));

    $this->setUpEntityManager(array('test_entity_type' => $this->entityType, 'base_field_override' => $override_entity_type));

    return $field_definition;
  }

  /**
   * Tests the clearCachedFieldDefinitions() method.
   *
   * @covers ::clearCachedFieldDefinitions
   */
  public function testClearCachedFieldDefinitions() {
    $this->setUpEntityManager();
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(array('entity_field_info'));
    $this->typedDataManager->expects($this->once())
      ->method('clearCachedDefinitions');

    $this->entityManager->clearCachedFieldDefinitions();
  }

  /**
   * Tests the clearCachedBundles() method.
   *
   * @covers ::clearCachedBundles
   */
  public function testClearCachedBundles() {
    $this->setUpEntityManager();
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(array('entity_bundles'));

    $this->entityManager->clearCachedBundles();
  }

  /**
   * Tests the getBundleInfo() method.
   *
   * @covers ::getBundleInfo
   *
   * @dataProvider providerTestGetBundleInfo
   */
  public function testGetBundleInfo($entity_type_id, $expected) {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('Apple'));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('Banana'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $bundle_info = $this->entityManager->getBundleInfo($entity_type_id);
    $this->assertSame($expected, $bundle_info);
  }

  /**
   * Provides test data for testGetBundleInfo().
   *
   * @return array
   *   Test data.
   */
  public function providerTestGetBundleInfo() {
    return array(
      array('apple', array(
        'apple' => array(
          'label' => 'Apple',
        ),
      )),
      array('banana', array(
        'banana' => array(
          'label' => 'Banana',
        ),
      )),
      array('pear', array()),
    );
  }

  /**
   * Tests the getAllBundleInfo() method.
   *
   * @covers ::getAllBundleInfo
   */
  public function testGetAllBundleInfo() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('Apple'));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('Banana'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));
    $this->cacheBackend->expects($this->at(0))
      ->method('get')
      ->with("entity_bundle_info:en", FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(1))
      ->method('get')
      ->with("entity_type", FALSE)
      ->will($this->returnValue(FALSE));
    $this->cacheBackend->expects($this->at(2))
      ->method('set')
      ->with("entity_type");
    $this->cacheBackend->expects($this->at(3))
      ->method('set')
      ->with("entity_bundle_info:en");
    $this->cacheTagsInvalidator->expects($this->at(0))
      ->method('invalidateTags')
      ->with(array('entity_types'));
    $this->cacheTagsInvalidator->expects($this->at(1))
      ->method('invalidateTags')
      ->with(array('entity_bundles'));
    $this->cacheTagsInvalidator->expects($this->at(2))
      ->method('invalidateTags')
      ->with(array('entity_field_info'));
    $this->cacheBackend->expects($this->at(4))
      ->method('get')
      ->with("entity_bundle_info:en", FALSE)
      ->will($this->returnValue((object) array('data' => 'cached data')));

    $expected = array(
      'apple' => array(
        'apple' => array(
          'label' => 'Apple',
        ),
      ),
      'banana' => array(
        'banana' => array(
          'label' => 'Banana',
        ),
      ),
    );
    $bundle_info = $this->entityManager->getAllBundleInfo();
    $this->assertSame($expected, $bundle_info);

    $bundle_info = $this->entityManager->getAllBundleInfo();
    $this->assertSame($expected, $bundle_info);

    $this->entityManager->clearCachedDefinitions();

    $bundle_info = $this->entityManager->getAllBundleInfo();
    $this->assertSame('cached data', $bundle_info);
  }

  /**
   * Tests the getEntityTypeLabels() method.
   *
   * @covers ::getEntityTypeLabels
   */
  public function testGetEntityTypeLabels() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('Apple'));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('Banana'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $expected = array(
      'apple' => 'Apple',
      'banana' => 'Banana',
    );
    $this->assertSame($expected, $this->entityManager->getEntityTypeLabels());
  }

  /**
   * Tests the getTranslationFromContext() method.
   *
   * @covers ::getTranslationFromContext
   */
  public function testGetTranslationFromContext() {
    $this->setUpEntityManager();

    $this->languageManager->expects($this->exactly(2))
      ->method('getFallbackCandidates')
      ->will($this->returnCallback(function (array $context = array()) {
        $candidates = array();
        if (!empty($context['langcode'])) {
          $candidates[$context['langcode']] = $context['langcode'];
        }
        return $candidates;
      }));

    $entity = $this->getMock('Drupal\Tests\Core\Entity\TestContentEntityInterface');
    $entity->expects($this->exactly(2))
      ->method('getUntranslated')
      ->will($this->returnValue($entity));
    $language = $this->getMock('\Drupal\Core\Language\LanguageInterface');
    $language->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $entity->expects($this->exactly(2))
      ->method('language')
      ->will($this->returnValue($language));
    $entity->expects($this->exactly(2))
      ->method('hasTranslation')
      ->will($this->returnValueMap(array(
        array(LanguageInterface::LANGCODE_DEFAULT, FALSE),
        array('custom_langcode', TRUE),
      )));

    $translated_entity = $this->getMock('Drupal\Tests\Core\Entity\TestContentEntityInterface');
    $entity->expects($this->once())
      ->method('getTranslation')
      ->with('custom_langcode')
      ->will($this->returnValue($translated_entity));
    $entity->expects($this->any())
      ->method('getTranslationLanguages')
      ->will($this->returnValue([new Language(['id' => 'en']), new Language(['id' => 'custom_langcode'])]));

    $this->assertSame($entity, $this->entityManager->getTranslationFromContext($entity));
    $this->assertSame($translated_entity, $this->entityManager->getTranslationFromContext($entity, 'custom_langcode'));
  }

  /**
   * @covers ::getExtraFields
   */
  function testgetExtraFields() {
    $this->setUpEntityManager();

    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $language_code = 'en';
    $hook_bundle_extra_fields = array(
      $entity_type_id => array(
        $bundle => array(
          'form' => array(
            'foo_extra_field' => array(
              'label' => 'Foo',
            ),
          ),
        ),
      ),
    );
    $processed_hook_bundle_extra_fields = $hook_bundle_extra_fields;
    $processed_hook_bundle_extra_fields[$entity_type_id][$bundle] += array(
      'display' => array(),
    );
    $cache_id = 'entity_bundle_extra_fields:' . $entity_type_id . ':' . $bundle . ':' . $language_code;

    $language = new Language(array('id' => $language_code));

    $this->languageManager->expects($this->once())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with($cache_id);

    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('entity_extra_field_info')
      ->will($this->returnValue($hook_bundle_extra_fields));
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('entity_extra_field_info', $hook_bundle_extra_fields);

    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with($cache_id, $processed_hook_bundle_extra_fields[$entity_type_id][$bundle]);

    $this->assertSame($processed_hook_bundle_extra_fields[$entity_type_id][$bundle], $this->entityManager->getExtraFields($entity_type_id, $bundle));
  }

  /**
   * @covers ::getFieldMap
   */
  public function testGetFieldMap() {
    // Set up a content entity type.
    $entity_type = $this->getMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $entity = $this->getMockBuilder('Drupal\Tests\Core\Entity\EntityManagerTestEntity')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $entity_class = get_class($entity);
    $entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue($entity_class));
    $entity_type->expects($this->any())
      ->method('getKeys')
      ->will($this->returnValue(array('default_langcode' => 'default_langcode')));
    $entity_type->expects($this->any())
      ->method('id')
      ->will($this->returnValue('test_entity_type'));
    $entity_type->expects($this->any())
      ->method('isSubclassOf')
      ->with('\Drupal\Core\Entity\FieldableEntityInterface')
      ->will($this->returnValue(TRUE));

    // Set up the module handler to return two bundles for the fieldable entity
    // type.
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('alter');
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue(array()));
    $module_implements_value_map = array(
      array(
        'entity_bundle_info', array(),
        array(
          'test_entity_type' => array(
            'first_bundle' => array(),
            'second_bundle' => array(),
          ),
        ),
      ),
    );
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValueMap($module_implements_value_map));


    // Define an ID field definition as a base field.
    $id_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $id_definition->expects($this->exactly(2))
      ->method('getType')
      ->will($this->returnValue('integer'));
    $base_field_definitions = array(
      'id' => $id_definition,
    );
    $entity_class::$baseFieldDefinitions = $base_field_definitions;

    // Set up a by bundle field definition that only exists on one bundle.
    $bundle_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $bundle_definition->expects($this->once())
      ->method('getType')
      ->will($this->returnValue('string'));
    $entity_class::$bundleFieldDefinitions = array(
      'test_entity_type' => array(
        'first_bundle' => array(),
        'second_bundle' => array(
          'by_bundle' => $bundle_definition,
        ),
      ),
    );

    // Set up a non-content entity type.
    $non_content_entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('isSubclassOf')
      ->with('\Drupal\Core\Entity\FieldableEntityInterface')
      ->will($this->returnValue(FALSE));

    // Mock the base field definition override.
    $override_entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $override_entity_class = get_class($entity);
    $override_entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue($override_entity_class));

    $override_entity_type->expects($this->any())
      ->method('getHandlerClass')
      ->with('storage')
      ->will($this->returnValue('\Drupal\Tests\Core\Entity\TestConfigEntityStorage'));

    $this->setUpEntityManager(array(
      'test_entity_type' => $entity_type,
      'non_fieldable' => $non_content_entity_type,
      'base_field_override' => $override_entity_type,
    ));

    $expected = array(
      'test_entity_type' => array(
        'id' => array(
          'type' => 'integer',
          'bundles' => array('first_bundle', 'second_bundle'),
        ),
        'by_bundle' => array(
          'type' => 'string',
          'bundles' => array('second_bundle'),
        ),
      )
    );
    $this->assertEquals($expected, $this->entityManager->getFieldMap());
  }

  /**
   * @covers ::getFieldMap
   */
  public function testGetFieldMapFromCache() {
    $expected = array(
      'test_entity_type' => array(
        'id' => array(
          'type' => 'integer',
          'bundles' => array('first_bundle', 'second_bundle'),
        ),
        'by_bundle' => array(
          'type' => 'string',
          'bundles' => array('second_bundle'),
        ),
      )
    );
    $this->setUpEntityManager();
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('entity_field_map')
      ->will($this->returnValue((object) array('data' => $expected)));

    // Call the field map twice to make sure the static cache works.
    $this->assertEquals($expected, $this->entityManager->getFieldMap());
    $this->assertEquals($expected, $this->entityManager->getFieldMap());
  }

  /**
   * @covers ::getFieldMapByFieldType
   */
  public function testGetFieldMapByFieldType() {
    // Set up a content entity type.
    $entity_type = $this->getMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $entity = $this->getMockBuilder('Drupal\Tests\Core\Entity\EntityManagerTestEntity')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $entity_class = get_class($entity);
    $entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue($entity_class));
    $entity_type->expects($this->any())
      ->method('getKeys')
      ->will($this->returnValue(array('default_langcode' => 'default_langcode')));
    $entity_type->expects($this->any())
      ->method('id')
      ->will($this->returnValue('test_entity_type'));
    $entity_type->expects($this->any())
      ->method('isSubclassOf')
      ->with('\Drupal\Core\Entity\FieldableEntityInterface')
      ->will($this->returnValue(TRUE));

    // Set up the module handler to return two bundles for the fieldable entity
    // type.
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->any())
      ->method('alter');
    $this->moduleHandler->expects($this->any())
      ->method('getImplementations')
      ->will($this->returnValue(array()));
    $module_implements_value_map = array(
      array(
        'entity_bundle_info', array(),
        array(
          'test_entity_type' => array(
            'first_bundle' => array(),
            'second_bundle' => array(),
          ),
        ),
      ),
    );
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->will($this->returnValueMap($module_implements_value_map));


    // Define an ID field definition as a base field.
    $id_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $id_definition->expects($this->exactly(2))
      ->method('getType')
      ->will($this->returnValue('integer'));
    $base_field_definitions = array(
      'id' => $id_definition,
    );
    $entity_class::$baseFieldDefinitions = $base_field_definitions;

    // Set up a by bundle field definition that only exists on one bundle.
    $bundle_definition = $this->getMockBuilder('Drupal\Core\Field\BaseFieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $bundle_definition->expects($this->once())
      ->method('getType')
      ->will($this->returnValue('string'));
    $entity_class::$bundleFieldDefinitions = array(
      'test_entity_type' => array(
        'first_bundle' => array(),
        'second_bundle' => array(
          'by_bundle' => $bundle_definition,
        ),
      ),
    );

    // Mock the base field definition override.
    $override_entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $override_entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue(get_class($entity)));

    $override_entity_type->expects($this->any())
      ->method('getHandlerClass')
      ->with('storage')
      ->will($this->returnValue('\Drupal\Tests\Core\Entity\TestConfigEntityStorage'));

    $this->setUpEntityManager(array(
      'test_entity_type' => $entity_type,
      'base_field_override' => $override_entity_type,
    ));

    $integerFields = $this->entityManager->getFieldMapByFieldType('integer');
    $this->assertCount(1, $integerFields['test_entity_type']);
    $this->assertArrayNotHasKey('non_fieldable', $integerFields);
    $this->assertArrayHasKey('id', $integerFields['test_entity_type']);
    $this->assertArrayNotHasKey('by_bundle', $integerFields['test_entity_type']);

    $stringFields = $this->entityManager->getFieldMapByFieldType('string');
    $this->assertCount(1, $stringFields['test_entity_type']);
    $this->assertArrayNotHasKey('non_fieldable', $stringFields);
    $this->assertArrayHasKey('by_bundle', $stringFields['test_entity_type']);
    $this->assertArrayNotHasKey('id', $stringFields['test_entity_type']);
  }

  /**
   * @covers ::getEntityTypeFromClass
   */
  public function testGetEntityTypeFromClass() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->exactly(2))
      ->method('getOriginalClass')
      ->will($this->returnValue('\Drupal\apple\Entity\Apple'));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->exactly(2))
      ->method('getOriginalClass')
      ->will($this->returnValue('\Drupal\banana\Entity\Banana'));
    $banana->expects($this->once())
      ->method('getClass')
      ->will($this->returnValue('\Drupal\mango\Entity\Mango'));
    $banana->expects($this->exactly(2))
      ->method('id')
      ->will($this->returnValue('banana'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $entity_type_id = $this->entityManager->getEntityTypeFromClass('\Drupal\banana\Entity\Banana');
    $this->assertSame('banana', $entity_type_id);
    $entity_type_id = $this->entityManager->getEntityTypeFromClass('\Drupal\mango\Entity\Mango');
    $this->assertSame('banana', $entity_type_id);
  }

  /**
   * @covers ::getEntityTypeFromClass
   *
   * @expectedException \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException
   * @expectedExceptionMessage The \Drupal\pear\Entity\Pear class does not correspond to an entity type.
   */
  public function testGetEntityTypeFromClassNoMatch() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getOriginalClass')
      ->will($this->returnValue('\Drupal\apple\Entity\Apple'));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getOriginalClass')
      ->will($this->returnValue('\Drupal\banana\Entity\Banana'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $this->entityManager->getEntityTypeFromClass('\Drupal\pear\Entity\Pear');
  }

  /**
   * @covers ::getEntityTypeFromClass
   *
   * @expectedException \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   * @expectedExceptionMessage Multiple entity types found for \Drupal\apple\Entity\Apple.
   */
  public function testGetEntityTypeFromClassAmbiguous() {
    $boskoop = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $boskoop->expects($this->once())
      ->method('getOriginalClass')
      ->will($this->returnValue('\Drupal\apple\Entity\Apple'));
    $boskoop->expects($this->once())
      ->method('id')
      ->will($this->returnValue('boskop'));
    $gala = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $gala->expects($this->once())
      ->method('getOriginalClass')
      ->will($this->returnValue('\Drupal\apple\Entity\Apple'));
    $gala->expects($this->once())
      ->method('id')
      ->will($this->returnValue('gala'));
    $this->setUpEntityManager(array(
      'boskoop' => $boskoop,
      'gala' => $gala,
    ));

    $this->entityManager->getEntityTypeFromClass('\Drupal\apple\Entity\Apple');
  }

  /**
   * @covers ::getRouteProviders
   */
  public function testGetRouteProviders() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getRouteProviderClasses')
      ->willReturn(['default' => 'Drupal\Tests\Core\Entity\TestRouteProvider']);
    $this->setUpEntityManager(array(
      'apple' => $apple,
    ));

    $apple_route_provider = $this->entityManager->getRouteProviders('apple');
    $this->assertInstanceOf('Drupal\Tests\Core\Entity\TestRouteProvider', $apple_route_provider['default']);
    $this->assertAttributeInstanceOf('Drupal\Core\Extension\ModuleHandlerInterface', 'moduleHandler', $apple_route_provider['default']);
    $this->assertAttributeInstanceOf('Drupal\Core\StringTranslation\TranslationInterface', 'stringTranslation', $apple_route_provider['default']);
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestHandlerClass() {
    return get_class($this->getMockForAbstractClass('Drupal\Core\Entity\EntityHandlerBase'));
  }

}

/*
 * Provides a content entity with dummy static method implementations.
 */
abstract class EntityManagerTestEntity implements \Iterator, ContentEntityInterface {

  /**
   * The base field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public static $baseFieldDefinitions = array();

  /**
   * The bundle field definitions.
   *
   * @var array[]
   *   Keys are entity type IDs, values are arrays of which the keys are bundle
   *   names and the values are field definitions.
   */
  public static $bundleFieldDefinitions = array();

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    return static::$baseFieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    return isset(static::$bundleFieldDefinitions[$entity_type->id()][$bundle]) ? static::$bundleFieldDefinitions[$entity_type->id()][$bundle] : array();
  }

}

/**
 * Provides a testable version of ContentEntityInterface.
 *
 * @see https://github.com/sebastianbergmann/phpunit-mock-objects/commit/96a6794
 */
interface TestContentEntityInterface extends \Iterator, ContentEntityInterface {
}

/**
 * Provides a testing version of EntityManager with an empty constructor.
 */
class TestEntityManager extends EntityManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

  /**
   * Allows the static caches to be cleared.
   */
  public function testClearEntityFieldInfo() {
    $this->baseFieldDefinitions = array();
    $this->fieldDefinitions = array();
    $this->fieldStorageDefinitions = array();
  }

}

/**
 * Provides a test entity handler that uses injection.
 */
class TestEntityHandlerInjected implements EntityHandlerInterface {

  /**
   * The color of the entity type.
   *
   * @var string
   */
  protected $color;

  /**
   * Constructs a new TestEntityHandlerInjected.
   *
   * @param string $color
   *   The color of the entity type.
   */
  public function __construct($color) {
    $this->color = $color;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static('yellow');
  }

}

/**
 * Provides a test entity form.
 */
class TestEntityForm extends EntityHandlerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Tests\Core\Entity\TestEntityManager
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'the_base_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'the_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityManager(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    return $this;
  }

}

/**
 * Provides a test entity form that uses injection.
 */
class TestEntityFormInjected extends TestEntityForm implements ContainerInjectionInterface {

  /**
   * The color of the entity type.
   *
   * @var string
   */
  protected $color;

  /**
   * Constructs a new TestEntityFormInjected.
   *
   * @param string $color
   *   The color of the entity type.
   */
  public function __construct($color) {
    $this->color = $color;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static('yellow');
  }

}

/**
 * Provides a test entity route provider.
 */
class TestRouteProvider extends EntityHandlerBase {

}


/**
 * Provides a test config entity storage for base field overrides.
 */
class TestConfigEntityStorage extends ConfigEntityStorage {

  public function __construct($entity_type) {
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type
    );
  }

  public function loadMultiple(array $ids = NULL) {
    return array();
  }
}

}

namespace {

  /**
   * Implements hook_entity_type_build().
   */
  function entity_manager_test_module_entity_type_build() {
  }
}
