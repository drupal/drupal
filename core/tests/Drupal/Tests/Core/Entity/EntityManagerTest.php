<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityManagerTest.
 */

namespace Drupal\Tests\Core\Entity {

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\DynamicallyFieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityType;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $discovery;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $container;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheBackend;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $languageManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $typedDataManager;

  /**
   * The keyvalue factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $keyValueFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->getImplementations('entity_type_build')->willReturn([]);
    $this->moduleHandler->alter('entity_type', Argument::type('array'))->willReturn(NULL);
    $this->moduleHandler->alter('entity_base_field_info', Argument::type('array'), Argument::any())->willReturn(NULL);
    $this->moduleHandler->alter('entity_bundle_field_info', Argument::type('array'), Argument::any(), Argument::type('string'))->willReturn(NULL);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
    $this->cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $language = new Language(['id' => 'en']);
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->languageManager->getCurrentLanguage()->willReturn($language);
    $this->languageManager->getLanguages()->willReturn(['en' => (object) ['id' => 'en']]);

    $this->typedDataManager = $this->prophesize(TypedDataManager::class);
    $this->typedDataManager->getDefinition('field_item:boolean')->willReturn([
      'class' => BooleanItem::class,
    ]);

    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

    $this->keyValueFactory = $this->prophesize(KeyValueFactoryInterface::class);

    $this->container = $this->prophesize(ContainerInterface::class);
    $this->container->get('cache_tags.invalidator')->willReturn($this->cacheTagsInvalidator->reveal());
    $this->container->get('typed_data_manager')->willReturn($this->typedDataManager->reveal());
    \Drupal::setContainer($this->container->reveal());

    $this->discovery = $this->prophesize(DiscoveryInterface::class);
    $translation_manager = $this->prophesize(TranslationInterface::class);

    $this->entityManager = new TestEntityManager(new \ArrayObject(), $this->moduleHandler->reveal(), $this->cacheBackend->reveal(), $this->languageManager->reveal(), $translation_manager->reveal(), $this->getClassResolverStub(), $this->typedDataManager->reveal(), $this->keyValueFactory->reveal(), $this->eventDispatcher->reveal());
    $this->entityManager->setContainer($this->container->reveal());
    $this->entityManager->setDiscovery($this->discovery->reveal());
  }

  /**
   * Sets up the entity manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\Prophecy\Prophecy\ProphecyInterface[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityManager($definitions = array()) {
    $class = $this->getMockClass(EntityInterface::class);
    foreach ($definitions as $key => $entity_type) {
      // \Drupal\Core\Entity\EntityTypeInterface::getLinkTemplates() is called
      // by \Drupal\Core\Entity\EntityManager::processDefinition() so it must
      // always be mocked.
      $entity_type->getLinkTemplates()->willReturn([]);

      // Give the entity type a legitimate class to return.
      $entity_type->getClass()->willReturn($class);

      $definitions[$key] = $entity_type->reveal();
    }

    $this->discovery->getDefinition(Argument::cetera())
      ->will(function ($args) use ($definitions) {
        $entity_type_id = $args[0];
        $exception_on_invalid = $args[1];
        if (isset($definitions[$entity_type_id])) {
          return $definitions[$entity_type_id];
        }
        elseif (!$exception_on_invalid) {
          return NULL;
        }
        else throw new PluginNotFoundException($entity_type_id);
    });
    $this->discovery->getDefinitions()->willReturn($definitions);
  }

  /**
   * Tests the clearCachedDefinitions() method.
   *
   * @covers ::clearCachedDefinitions
   *
   */
  public function testClearCachedDefinitions() {
    $this->setUpEntityManager();

    $this->typedDataManager->clearCachedDefinitions()->shouldBeCalled();

    $this->cacheTagsInvalidator->invalidateTags(['entity_types'])->shouldBeCalled();
    $this->cacheTagsInvalidator->invalidateTags(['entity_bundles'])->shouldBeCalled();
    $this->cacheTagsInvalidator->invalidateTags(['entity_field_info'])->shouldBeCalled();

    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Tests the processDefinition() method.
   *
   * @covers ::processDefinition
   *
   * @expectedException \Drupal\Core\Entity\Exception\InvalidLinkTemplateException
   * @expectedExceptionMessage Link template 'canonical' for entity type 'apple' must start with a leading slash, the current link template is 'path/to/apple'
   */
  public function testProcessDefinition() {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(array('apple' => $apple));

    $apple->getLinkTemplates()->willReturn(['canonical' => 'path/to/apple']);

    $definition = $apple->reveal();
    $this->entityManager->processDefinition($definition, 'apple');
  }

  /**
   * Tests the getDefinition() method.
   *
   * @covers ::getDefinition
   *
   * @dataProvider providerTestGetDefinition
   */
  public function testGetDefinition($entity_type_id, $expected) {
    $entity = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityManager(array(
      'apple' => $entity,
      'banana' => $entity,
    ));

    $entity_type = $this->entityManager->getDefinition($entity_type_id, FALSE);
    if ($expected) {
      $this->assertInstanceOf(EntityTypeInterface::class, $entity_type);
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
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->hasHandlerClass('storage')->willReturn(TRUE);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->hasHandlerClass('storage')->willReturn(FALSE);

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
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
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
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('list_builder')->willReturn($class);
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
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('view_builder')->willReturn($class);
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
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('access')->willReturn($class);
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getAccessControlHandler('test_entity_type'));
  }

  /**
   * Tests the getFormObject() method.
   *
   * @covers ::getFormObject
   */
  public function testGetFormObject() {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getFormClass('default')->willReturn(TestEntityForm::class);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getFormClass('default')->willReturn(TestEntityFormInjected::class);

    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $apple_form = $this->entityManager->getFormObject('apple', 'default');
    $this->assertInstanceOf(TestEntityForm::class, $apple_form);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $apple_form);
    $this->assertAttributeInstanceOf(TranslationInterface::class, 'stringTranslation', $apple_form);

    $banana_form = $this->entityManager->getFormObject('banana', 'default');
    $this->assertInstanceOf(TestEntityFormInjected::class, $banana_form);
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
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getFormClass('edit')->willReturn('');
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
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getHandlerClass('storage')->willReturn($class);

    $this->setUpEntityManager(array(
      'apple' => $apple,
    ));

    $apple_controller = $this->entityManager->getHandler('apple', 'storage');
    $this->assertInstanceOf($class, $apple_controller);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $apple_controller);
    $this->assertAttributeInstanceOf(TranslationInterface::class, 'stringTranslation', $apple_controller);
  }

  /**
   * Tests the getHandler() method when no controller is defined.
   *
   * @covers ::getHandler
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetHandlerMissingHandler() {
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn('');
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
    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getName()->willReturn('field_storage');

    $definitions = ['field_storage' => $field_storage_definition->reveal()];

    $this->moduleHandler->getImplementations('entity_base_field_info')->willReturn([]);
    $this->moduleHandler->getImplementations('entity_field_storage_info')->willReturn(['example_module']);
    $this->moduleHandler->invoke('example_module', 'entity_field_storage_info', [$this->entityType])->willReturn($definitions);
    $this->moduleHandler->alter('entity_field_storage_info', $definitions, $this->entityType)->willReturn(NULL);

    $expected = array(
      'id' => $field_definition,
      'field_storage' => $field_storage_definition->reveal(),
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

    $field_definition = $this->prophesize()->willImplement(FieldDefinitionInterface::class)->willImplement(FieldStorageDefinitionInterface::class);
    $field_definition->isTranslatable()->willReturn(TRUE);

    $entity_class = EntityManagerTestEntity::class;
    $entity_class::$baseFieldDefinitions += array('langcode' => $field_definition);

    $this->entityType->isTranslatable()->willReturn(TRUE);

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
      $field_definition = $this->prophesize()->willImplement(FieldDefinitionInterface::class)->willImplement(FieldStorageDefinitionInterface::class);
      $field_definition->isTranslatable()->willReturn($translatable);
      if (!$translatable) {
        $field_definition->setTranslatable(!$translatable)->shouldBeCalled();
      }

      $entity_class = EntityManagerTestEntity::class;
      $entity_class::$baseFieldDefinitions += array('langcode' => $field_definition->reveal());
    }

    $this->entityType->isTranslatable()->willReturn(TRUE);
    $this->entityType->getLabel()->willReturn('Test');

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

    $this->cacheBackend->get('entity_base_field_definitions:test_entity_type:en')
      ->willReturn(FALSE)
      ->shouldBeCalled();
    $this->cacheBackend->set('entity_base_field_definitions:test_entity_type:en', Argument::any(), Cache::PERMANENT, ['entity_types', 'entity_field_info'])
      ->will(function ($args) {
        $data = (object) ['data' => $args[1]];
        $this->get('entity_base_field_definitions:test_entity_type:en')
          ->willReturn($data)
          ->shouldBeCalled();
      })
      ->shouldBeCalled();
    $this->cacheBackend->get('entity_type')->willReturn(FALSE);
    $this->cacheBackend->set('entity_type', Argument::any(), Cache::PERMANENT, ['entity_types'])->shouldBeCalled();

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

    $this->cacheBackend->get('entity_base_field_definitions:test_entity_type:en')
      ->willReturn((object) array('data' => $expected))
      ->shouldBeCalledTimes(2);
    $this->cacheBackend->get('entity_bundle_field_definitions:test_entity_type:test_bundle:en')
      ->willReturn(FALSE)
      ->shouldBeCalledTimes(1);
    $this->cacheBackend->get('entity_type')->willReturn(FALSE);
    $this->cacheBackend->set('entity_type', Argument::any(), Cache::PERMANENT, ['entity_types'])->shouldBeCalled();
    $this->cacheBackend->set('entity_bundle_field_definitions:test_entity_type:test_bundle:en', Argument::any(), Cache::PERMANENT, ['entity_types', 'entity_field_info'])
      ->will(function ($args) {
        $data = (object) ['data' => $args[1]];
        $this->get('entity_bundle_field_definitions:test_entity_type:test_bundle:en')
          ->willReturn($data)
          ->shouldBeCalled();
      })
      ->shouldBeCalled();

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
    $field_storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $field_storage_definition->getName()->willReturn('field_storage');

    $definitions = ['field_storage' => $field_storage_definition->reveal()];

    $this->moduleHandler->getImplementations('entity_field_storage_info')->willReturn(['example_module']);
    $this->moduleHandler->invoke('example_module', 'entity_field_storage_info', [$this->entityType])->willReturn($definitions);
    $this->moduleHandler->alter('entity_field_storage_info', $definitions, $this->entityType)->willReturn(NULL);

    $expected = array(
      'id' => $field_definition,
      'field_storage' => $field_storage_definition->reveal(),
    );

    $this->cacheBackend->get('entity_base_field_definitions:test_entity_type:en')
      ->willReturn((object) ['data' => ['id' => $expected['id']]])
      ->shouldBeCalledTimes(2);
    $this->cacheBackend->get('entity_field_storage_definitions:test_entity_type:en')->willReturn(FALSE);
    $this->cacheBackend->get('entity_type')->willReturn(FALSE);

    $this->cacheBackend->set('entity_type', Argument::any(), Cache::PERMANENT, ['entity_types'])->shouldBeCalled();
    $this->cacheBackend->set('entity_field_storage_definitions:test_entity_type:en', Argument::any(), Cache::PERMANENT, ['entity_types', 'entity_field_info'])
      ->will(function () use ($expected) {
        $this->get('entity_field_storage_definitions:test_entity_type:en')
          ->willReturn((object) ['data' => $expected])
          ->shouldBeCalled();
      })
      ->shouldBeCalled();


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
    $this->setUpEntityWithFieldDefinition(FALSE, 'langcode', array('langcode' => 'langcode'));

    $this->entityType->isTranslatable()->willReturn(TRUE);
    $this->entityType->getLabel()->willReturn('the_label');

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
    //   setter. See https://www.drupal.org/node/2225961.
    $field_definition = $this->prophesize(BaseFieldDefinition::class);

    // We expect two calls as the field definition will be returned from both
    // base and bundle entity field info hook implementations.
    $field_definition->getProvider()->shouldBeCalled();
    $field_definition->setProvider($module)->shouldBeCalledTimes(2);
    $field_definition->setName(0)->shouldBeCalledTimes(2);
    $field_definition->setTargetEntityTypeId('test_entity_type')->shouldBeCalled();
    $field_definition->setTargetBundle(NULL)->shouldBeCalled();
    $field_definition->setTargetBundle('test_bundle')->shouldBeCalled();

    $this->moduleHandler->getImplementations(Argument::type('string'))->willReturn([$module]);
    $this->moduleHandler->invoke($module, 'entity_base_field_info', [$this->entityType])->willReturn([$field_definition->reveal()]);
    $this->moduleHandler->invoke($module, 'entity_bundle_field_info', Argument::type('array'))->willReturn([$field_definition->reveal()]);

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
   * @return \Drupal\Core\Field\BaseFieldDefinition|\Prophecy\Prophecy\ProphecyInterface
   *   A field definition object.
   */
  protected function setUpEntityWithFieldDefinition($custom_invoke_all = FALSE, $field_definition_id = 'id', $entity_keys = array()) {
    $field_type_manager = $this->prophesize(FieldTypePluginManagerInterface::class);
    $field_type_manager->getDefaultStorageSettings('boolean')->willReturn([]);
    $field_type_manager->getDefaultFieldSettings('boolean')->willReturn([]);
    $this->container->get('plugin.manager.field.field_type')->willReturn($field_type_manager->reveal());

    $string_translation = $this->prophesize(TranslationInterface::class);
    $this->container->get('string_translation')->willReturn($string_translation->reveal());

    $entity_class = EntityManagerTestEntity::class;

    $field_definition = $this->prophesize()->willImplement(FieldDefinitionInterface::class)->willImplement(FieldStorageDefinitionInterface::class);
    $entity_class::$baseFieldDefinitions = array(
      $field_definition_id => $field_definition->reveal(),
    );
    $entity_class::$bundleFieldDefinitions = array();

    if (!$custom_invoke_all) {
      $this->moduleHandler->getImplementations(Argument::cetera())->willReturn([]);
    }

    // Mock the base field definition override.
    $override_entity_type = $this->prophesize(EntityTypeInterface::class);

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->setUpEntityManager(array('test_entity_type' => $this->entityType, 'base_field_override' => $override_entity_type));

    $override_entity_type->getClass()->willReturn($entity_class);
    $override_entity_type->getHandlerClass('storage')->willReturn(TestConfigEntityStorage::class);

    $this->entityType->getClass()->willReturn($entity_class);
    $this->entityType->getKeys()->willReturn($entity_keys + ['default_langcode' => 'default_langcode']);
    $this->entityType->isSubclassOf(FieldableEntityInterface::class)->willReturn(TRUE);
    $this->entityType->isTranslatable()->willReturn(FALSE);
    $this->entityType->getProvider()->willReturn('the_provider');
    $this->entityType->id()->willReturn('the_entity_id');

    return $field_definition->reveal();
  }

  /**
   * Tests the clearCachedFieldDefinitions() method.
   *
   * @covers ::clearCachedFieldDefinitions
   */
  public function testClearCachedFieldDefinitions() {
    $this->setUpEntityManager();

    $this->cacheTagsInvalidator->invalidateTags(['entity_field_info'])->shouldBeCalled();

    $this->typedDataManager->clearCachedDefinitions()->shouldBeCalled();

    $this->entityManager->clearCachedFieldDefinitions();
  }

  /**
   * Tests the clearCachedBundles() method.
   *
   * @covers ::clearCachedBundles
   */
  public function testClearCachedBundles() {
    $this->setUpEntityManager();

    $this->typedDataManager->clearCachedDefinitions()->shouldBeCalled();

    $this->cacheTagsInvalidator->invalidateTags(['entity_bundles'])->shouldBeCalled();

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
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleOf()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleOf()->willReturn(NULL);

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
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleOf()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleOf()->willReturn(NULL);

    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $this->cacheBackend->get('entity_bundle_info:en')->willReturn(FALSE);
    $this->cacheBackend->get('entity_type')->willReturn(FALSE);
    $this->cacheBackend->set('entity_type', Argument::any(), Cache::PERMANENT, ['entity_types'])->shouldBeCalled();
    $this->cacheBackend->set('entity_bundle_info:en', Argument::any(), Cache::PERMANENT, ['entity_types', 'entity_bundles'])
      ->will(function () {
        $this->get('entity_bundle_info:en')
          ->willReturn((object) ['data' => 'cached data'])
          ->shouldBeCalled();
      })
      ->shouldBeCalled();

    $this->cacheTagsInvalidator->invalidateTags(['entity_types'])->shouldBeCalled();
    $this->cacheTagsInvalidator->invalidateTags(['entity_bundles'])->shouldBeCalled();
    $this->cacheTagsInvalidator->invalidateTags(['entity_field_info'])->shouldBeCalled();

    $this->typedDataManager->clearCachedDefinitions()->shouldBeCalled();

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
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getLabel()->willReturn('Apple');
    $apple->getBundleOf()->willReturn(NULL);

    $banana = $this->prophesize(EntityTypeInterface::class);
    $banana->getLabel()->willReturn('Banana');
    $banana->getBundleOf()->willReturn(NULL);

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

    $language = new Language(['id' => 'en']);
    $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->willReturn($language)
      ->shouldBeCalledTimes(1);
    $this->languageManager->getFallbackCandidates(Argument::type('array'))
      ->will(function ($args) {
        $context = $args[0];
        $candidates = array();
        if (!empty($context['langcode'])) {
          $candidates[$context['langcode']] = $context['langcode'];
        }
        return $candidates;
      })
      ->shouldBeCalledTimes(1);

    $translated_entity = $this->prophesize(ContentEntityInterface::class);

    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->getUntranslated()->willReturn($entity);
    $entity->language()->willReturn($language);
    $entity->hasTranslation(LanguageInterface::LANGCODE_DEFAULT)->willReturn(FALSE);
    $entity->hasTranslation('custom_langcode')->willReturn(TRUE);
    $entity->getTranslation('custom_langcode')->willReturn($translated_entity->reveal());
    $entity->getTranslationLanguages()->willReturn([new Language(['id' => 'en']), new Language(['id' => 'custom_langcode'])]);
    $entity->addCacheContexts(['languages:language_content'])->shouldBeCalled();

    $this->assertSame($entity->reveal(), $this->entityManager->getTranslationFromContext($entity->reveal()));
    $this->assertSame($translated_entity->reveal(), $this->entityManager->getTranslationFromContext($entity->reveal(), 'custom_langcode'));
  }

  /**
   * @covers ::getExtraFields
   */
  function testGetExtraFields() {
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
    $this->languageManager->getCurrentLanguage()
      ->willReturn($language)
      ->shouldBeCalledTimes(1);

    $this->cacheBackend->get($cache_id)->shouldBeCalled();

    $this->moduleHandler->invokeAll('entity_extra_field_info')->willReturn($hook_bundle_extra_fields);
    $this->moduleHandler->alter('entity_extra_field_info', $hook_bundle_extra_fields)->shouldBeCalled();

    $this->cacheBackend->set($cache_id, $processed_hook_bundle_extra_fields[$entity_type_id][$bundle], Cache::PERMANENT, ['entity_field_info'])->shouldBeCalled();

    $this->assertSame($processed_hook_bundle_extra_fields[$entity_type_id][$bundle], $this->entityManager->getExtraFields($entity_type_id, $bundle));
  }

  /**
   * @covers ::getFieldMap
   */
  public function testGetFieldMap() {
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    // Set up a content entity type.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_class = EntityManagerTestEntity::class;

    // Define an ID field definition as a base field.
    $id_definition = $this->prophesize(FieldDefinitionInterface::class);
    $id_definition->getType()->willReturn('integer');
    $base_field_definitions = array(
      'id' => $id_definition->reveal(),
    );
    $entity_class::$baseFieldDefinitions = $base_field_definitions;

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->getAll()->willReturn([
      'test_entity_type' => [
        'by_bundle' => [
          'type' => 'string',
          'bundles' => ['second_bundle' => 'second_bundle'],
        ],
      ],
    ]);

    // Set up a non-content entity type.
    $non_content_entity_type = $this->prophesize(EntityTypeInterface::class);

    // Mock the base field definition override.
    $override_entity_type = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityManager(array(
      'test_entity_type' => $entity_type,
      'non_fieldable' => $non_content_entity_type,
      'base_field_override' => $override_entity_type,
    ));

    $entity_type->getClass()->willReturn($entity_class);
    $entity_type->getKeys()->willReturn(['default_langcode' => 'default_langcode']);
    $entity_type->getBundleOf()->willReturn(NULL);
    $entity_type->id()->willReturn('test_entity_type');
    $entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type->isTranslatable()->shouldBeCalled();
    $entity_type->getProvider()->shouldBeCalled();

    $non_content_entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(FALSE);
    $non_content_entity_type->getBundleOf()->willReturn(NULL);
    $non_content_entity_type->getLabel()->shouldBeCalled();

    $override_entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(FALSE);
    $override_entity_type->getHandlerClass('storage')->willReturn(TestConfigEntityStorage::class);
    $override_entity_type->getBundleOf()->willReturn(NULL);
    $override_entity_type->getLabel()->shouldBeCalled();

    // Set up the module handler to return two bundles for the fieldable entity
    // type.
    $this->moduleHandler->alter(Argument::type('string'), Argument::type('array'));
    $this->moduleHandler->getImplementations('entity_base_field_info')->willReturn([]);
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([
      'test_entity_type' => [
        'first_bundle' => [],
        'second_bundle' => [],
      ],
    ]);

    $expected = array(
      'test_entity_type' => array(
        'id' => array(
          'type' => 'integer',
          'bundles' => array('first_bundle' => 'first_bundle', 'second_bundle' => 'second_bundle'),
        ),
        'by_bundle' => array(
          'type' => 'string',
          'bundles' => array('second_bundle' => 'second_bundle'),
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
          'bundles' => array('first_bundle' => 'first_bundle', 'second_bundle' => 'second_bundle'),
        ),
        'by_bundle' => array(
          'type' => 'string',
          'bundles' => array('second_bundle' => 'second_bundle'),
        ),
      )
    );
    $this->setUpEntityManager();
    $this->cacheBackend->get('entity_field_map')->willReturn((object) array('data' => $expected));

    // Call the field map twice to make sure the static cache works.
    $this->assertEquals($expected, $this->entityManager->getFieldMap());
    $this->assertEquals($expected, $this->entityManager->getFieldMap());
  }

  /**
   * @covers ::getFieldMapByFieldType
   */
  public function testGetFieldMapByFieldType() {
    // Set up a content entity type.
    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_class = EntityManagerTestEntity::class;

    // Set up the module handler to return two bundles for the fieldable entity
    // type.
    $this->moduleHandler->getImplementations('entity_base_field_info')->willReturn([]);
    $this->moduleHandler->invokeAll('entity_bundle_info')->willReturn([
      'test_entity_type' => [
        'first_bundle' => [],
        'second_bundle' => [],
      ],
    ]);
    $this->moduleHandler->alter('entity_bundle_info', Argument::type('array'))->willReturn(NULL);

    // Define an ID field definition as a base field.
    $id_definition = $this->prophesize(FieldDefinitionInterface::class);
    $id_definition->getType()->willReturn('integer');
    $base_field_definitions = array(
      'id' => $id_definition->reveal(),
    );
    $entity_class::$baseFieldDefinitions = $base_field_definitions;

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->getAll()->willReturn([
      'test_entity_type' => [
        'by_bundle' => [
          'type' => 'string',
          'bundles' => ['second_bundle' => 'second_bundle'],
        ],
      ],
    ]);

    // Mock the base field definition override.
    $override_entity_type = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityManager(array(
      'test_entity_type' => $entity_type,
      'base_field_override' => $override_entity_type,
    ));

    $entity_type->getClass()->willReturn($entity_class);
    $entity_type->getKeys()->willReturn(['default_langcode' => 'default_langcode']);
    $entity_type->id()->willReturn('test_entity_type');
    $entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(TRUE);
    $entity_type->getBundleOf()->shouldBeCalled();
    $entity_type->isTranslatable()->shouldBeCalled();
    $entity_type->getProvider()->shouldBeCalled();

    $override_entity_type->getClass()->willReturn($entity_class);
    $override_entity_type->isSubclassOf(FieldableEntityInterface::class)->willReturn(FALSE);
    $override_entity_type->getHandlerClass('storage')->willReturn(TestConfigEntityStorage::class);
    $override_entity_type->getBundleOf()->shouldBeCalled();
    $override_entity_type->getLabel()->shouldBeCalled();

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
   * @covers ::onFieldDefinitionCreate
   */
  public function testOnFieldDefinitionCreateNewField() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');
    $field_definition->getType()->willReturn('test_type');

    $class = $this->getMockClass(DynamicallyFieldableEntityStorageInterface::class);
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    // The entity manager will instantiate a new object with the given class
    // name. Define the mock expectations on that.
    $storage = $this->entityManager->getStorage('test_entity_type');
    $storage->expects($this->once())
      ->method('onFieldDefinitionCreate')
      ->with($field_definition->reveal());

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([]);
    $key_value_store->set('test_entity_type', [
        'test_field' => [
          'type' => 'test_type',
          'bundles' => ['test_bundle' => 'test_bundle'],
        ],
      ])->shouldBeCalled();

    $this->entityManager->onFieldDefinitionCreate($field_definition->reveal());
  }

  /**
   * @covers ::onFieldDefinitionCreate
   */
  public function testOnFieldDefinitionCreateExistingField() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');

    $class = $this->getMockClass(DynamicallyFieldableEntityStorageInterface::class);
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    // The entity manager will instantiate a new object with the given class
    // name. Define the mock expectations on that.
    $storage = $this->entityManager->getStorage('test_entity_type');
    $storage->expects($this->once())
      ->method('onFieldDefinitionCreate')
      ->with($field_definition->reveal());

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([
        'test_field' => [
          'type' => 'test_type',
          'bundles' => ['existing_bundle' => 'existing_bundle'],
        ],
      ]);
    $key_value_store->set('test_entity_type', [
        'test_field' => [
          'type' => 'test_type',
          'bundles' => ['existing_bundle' => 'existing_bundle', 'test_bundle' => 'test_bundle'],
        ],
      ])
      ->shouldBeCalled();

    $this->entityManager->onFieldDefinitionCreate($field_definition->reveal());
  }

  /**
   * @covers ::onFieldDefinitionUpdate
   */
  public function testOnFieldDefinitionUpdate() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');

    $class = $this->getMockClass(DynamicallyFieldableEntityStorageInterface::class);
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    // The entity manager will instantiate a new object with the given class
    // name. Define the mock expectations on that.
    $storage = $this->entityManager->getStorage('test_entity_type');
    $storage->expects($this->once())
      ->method('onFieldDefinitionUpdate')
      ->with($field_definition->reveal());

    $this->entityManager->onFieldDefinitionUpdate($field_definition->reveal(), $field_definition->reveal());
  }

  /**
   * @covers ::onFieldDefinitionDelete
   */
  public function testOnFieldDefinitionDeleteMultipleBundles() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');

    $class = $this->getMockClass(DynamicallyFieldableEntityStorageInterface::class);
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    // The entity manager will instantiate a new object with the given class
    // name. Define the mock expectations on that.
    $storage = $this->entityManager->getStorage('test_entity_type');
    $storage->expects($this->once())
      ->method('onFieldDefinitionDelete')
      ->with($field_definition->reveal());

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([
        'test_field' => [
          'type' => 'test_type',
          'bundles' => ['test_bundle' => 'test_bundle'],
        ],
        'second_field' => [
          'type' => 'test_type',
          'bundles' => ['test_bundle' => 'test_bundle'],
        ],
      ]);
    $key_value_store->set('test_entity_type', [
        'second_field' => [
          'type' => 'test_type',
          'bundles' => ['test_bundle' => 'test_bundle'],
        ],
      ])
      ->shouldBeCalled();

    $this->entityManager->onFieldDefinitionDelete($field_definition->reveal());
  }


  /**
   * @covers ::onFieldDefinitionDelete
   */
  public function testOnFieldDefinitionDeleteSingleBundles() {
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getTargetEntityTypeId()->willReturn('test_entity_type');
    $field_definition->getTargetBundle()->willReturn('test_bundle');
    $field_definition->getName()->willReturn('test_field');

    $class = $this->getMockClass(DynamicallyFieldableEntityStorageInterface::class);
    $entity = $this->prophesize(EntityTypeInterface::class);
    $entity->getHandlerClass('storage')->willReturn($class);
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    // The entity manager will instantiate a new object with the given class
    // name. Define the mock expectations on that.
    $storage = $this->entityManager->getStorage('test_entity_type');
    $storage->expects($this->once())
      ->method('onFieldDefinitionDelete')
      ->with($field_definition->reveal());

    // Set up the stored bundle field map.
    $key_value_store = $this->prophesize(KeyValueStoreInterface::class);
    $this->keyValueFactory->get('entity.definitions.bundle_field_map')->willReturn($key_value_store->reveal());
    $key_value_store->get('test_entity_type')->willReturn([
        'test_field' => [
          'type' => 'test_type',
          'bundles' => ['test_bundle' => 'test_bundle', 'second_bundle' => 'second_bundle'],
        ],
      ]);
    $key_value_store->set('test_entity_type', [
        'test_field' => [
          'type' => 'test_type',
          'bundles' => ['second_bundle' => 'second_bundle'],
        ],
      ])
      ->shouldBeCalled();

    $this->entityManager->onFieldDefinitionDelete($field_definition->reveal());
  }

  /**
   * @covers ::getEntityTypeFromClass
   */
  public function testGetEntityTypeFromClass() {
    $apple = $this->prophesize(EntityTypeInterface::class);
    $banana = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $apple->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');

    $banana->getOriginalClass()->willReturn('\Drupal\banana\Entity\Banana');
    $banana->getClass()->willReturn('\Drupal\mango\Entity\Mango');
    $banana->id()
      ->willReturn('banana')
      ->shouldBeCalledTimes(2);

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
    $apple = $this->prophesize(EntityTypeInterface::class);
    $banana = $this->prophesize(EntityTypeInterface::class);

    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $apple->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $banana->getOriginalClass()->willReturn('\Drupal\banana\Entity\Banana');

    $this->entityManager->getEntityTypeFromClass('\Drupal\pear\Entity\Pear');
  }

  /**
   * @covers ::getEntityTypeFromClass
   *
   * @expectedException \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   * @expectedExceptionMessage Multiple entity types found for \Drupal\apple\Entity\Apple.
   */
  public function testGetEntityTypeFromClassAmbiguous() {
    $boskoop = $this->prophesize(EntityTypeInterface::class);
    $boskoop->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $boskoop->id()->willReturn('boskop');

    $gala = $this->prophesize(EntityTypeInterface::class);
    $gala->getOriginalClass()->willReturn('\Drupal\apple\Entity\Apple');
    $gala->id()->willReturn('gala');

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
    $apple = $this->prophesize(EntityTypeInterface::class);
    $apple->getRouteProviderClasses()->willReturn(['default' => TestRouteProvider::class]);

    $this->setUpEntityManager(array(
      'apple' => $apple,
    ));

    $apple_route_provider = $this->entityManager->getRouteProviders('apple');
    $this->assertInstanceOf(TestRouteProvider::class, $apple_route_provider['default']);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $apple_route_provider['default']);
    $this->assertAttributeInstanceOf(TranslationInterface::class, 'stringTranslation', $apple_route_provider['default']);
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestHandlerClass() {
    return get_class($this->getMockForAbstractClass(EntityHandlerBase::class));
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
