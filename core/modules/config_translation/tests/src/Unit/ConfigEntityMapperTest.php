<?php

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests the functionality provided by the configuration entity mapper.
 *
 * @group config_translation
 */
class ConfigEntityMapperTest extends UnitTestCase {

  /**
   * The configuration entity mapper to test.
   *
   * @var \Drupal\config_translation\ConfigEntityMapper
   */
  protected $configEntityMapper;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The entity instance used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The route provider used for testing.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface $language_manager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $this->entity = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityInterface');

    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');

    $this->routeProvider
      ->expects($this->any())
      ->method('getRouteByName')
      ->with('entity.configurable_language.edit_form')
      ->will($this->returnValue(new Route('/admin/config/regional/language/edit/{configurable_language}')));

    $definition = [
      'class' => '\Drupal\config_translation\ConfigEntityMapper',
      'base_route_name' => 'entity.configurable_language.edit_form',
      'title' => '@label language',
      'names' => [],
      'entity_type' => 'configurable_language',
      'route_name' => 'config_translation.item.overview.entity.configurable_language.edit_form',
    ];

    $typed_config_manager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $locale_config_manager = $this->getMockBuilder('Drupal\locale\LocaleConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $this->configEntityMapper = new ConfigEntityMapper(
      'configurable_language',
      $definition,
      $this->getConfigFactoryStub(),
      $typed_config_manager,
      $locale_config_manager,
      $this->getMock('Drupal\config_translation\ConfigMapperManagerInterface'),
      $this->routeProvider,
      $this->getStringTranslationStub(),
      $this->entityManager,
      $this->languageManager
    );
  }

  /**
   * Tests ConfigEntityMapper::setEntity() and ConfigEntityMapper::getEntity().
   */
  public function testEntityGetterAndSetter() {
    $this->entity
      ->expects($this->once())
      ->method('id')
      ->with()
      ->will($this->returnValue('entity_id'));

    $entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->will($this->returnValue('config_prefix'));
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->will($this->returnValue($entity_type));

    // No entity is set.
    $this->assertNull($this->configEntityMapper->getEntity());

    $result = $this->configEntityMapper->setEntity($this->entity);
    $this->assertTrue($result);

    // Ensure that the getter provides the entity.
    $this->assertEquals($this->entity, $this->configEntityMapper->getEntity());

    // Ensure that the configuration name was added to the mapper.
    $plugin_definition = $this->configEntityMapper->getPluginDefinition();
    $this->assertTrue(in_array('config_prefix.entity_id', $plugin_definition['names']));

    // Make sure setEntity() returns FALSE when called a second time.
    $result = $this->configEntityMapper->setEntity($this->entity);
    $this->assertFalse($result);
  }

  /**
   * Tests ConfigEntityMapper::getOverviewRouteParameters().
   */
  public function testGetOverviewRouteParameters() {
    $entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->will($this->returnValue($entity_type));
    $this->configEntityMapper->setEntity($this->entity);

    $this->entity
      ->expects($this->once())
      ->method('id')
      ->with()
      ->will($this->returnValue('entity_id'));

    $result = $this->configEntityMapper->getOverviewRouteParameters();

    $this->assertSame(['configurable_language' => 'entity_id'], $result);
  }

  /**
   * Tests ConfigEntityMapper::getType().
   */
  public function testGetType() {
    $result = $this->configEntityMapper->getType();
    $this->assertSame('configurable_language', $result);
  }

  /**
   * Tests ConfigEntityMapper::getTypeName().
   */
  public function testGetTypeName() {
    $entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('test'));
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->will($this->returnValue($entity_type));

    $result = $this->configEntityMapper->getTypeName();
    $this->assertSame('test', $result);
  }

  /**
   * Tests ConfigEntityMapper::getTypeLabel().
   */
  public function testGetTypeLabel() {
    $entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('test'));
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('configurable_language')
      ->will($this->returnValue($entity_type));

    $result = $this->configEntityMapper->getTypeLabel();
    $this->assertSame('test', $result);
  }

  /**
   * Tests ConfigEntityMapper::getOperations().
   */
  public function testGetOperations() {
    $result = $this->configEntityMapper->getOperations();

    $expected = [
      'list' => [
        'title' => 'List',
        'url' => Url::fromRoute('config_translation.entity_list', ['mapper_id' => 'configurable_language']),
      ],
    ];

    $this->assertEquals($expected, $result);
  }

}
