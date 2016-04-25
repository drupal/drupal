<?php

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigFieldMapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the functionality provided by the configuration field mapper.
 *
 * @group config_translation
 *
 * @coversDefaultClass \Drupal\config_translation\ConfigFieldMapper
 */
class ConfigFieldMapperTest extends UnitTestCase {

  /**
   * The configuration field mapper to test.
   *
   * @var \Drupal\config_translation\ConfigFieldMapper
   */
  protected $configFieldMapper;

  /**
   * The field config instance used for testing.
   *
   * @var \Drupal\field\FieldConfigInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entity = $this->getMock('Drupal\field\FieldConfigInterface');

    $definition = array(
      'class' => '\Drupal\config_translation\ConfigFieldMapper',
      'base_route_name' => 'entity.field_config.node_field_edit_form',
      'title' => '@label field',
      'names' => array(),
      'entity_type' => 'field_config',
    );

    $locale_config_manager = $this->getMockBuilder('Drupal\locale\LocaleConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->configFieldMapper = new ConfigFieldMapper(
      'node_fields',
      $definition,
      $this->getConfigFactoryStub(),
      $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface'),
      $locale_config_manager,
      $this->getMock('Drupal\config_translation\ConfigMapperManagerInterface'),
      $this->getMock('Drupal\Core\Routing\RouteProviderInterface'),
      $this->getStringTranslationStub(),
      $this->entityManager,
      $this->getMock('Drupal\Core\Language\LanguageManagerInterface')
    );
  }

  /**
   * Tests ConfigFieldMapper::setEntity().
   *
   * @covers ::setEntity
   */
  public function testSetEntity() {
    $entity_type = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->will($this->returnValue('config_prefix'));

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($entity_type));

    $field_storage = $this->getMock('Drupal\field\FieldStorageConfigInterface');
    $field_storage
      ->expects($this->any())
      ->method('id')
      ->will($this->returnValue('field_storage_id'));

    $this->entity
      ->expects($this->any())
      ->method('getFieldStorageDefinition')
      ->will($this->returnValue($field_storage));

    $result = $this->configFieldMapper->setEntity($this->entity);
    $this->assertTrue($result);

    // Ensure that the configuration name was added to the mapper.
    $plugin_definition = $this->configFieldMapper->getPluginDefinition();
    $this->assertTrue(in_array('config_prefix.field_storage_id', $plugin_definition['names']));

    // Make sure setEntity() returns FALSE when called a second time.
    $result = $this->configFieldMapper->setEntity($this->entity);
    $this->assertFalse($result);
  }

}
