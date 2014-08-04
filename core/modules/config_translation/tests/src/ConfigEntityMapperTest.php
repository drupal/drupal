<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigEntityMapperTest.
 */

namespace Drupal\config_translation\Tests;

use Drupal\config_translation\ConfigEntityMapper;
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

  public function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $this->entity = $this->getMock('Drupal\Core\Entity\EntityInterface');

    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');

    $this->routeProvider
      ->expects($this->any())
      ->method('getRouteByName')
      ->with('entity.language_entity.edit_form')
      ->will($this->returnValue(new Route('/admin/config/regional/language/edit/{language_entity}')));

    $definition = array(
      'class' => '\Drupal\config_translation\ConfigEntityMapper',
      'base_route_name' => 'entity.language_entity.edit_form',
      'title' => '!label language',
      'names' => array(),
      'entity_type' => 'language_entity',
      'route_name' => 'config_translation.item.overview.entity.language_entity.edit_form',
    );

    $typed_config_manager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $locale_config_manager = $this->getMockBuilder('Drupal\locale\LocaleConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->configEntityMapper = new ConfigEntityMapper(
      'language_entity',
      $definition,
      $this->getConfigFactoryStub(),
      $typed_config_manager,
      $locale_config_manager,
      $this->getMock('Drupal\config_translation\ConfigMapperManagerInterface'),
      $this->routeProvider,
      $this->getStringTranslationStub(),
      $this->entityManager
    );
  }

  /**
   * Tests ConfigEntityMapper::setEntity().
   */
  public function testSetEntity() {
    $this->entity
      ->expects($this->once())
      ->method('id')
      ->with()
      ->will($this->returnValue('entity_id'));

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('language_entity')
      ->will($this->returnValue($entity_type));

    $result = $this->configEntityMapper->setEntity($this->entity);
    $this->assertTrue($result);

    // Make sure setEntity() returns FALSE when called a second time.
    $result = $this->configEntityMapper->setEntity($this->entity);
    $this->assertFalse($result);
  }

  /**
   * Tests ConfigEntityMapper::getOverviewRouteParameters().
   */
  public function testGetOverviewRouteParameters() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('language_entity')
      ->will($this->returnValue($entity_type));
    $this->configEntityMapper->setEntity($this->entity);

    $this->entity
      ->expects($this->once())
      ->method('id')
      ->with()
      ->will($this->returnValue('entity_id'));

    $result = $this->configEntityMapper->getOverviewRouteParameters();

    $this->assertSame(array('language_entity' => 'entity_id'), $result);
  }

  /**
   * Tests ConfigEntityMapper::getType().
   */
  public function testGetType() {
    $result = $this->configEntityMapper->getType();
    $this->assertSame('language_entity', $result);
  }

  /**
   * Tests ConfigEntityMapper::getTypeName().
   */
  public function testGetTypeName() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('test'));
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('language_entity')
      ->will($this->returnValue($entity_type));

    $result = $this->configEntityMapper->getTypeName();
    $this->assertSame('test', $result);
  }

  /**
   * Tests ConfigEntityMapper::getTypeLabel().
   */
  public function testGetTypeLabel() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->once())
      ->method('getLabel')
      ->will($this->returnValue('test'));
    $this->entityManager
      ->expects($this->once())
      ->method('getDefinition')
      ->with('language_entity')
      ->will($this->returnValue($entity_type));

    $result = $this->configEntityMapper->getTypeLabel();
    $this->assertSame('test', $result);
  }

  /**
   * Tests ConfigEntityMapper::getOperations().
   */
  public function testGetOperations() {
    $result = $this->configEntityMapper->getOperations();

    $expected = array(
      'list' => array(
        'title' => 'List',
        'href' => 'admin/config/regional/config-translation/language_entity',
      )
    );

    $this->assertSame($expected, $result);
  }

}
