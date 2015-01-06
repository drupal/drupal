<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigEntityBaseUnitTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Language\Language;
use Drupal\Tests\Core\Plugin\Fixtures\TestConfigurablePlugin;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityBase
 * @group Config
 */
class ConfigEntityBaseUnitTest extends UnitTestCase {

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * The provider of the entity type.
   *
   * @var string
   */
  protected $provider;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * The mocked typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->id = $this->randomMachineName();
    $values = array(
      'id' => $this->id,
      'langcode' => 'en',
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
    );
    $this->entityTypeId = $this->randomMachineName();
    $this->provider = $this->randomMachineName();
    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue($this->provider));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->languageManager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('en')
      ->will($this->returnValue(new Language(array('id' => 'en'))));

    $this->cacheTagsInvalidator = $this->getMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $this->typedConfigManager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('language_manager', $this->languageManager);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    $container->set('config.typed', $this->typedConfigManager);
    \Drupal::setContainer($container);

    $this->entity = $this->getMockForAbstractClass('\Drupal\Core\Config\Entity\ConfigEntityBase', array($values, $this->entityTypeId));
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    // Calculating dependencies will reset the dependencies array.
    $this->entity->set('dependencies', array('module' => array('node')));
    $this->assertEmpty($this->entity->calculateDependencies());

    // Calculating dependencies will reset the dependencies array using enforced
    // dependencies.
    $this->entity->set('dependencies', array('module' => array('node'), 'enforced' => array('module' => 'views')));
    $dependencies = $this->entity->calculateDependencies();
    $this->assertContains('views', $dependencies['module']);
    $this->assertNotContains('node', $dependencies['module']);
    $this->assertContains('views', $dependencies['enforced']['module']);
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveDuringSync() {
    $query = $this->getMock('\Drupal\Core\Entity\Query\QueryInterface');
    $storage = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');

    $query->expects($this->any())
      ->method('execute')
      ->will($this->returnValue(array()));
    $query->expects($this->any())
      ->method('condition')
      ->will($this->returnValue($query));
    $storage->expects($this->any())
      ->method('getQuery')
      ->will($this->returnValue($query));
    $storage->expects($this->any())
      ->method('loadUnchanged')
      ->will($this->returnValue($this->entity));

    // Saving an entity will not reset the dependencies array during config
    // synchronization.
    $this->entity->set('dependencies', array('module' => array('node')));
    $this->entity->preSave($storage);
    $this->assertEmpty($this->entity->getDependencies());

    $this->entity->setSyncing(TRUE);
    $this->entity->set('dependencies', array('module' => array('node')));
    $this->entity->preSave($storage);
    $dependencies = $this->entity->getDependencies();
    $this->assertContains('node', $dependencies['module']);
  }

  /**
   * @covers ::addDependency
   */
  public function testAddDependency() {
    $method = new \ReflectionMethod('\Drupal\Core\Config\Entity\ConfigEntityBase', 'addDependency');
    $method->setAccessible(TRUE);
    $method->invoke($this->entity, 'module', $this->provider);
    $method->invoke($this->entity, 'module', 'core');
    $method->invoke($this->entity, 'module', 'node');
    $dependencies = $this->entity->getDependencies();
    $this->assertNotContains($this->provider, $dependencies['module']);
    $this->assertNotContains('core', $dependencies['module']);
    $this->assertContains('node', $dependencies['module']);

    // Test sorting of dependencies.
    $method->invoke($this->entity, 'module', 'action');
    $dependencies = $this->entity->getDependencies();
    $this->assertEquals(array('action', 'node'), $dependencies['module']);

    // Test sorting of dependency types.
    $method->invoke($this->entity, 'entity', 'system.action.id');
    $dependencies = $this->entity->getDependencies();
    $this->assertEquals(array('entity', 'module'), array_keys($dependencies));
  }

  /**
   * @covers ::calculateDependencies
   *
   * @dataProvider providerCalculateDependenciesWithPluginCollections
   */
  public function testCalculateDependenciesWithPluginCollections($definition, $expected_dependencies) {
    $values = array();
    $this->entity = $this->getMockBuilder('\Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginCollections')
      ->setConstructorArgs(array($values, $this->entityTypeId))
      ->setMethods(array('getPluginCollections'))
      ->getMock();

    // Create a configurable plugin that would add a dependency.
    $instance_id = $this->randomMachineName();
    $instance = new TestConfigurablePlugin(array(), $instance_id, $definition);

    // Create a plugin collection to contain the instance.
    $pluginCollection = $this->getMockBuilder('\Drupal\Core\Plugin\DefaultLazyPluginCollection')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();
    $pluginCollection->expects($this->atLeastOnce())
      ->method('get')
      ->with($instance_id)
      ->will($this->returnValue($instance));
    $pluginCollection->addInstanceId($instance_id);

    // Return the mocked plugin collection.
    $this->entity->expects($this->once())
      ->method('getPluginCollections')
      ->will($this->returnValue(array($pluginCollection)));

    $this->assertEquals($expected_dependencies, $this->entity->calculateDependencies());
  }

  /**
   * Data provider for testCalculateDependenciesWithPluginCollections.
   *
   * @return array
   */
  public function providerCalculateDependenciesWithPluginCollections() {
    // Start with 'a' so that order of the dependency array is fixed.
    $instance_dependency_1 = 'a' . $this->randomMachineName(10);
    $instance_dependency_2 = 'a' . $this->randomMachineName(11);

    return array(
      // Tests that the plugin provider is a module dependency.
      array(
        array('provider' => 'test'),
        array('module' => array('test')),
      ),
      // Tests that a plugin that is provided by the same module as the config
      // entity is not added to the dependencies array.
      array(
        array('provider' => $this->provider),
        array('module' => array(NULL)),
      ),
      // Tests that a config entity that has a plugin which provides config
      // dependencies in its definition has them.
      array(
        array(
          'provider' => 'test',
          'config_dependencies' => array(
            'config' => array($instance_dependency_1),
            'module' => array($instance_dependency_2),
          )
        ),
        array(
          'config' => array($instance_dependency_1),
          'module' => array($instance_dependency_2, 'test')
        )
      )
    );
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithThirdPartySettings() {
    $this->entity = $this->getMockForAbstractClass('\Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithThirdPartySettings', array(array(), $this->entityTypeId));
    $this->entity->setThirdPartySetting('test_provider', 'test', 'test');
    $this->entity->setThirdPartySetting('test_provider2', 'test', 'test');
    $this->entity->setThirdPartySetting($this->provider, 'test', 'test');

    $this->assertEquals(array('test_provider', 'test_provider2'), $this->entity->calculateDependencies()['module']);
  }

  /**
   * @covers ::setOriginalId
   * @covers ::getOriginalId
   */
  public function testGetOriginalId() {
    $new_id = $this->randomMachineName();
    $this->entity->set('id', $new_id);
    $this->assertSame($this->id, $this->entity->getOriginalId());
    $this->assertSame($this->entity, $this->entity->setOriginalId($new_id));
    $this->assertSame($new_id, $this->entity->getOriginalId());
  }

  /**
   * @covers ::isNew
   */
  public function testIsNew() {
    $this->assertFalse($this->entity->isNew());
    $this->assertSame($this->entity, $this->entity->enforceIsNew());
    $this->assertTrue($this->entity->isNew());
    $this->entity->enforceIsNew(FALSE);
    $this->assertFalse($this->entity->isNew());
  }

  /**
   * @covers ::set
   * @covers ::get
   */
  public function testGet() {
    $name = 'id';
    $value = $this->randomMachineName();
    $this->assertSame($this->id, $this->entity->get($name));
    $this->assertSame($this->entity, $this->entity->set($name, $value));
    $this->assertSame($value, $this->entity->get($name));
  }

  /**
   * @covers ::setStatus
   * @covers ::status
   */
  public function testSetStatus() {
    $this->assertTrue($this->entity->status());
    $this->assertSame($this->entity, $this->entity->setStatus(FALSE));
    $this->assertFalse($this->entity->status());
    $this->entity->setStatus(TRUE);
    $this->assertTrue($this->entity->status());
  }

  /**
   * @covers ::enable
   * @depends testSetStatus
   */
  public function testEnable() {
    $this->entity->setStatus(FALSE);
    $this->assertSame($this->entity, $this->entity->enable());
    $this->assertTrue($this->entity->status());
  }

  /**
   * @covers ::disable
   * @depends testSetStatus
   */
  public function testDisable() {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(array($this->entityTypeId . ':' . $this->id));

    $this->entity->setStatus(TRUE);
    $this->assertSame($this->entity, $this->entity->disable());
    $this->assertFalse($this->entity->status());
  }

  /**
   * @covers ::setSyncing
   * @covers ::isSyncing
   */
  public function testIsSyncing() {
    $this->assertFalse($this->entity->isSyncing());
    $this->assertSame($this->entity, $this->entity->setSyncing(TRUE));
    $this->assertTrue($this->entity->isSyncing());
    $this->entity->setSyncing(FALSE);
    $this->assertFalse($this->entity->isSyncing());
  }

  /**
   * @covers ::createDuplicate
   */
  public function testCreateDuplicate() {
    $this->entityType->expects($this->at(0))
      ->method('getKey')
      ->with('id')
      ->will($this->returnValue('id'));

    $this->entityType->expects($this->at(1))
      ->method('hasKey')
      ->with('uuid')
      ->will($this->returnValue(TRUE));

    $this->entityType->expects($this->at(2))
      ->method('getKey')
      ->with('uuid')
      ->will($this->returnValue('uuid'));

    $new_uuid = '8607ef21-42bc-4913-978f-8c06207b0395';
    $this->uuid->expects($this->once())
      ->method('generate')
      ->will($this->returnValue($new_uuid));

    $duplicate = $this->entity->createDuplicate();
    $this->assertInstanceOf('\Drupal\Core\Entity\Entity', $duplicate);
    $this->assertNotSame($this->entity, $duplicate);
    $this->assertFalse($this->entity->isNew());
    $this->assertTrue($duplicate->isNew());
    $this->assertNull($duplicate->id());
    $this->assertNull($duplicate->getOriginalId());
    $this->assertNotEquals($this->entity->uuid(), $duplicate->uuid());
    $this->assertSame($new_uuid, $duplicate->uuid());
  }

  /**
   * @covers ::sort
   */
  public function testSort() {
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue(array(
        'entity_keys' => array(
          'label' => 'label',
        ),
      )));
    $entity_a = $this->entity;
    $entity_a->label = 'foo';
    $entity_b = clone $this->entity;
    $entity_b->label = 'bar';
    $list = array($entity_a, $entity_b);
    // Suppress errors because of https://bugs.php.net/bug.php?id=50688.
    @usort($list, '\Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    $this->assertSame($entity_b, $list[0]);
    $entity_a->weight = 0;
    $entity_b->weight = 1;
    // Suppress errors because of https://bugs.php.net/bug.php?id=50688.
    @usort($list, array($entity_a, 'sort'));
    $this->assertSame($entity_a, $list[0]);
  }

  /**
   * @covers ::toArray
   */
  public function testToArray() {
    $this->typedConfigManager->expects($this->once())
      ->method('getDefinition')
      ->will($this->returnValue(array('mapping' => array('id' => '', 'dependencies' => ''))));
    $properties = $this->entity->toArray();
    $this->assertInternalType('array', $properties);
    $this->assertEquals(array('id' => $this->entity->id(), 'dependencies' => array()), $properties);
  }

  /**
   * @covers ::toArray
   *
   * @expectedException \Drupal\Core\Config\Schema\SchemaIncompleteException
   */
  public function testToArrayFallback() {
    $this->entity->toArray();
  }

}
