<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityManagerTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityControllerBase;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the \Drupal\Core\Entity\EntityManager class.
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityManager
 *
 * @group Drupal
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
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The string translationManager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Entity Manager test',
      'description' => 'Unit test the entity manager.',
      'group' => 'Entity',
    );
  }

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

    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->languageManager = $this->getMockBuilder('Drupal\Core\Language\LanguageManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue((object) array('id' => 'en')));

    $this->translationManager = $this->getStringTranslationStub();

    $this->formBuilder = $this->getMock('Drupal\Core\Form\FormBuilderInterface');

    $this->container = $this->getContainerWithCacheBins($this->cache);

    $this->discovery = $this->getMock('Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface');
  }

  /**
   * Sets up the entity manager to be tested.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[]|\PHPUnit_Framework_MockObject_MockObject[] $definitions
   *   (optional) An array of entity type definitions.
   */
  protected function setUpEntityManager($definitions = array()) {
    $class = $this->getMockClass('Drupal\Core\Entity\EntityInterface');
    $definitions_map = array();
    foreach ($definitions as $entity_type_id => $entity_type) {
      $entity_type->expects($this->any())
        ->method('getClass')
        ->will($this->returnValue($class));
      $definitions_map[] = array($entity_type_id, $entity_type);
    }
    $this->discovery->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValueMap($definitions_map));
    $this->discovery->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue($definitions));

    $this->entityManager = new TestEntityManager(new \ArrayObject(), $this->container, $this->moduleHandler, $this->cache, $this->languageManager, $this->translationManager, $this->formBuilder);
    $this->entityManager->setDiscovery($this->discovery);
  }

  /**
   * Tests the clearCachedDefinitions() method.
   *
   * @covers ::clearCachedDefinitions()
   *
   */
  public function testClearCachedDefinitions() {
    $this->setUpEntityManager();
    $this->discovery->expects($this->once())
      ->method('clearCachedDefinitions');

    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Tests the getDefinition() method.
   *
   * @covers ::getDefinition()
   *
   * @dataProvider providerTestGetDefinition
   */
  public function testGetDefinition($entity_type_id, $expected) {
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $this->setUpEntityManager(array(
      'apple' => $entity,
      'banana' => $entity,
    ));

    $entity_type = $this->entityManager->getDefinition($entity_type_id);
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
   * @covers ::getDefinition()
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage The "pear" entity type does not exist.
   */
  public function testGetDefinitionInvalidException() {
    $this->setUpEntityManager();

    $this->entityManager->getDefinition('pear', TRUE);
  }

  /**
   * Tests the hasController() method.
   *
   * @covers ::hasController()
   *
   * @dataProvider providerTestHasController
   */
  public function testHasController($entity_type_id, $expected) {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->any())
      ->method('hasControllerClass')
      ->with('storage')
      ->will($this->returnValue(TRUE));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->any())
      ->method('hasControllerClass')
      ->with('storage')
      ->will($this->returnValue(FALSE));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $entity_type = $this->entityManager->hasController($entity_type_id, 'storage');
    $this->assertSame($expected, $entity_type);
  }

  /**
   * Provides test data for testHasController().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHasController() {
    return array(
      array('apple', TRUE),
      array('banana', FALSE),
      array('pear', FALSE),
    );
  }

  /**
   * Tests the getStorageController() method.
   *
   * @covers ::getStorageController()
   */
  public function testGetStorageController() {
    $class = $this->getTestControllerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getStorageClass')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getStorageController('test_entity_type'));
  }

  /**
   * Tests the getListController() method.
   *
   * @covers ::getListController()
   */
  public function testGetListController() {
    $class = $this->getTestControllerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getListClass')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getListController('test_entity_type'));
  }

  /**
   * Tests the getViewBuilder() method.
   *
   * @covers ::getViewBuilder()
   */
  public function testGetViewBuilder() {
    $class = $this->getTestControllerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getViewBuilderClass')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getViewBuilder('test_entity_type'));
  }

  /**
   * Tests the getAccessController() method.
   *
   * @covers ::getAccessController()
   */
  public function testGetAccessController() {
    $class = $this->getTestControllerClass();
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getAccessClass')
      ->will($this->returnValue($class));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->assertInstanceOf($class, $this->entityManager->getAccessController('test_entity_type'));
  }

  /**
   * Tests the getFormController() method.
   *
   * @covers ::getFormController()
   */
  public function testGetFormController() {
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

    $apple_form = $this->entityManager->getFormController('apple', 'default');
    $this->assertInstanceOf('Drupal\Tests\Core\Entity\TestEntityForm', $apple_form);
    $this->assertAttributeInstanceOf('Drupal\Core\Extension\ModuleHandlerInterface', 'moduleHandler', $apple_form);
    $this->assertAttributeInstanceOf('Drupal\Core\StringTranslation\TranslationInterface', 'translationManager', $apple_form);

    $banana_form = $this->entityManager->getFormController('banana', 'default');
    $this->assertInstanceOf('Drupal\Tests\Core\Entity\TestEntityFormInjected', $banana_form);
    $this->assertAttributeEquals('yellow', 'color', $banana_form);

  }

  /**
   * Tests the getFormController() method with an invalid operation.
   *
   * @covers ::getFormController()
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetFormControllerInvalidOperation() {
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getFormClass')
      ->with('edit')
      ->will($this->returnValue(''));
    $this->setUpEntityManager(array('test_entity_type' => $entity));

    $this->entityManager->getFormController('test_entity_type', 'edit');
  }

  /**
   * Tests the getController() method.
   *
   * @covers ::getController()
   */
  public function testGetController() {
    $class = $this->getTestControllerClass();
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $apple->expects($this->once())
      ->method('getControllerClass')
      ->with('storage')
      ->will($this->returnValue($class));
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getStorageClass')
      ->will($this->returnValue('Drupal\Tests\Core\Entity\TestEntityControllerInjected'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $apple_controller = $this->entityManager->getController('apple', 'storage');
    $this->assertInstanceOf($class, $apple_controller);
    $this->assertAttributeInstanceOf('Drupal\Core\Extension\ModuleHandlerInterface', 'moduleHandler', $apple_controller);
    $this->assertAttributeInstanceOf('Drupal\Core\StringTranslation\TranslationInterface', 'translationManager', $apple_controller);

    $banana_controller = $this->entityManager->getController('banana', 'storage', 'getStorageClass');
    $this->assertInstanceOf('Drupal\Tests\Core\Entity\TestEntityControllerInjected', $banana_controller);
    $this->assertAttributeEquals('yellow', 'color', $banana_controller);
  }

  /**
   * Tests the getController() method when no controller is defined.
   *
   * @covers ::getController()
   *
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testGetControllerMissingController() {
    $entity = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity->expects($this->once())
      ->method('getControllerClass')
      ->with('storage')
      ->will($this->returnValue(''));
    $this->setUpEntityManager(array('test_entity_type' => $entity));
    $this->entityManager->getController('test_entity_type', 'storage');
  }

  /**
   * Tests the getAdminRouteInfo() method.
   *
   * @covers ::getAdminRouteInfo()
   */
  public function testGetAdminRouteInfo() {
    $apple = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $banana->expects($this->once())
      ->method('getBundleEntityType')
      ->will($this->returnValue('bundle'));
    $banana->expects($this->once())
      ->method('getLinkTemplate')
      ->with('admin-form')
      ->will($this->returnValue('entity.view'));
    $this->setUpEntityManager(array(
      'apple' => $apple,
      'banana' => $banana,
    ));

    $expected = array(
      'route_name' => 'entity.view',
      'route_parameters' => array(
        'bundle' => 'chiquita',
      ),
    );
    $this->assertSame($expected, $this->entityManager->getAdminRouteInfo('banana', 'chiquita'));
    $this->assertNull($this->entityManager->getAdminRouteInfo('apple', 'delicious'));
  }

  /**
   * Tests the getBaseFieldDefinitions() method.
   *
   * @covers ::getBaseFieldDefinitions()
   */
  public function testGetBaseFieldDefinitions() {
    $field_definition = $this->setUpEntityWithFieldDefinition();

    $expected = array('id' => $field_definition);
    $this->assertSame($expected, $this->entityManager->getBaseFieldDefinitions('test_entity_type'));
  }

  /**
   * Tests the getFieldDefinitions() method.
   *
   * @covers ::getFieldDefinitions()
   */
  public function testGetFieldDefinitions() {
    $field_definition = $this->setUpEntityWithFieldDefinition();

    $expected = array('id' => $field_definition);
    $this->assertSame($expected, $this->entityManager->getFieldDefinitions('test_entity_type', 'test_entity_bundle'));
  }

  /**
   * Tests the getBaseFieldDefinitions() method with caching.
   *
   * @covers ::getBaseFieldDefinitions()
   */
  public function testGetBaseFieldDefinitionsWithCaching() {
    $field_definition = $this->setUpEntityWithFieldDefinition();

    $expected = array('id' => $field_definition);

    $this->cache->expects($this->at(0))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cache->expects($this->once())
      ->method('set');
    $this->cache->expects($this->at(2))
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
   * @covers ::getFieldDefinitions()
   */
  public function testGetFieldDefinitionsWithCaching() {
    $field_definition = $this->setUpEntityWithFieldDefinition(FALSE, 'id', 0);

    $expected = array('id' => $field_definition);

    $this->cache->expects($this->at(0))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));
    $this->cache->expects($this->at(1))
      ->method('get')
      ->with('entity_bundle_field_definitions:test_entity_type:test_bundle:en', FALSE)
      ->will($this->returnValue(FALSE));
    $this->cache->expects($this->at(2))
      ->method('set');
    $this->cache->expects($this->at(3))
      ->method('get')
      ->with('entity_base_field_definitions:test_entity_type:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));
    $this->cache->expects($this->at(4))
      ->method('get')
      ->with('entity_bundle_field_definitions:test_entity_type:test_bundle:en', FALSE)
      ->will($this->returnValue((object) array('data' => $expected)));

    $this->assertSame($expected, $this->entityManager->getFieldDefinitions('test_entity_type', 'test_bundle'));
    $this->entityManager->testClearEntityFieldInfo();
    $this->assertSame($expected, $this->entityManager->getFieldDefinitions('test_entity_type', 'test_bundle'));
  }

  /**
   * Tests the getBaseFieldDefinitions() method with an invalid definition.
   *
   * @covers ::getBaseFieldDefinitions()
   *
   * @expectedException \LogicException
   */
  public function testGetBaseFieldDefinitionsInvalidDefinition() {
    $langcode_definition = $this->setUpEntityWithFieldDefinition(FALSE, 'langcode');
    $langcode_definition->expects($this->once())
      ->method('isTranslatable')
      ->will($this->returnValue(TRUE));

    $this->entityManager->getBaseFieldDefinitions('test_entity_type');
  }

  /**
   * Prepares an entity that defines a field definition.
   *
   * @param bool $custom_invoke_all
   *   (optional) Whether the test will set up its own
   *   ModuleHandlerInterface::invokeAll() implementation. Defaults to FALSE.
   * @param string $field_definition_id
   *   (optional) The ID to use for the field definition. Defaults to 'id'.
   *
   * @return \Drupal\Core\Field\FieldDefinition|\PHPUnit_Framework_MockObject_MockObject
   *   A field definition object.
   */
  protected function setUpEntityWithFieldDefinition($custom_invoke_all = FALSE, $field_definition_id = 'id', $base_field_definition_calls = 1) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity = $this->getMock('Drupal\Tests\Core\Entity\TestContentEntityInterface');
    $entity_class = get_class($entity);

    $entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue($entity_class));
    $entity_type->expects($this->any())
      ->method('getKeys')
      ->will($this->returnValue(array()));
    $field_definition = $this->getMockBuilder('Drupal\Core\Field\FieldDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_class::staticExpects($this->exactly($base_field_definition_calls))
      ->method('baseFieldDefinitions')
      ->will($this->returnValue(array(
        $field_definition_id => $field_definition,
      )));
    $entity_class::staticExpects($this->any())
      ->method('bundleFieldDefinitions')
      ->will($this->returnValue(array()));

    $this->moduleHandler->expects($this->any())
      ->method('alter');
    if (!$custom_invoke_all) {
      $this->moduleHandler->expects($this->any())
        ->method('invokeAll')
        ->will($this->returnValue(array()));
    }

    $this->setUpEntityManager(array('test_entity_type' => $entity_type));

    return $field_definition;
  }

  /**
   * Tests the clearCachedFieldDefinitions() method.
   *
   * @covers ::clearCachedFieldDefinitions()
   */
  public function testClearCachedFieldDefinitions() {
    $this->setUpEntityManager();
    $this->cache->expects($this->once())
      ->method('deleteTags')
      ->with(array('entity_field_info' => TRUE));

    $this->entityManager->clearCachedFieldDefinitions();
  }

  /**
   * Tests the getBundleInfo() method.
   *
   * @covers ::getBundleInfo()
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
   * @covers ::getAllBundleInfo()
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
    $this->cache->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue(FALSE));
    $this->cache->expects($this->at(2))
      ->method('get')
      ->will($this->returnValue((object) array('data' => 'cached data')));
    $this->cache->expects($this->once())
      ->method('set');

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
   * @covers ::getEntityTypeLabels()
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
   * @covers ::getTranslationFromContext()
   */
  public function testGetTranslationFromContext() {
    $this->setUpEntityManager();

    $this->languageManager->expects($this->exactly(2))
      ->method('getFallbackCandidates')
      ->will($this->returnCallback(function ($langcode = NULL, array $context = array()) {
        $candidates = array();
        if ($langcode) {
          $candidates[$langcode] = $langcode;
        }
        return $candidates;
      }));

    $entity = $this->getMock('Drupal\Tests\Core\Entity\TestContentEntityInterface');
    $entity->expects($this->exactly(2))
      ->method('getUntranslated')
      ->will($this->returnValue($entity));
    $entity->expects($this->exactly(2))
      ->method('language')
      ->will($this->returnValue((object) array('id' => 'en')));
    $entity->expects($this->exactly(2))
      ->method('hasTranslation')
      ->will($this->returnValueMap(array(
        array(Language::LANGCODE_DEFAULT, FALSE),
        array('custom_langcode', TRUE),
      )));

    $translated_entity = $this->getMock('Drupal\Tests\Core\Entity\TestContentEntityInterface');
    $entity->expects($this->once())
      ->method('getTranslation')
      ->with('custom_langcode')
      ->will($this->returnValue($translated_entity));

    $this->assertSame($entity, $this->entityManager->getTranslationFromContext($entity));
    $this->assertSame($translated_entity, $this->entityManager->getTranslationFromContext($entity, 'custom_langcode'));
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestControllerClass() {
    return get_class($this->getMockForAbstractClass('Drupal\Core\Entity\EntityControllerBase'));
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
   * Allows the $entityFieldInfo property to be cleared.
   */
  public function testClearEntityFieldInfo() {
    $this->baseFieldDefinitions = array();
    $this->fieldDefinitions = array();
  }

}

/**
 * Provides a test entity controller that uses injection.
 */
class TestEntityControllerInjected implements EntityControllerInterface {

  /**
   * The color of the entity type.
   *
   * @var string
   */
  protected $color;

  /**
   * Constructs a new TestEntityControllerInjected.
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
class TestEntityForm extends EntityControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return 'the_base_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
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

if (!defined('DRUPAL_ROOT')) {
  define('DRUPAL_ROOT', dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)))));
}
