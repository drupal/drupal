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
   * @var \Drupal\field\FieldConfigInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entity;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entity = $this->createMock('Drupal\field\FieldConfigInterface');

    $definition = [
      'class' => '\Drupal\config_translation\ConfigFieldMapper',
      'base_route_name' => 'entity.field_config.node_field_edit_form',
      'title' => '@label field',
      'names' => [],
      'entity_type' => 'field_config',
    ];

    $locale_config_manager = $this->getMockBuilder('Drupal\locale\LocaleConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->eventDispatcher = $this->createMock('Symfony\Contracts\EventDispatcher\EventDispatcherInterface');

    $this->configFieldMapper = new ConfigFieldMapper(
      'node_fields',
      $definition,
      $this->getConfigFactoryStub(),
      $this->createMock('Drupal\Core\Config\TypedConfigManagerInterface'),
      $locale_config_manager,
      $this->createMock('Drupal\config_translation\ConfigMapperManagerInterface'),
      $this->createMock('Drupal\Core\Routing\RouteProviderInterface'),
      $this->getStringTranslationStub(),
      $this->entityTypeManager,
      $this->createMock('Drupal\Core\Language\LanguageManagerInterface'),
      $this->eventDispatcher
    );
  }

  /**
   * Tests ConfigFieldMapper::setEntity().
   *
   * @covers ::setEntity
   */
  public function testSetEntity() {
    $entity_type = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type
      ->expects($this->any())
      ->method('getConfigPrefix')
      ->will($this->returnValue('config_prefix'));

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($entity_type));

    $field_storage = $this->createMock('Drupal\field\FieldStorageConfigInterface');
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
    $this->assertContains('config_prefix.field_storage_id', $plugin_definition['names']);

    // Make sure setEntity() returns FALSE when called a second time.
    $result = $this->configFieldMapper->setEntity($this->entity);
    $this->assertFalse($result);
  }

}
