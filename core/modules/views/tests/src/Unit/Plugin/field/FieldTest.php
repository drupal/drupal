<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\field\FieldTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\field;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Unit\Plugin\HandlerTestTrait;
use Drupal\views\Plugin\views\field\Field;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\Field
 * @group views
 */
class FieldTest extends UnitTestCase {

  use HandlerTestTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $formatterPluginManager;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The mocked field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->formatterPluginManager = $this->getMockBuilder('Drupal\Core\Field\FormatterPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->fieldTypePluginManager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->willReturn([]);

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->renderer = $this->getMock('Drupal\Core\Render\RendererInterface');

    $this->setupExecutableAndView();
    $this->setupViewsData();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $definition = [
      'entity_type' => 'test_entity',
      // Just provide 'entity field' as definition. This is how EntityViewsData
      // provides it.
      'entity field' => 'title',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);

    $this->assertEquals('title', $handler->definition['field_name']);
  }

  /**
   * @covers ::defineOptions()
   */
  public function testDefineOptionsWithNoOptions() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title'
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);

    // Setup the entity manager to allow fetching the storage definitions.
    $title_storage = $this->getBaseFieldStorage();

    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $options = [];
    $handler->init($this->executable, $this->display, $options);

    $this->assertEquals('value', $handler->options['group_column']);
    $this->assertEquals('all', $handler->options['delta_limit']);
  }

  /**
   * @covers ::defineOptions()
   */
  public function testDefineOptionsWithDefaultFormatter() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
      'default_formatter_settings' => ['link_to_entity' => TRUE]
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);

    // Setup the entity manager to allow fetching the storage definitions.
    $title_storage = $this->getBaseFieldStorage();

    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $options = [];
    $handler->init($this->executable, $this->display, $options);

    $this->assertEquals(['link_to_entity' => TRUE], $handler->options['settings']);
  }

  /**
   * @covers ::calculateDependencies()
   */
  public function testCalculateDependenciesWithBaseField() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title'
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);

    $title_storage = $this->getBaseFieldStorage();
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $dependencies = $handler->calculateDependencies();
    $this->assertEmpty($dependencies);
  }

  /**
   * @covers ::calculateDependencies()
   */
  public function testCalculateDependenciesWithConfiguredField() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'body'
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);

    $body_storage = $this->getConfigFieldStorage();
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'body' => $body_storage,
      ]);

    $body_storage->expects($this->atLeastOnce())
      ->method('getConfigDependencyName')
      ->willReturn('field.field_storage_config.body');

    $dependencies = $handler->calculateDependencies();
    $this->assertEquals(['config' => ['field.field_storage_config.body']], $dependencies);
  }

  /**
   * @covers ::access()
   */
  public function testAccess() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);
    $handler->view = $this->executable;
    $handler->setViewsData($this->viewsData);

    $this->view->expects($this->atLeastOnce())
      ->method('get')
      ->with('base_table')
      ->willReturn('test_entity_table');

    $this->viewsData->expects($this->atLeastOnce())
      ->method('get')
      ->with('test_entity_table')
      ->willReturn([
        'table' => ['entity type' => 'test_entity']
      ]);

    $access_control_handler = $this->getMock('Drupal\Core\Entity\EntityAccessControlHandlerInterface');
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getAccessControlHandler')
      ->with('test_entity')
      ->willReturn($access_control_handler);

    $title_storage = $this->getBaseFieldStorage();
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');

    $access_control_handler->expects($this->atLeastOnce())
      ->method('fieldAccess')
      ->with('view', $this->anything(), $account, NULL, FALSE)
      ->willReturn(TRUE);

    $this->assertTrue($handler->access($account));
  }

  /**
   * @dataProvider providerSortOrders
   *
   * @param string $order
   *   The sort order.
   *
   * @covers ::clickSort
   */
  public function testClickSortWithOutConfiguredColumn($order) {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);
    $handler->view = $this->executable;

    $this->entityManager->expects($this->never())
      ->method('getFieldStorageDefinitions');

    $handler->clickSort($order);
  }

  /**
   * @dataProvider providerSortOrders
   *
   * @param string $order
   *   The sort order.
   *
   * @covers ::clickSort
   */
  public function testClickSortWithBaseField($order) {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);
    $handler->view = $this->executable;

    $field_storage = $this->getBaseFieldStorage();
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $field_storage,
      ]);

    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->atLeastOnce())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('title');
    $entity_storage = $this->getMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->atLeastOnce())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($entity_storage);

    // Setup a click sort configuration.
    $options = [
      'click_sort_column' => 'value',
      'table' => 'test_entity',
    ];
    $handler->init($this->executable, $this->display, $options);

    $handler->query = $this->getMockBuilder('Drupal\views\Plugin\views\query\Sql')
      ->disableOriginalConstructor()
      ->getMock();
    $handler->query->expects($this->atLeastOnce())
      ->method('ensureTable')
      ->with('test_entity', NULL)
      ->willReturn('test_entity');

    $handler->query->expects($this->atLeastOnce())
      ->method('addOrderBy')
      ->with(NULL, NULL, $order, 'test_entity.title', []);
    $handler->clickSort($order);
  }

  /**
   * @dataProvider providerSortOrders
   *
   * @param string $order
   *   The sort order.
   *
   * @covers ::clickSort
   */
  public function testClickSortWithConfiguredField($order) {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'body',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);
    $handler->view = $this->executable;

    $field_storage = $this->getConfigFieldStorage();
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'body' => $field_storage,
      ]);

    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->atLeastOnce())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('body_value');
    $entity_storage = $this->getMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->atLeastOnce())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityManager->expects($this->atLeastOnce())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($entity_storage);

    // Setup a click sort configuration.
    $options = [
      'click_sort_column' => 'value',
      'table' => 'test_entity__body',
    ];
    $handler->init($this->executable, $this->display, $options);

    $handler->query = $this->getMockBuilder('Drupal\views\Plugin\views\query\Sql')
      ->disableOriginalConstructor()
      ->getMock();
    $handler->query->expects($this->atLeastOnce())
      ->method('ensureTable')
      ->with('test_entity__body', NULL)
      ->willReturn('test_entity__body_alias');

    $handler->query->expects($this->atLeastOnce())
      ->method('addOrderBy')
      ->with(NULL, NULL, $order, 'test_entity__body_alias.body_value', []);
    $handler->clickSort($order);
  }

  /**
   * @covers ::query
   */
  public function testQueryWithGroupByForBaseField() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);
    $handler->view = $this->executable;

    $field_storage = $this->getBaseFieldStorage();
    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $field_storage,
      ]);

    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->any())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('title');
    $entity_storage = $this->getMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($entity_storage);

    $options = [
      'group_column' => 'value',
      'group_columns' => [],
      'table' => 'test_entity_table',
    ];
    $handler->init($this->executable, $this->display, $options);

    $query = $this->getMockBuilder('Drupal\views\Plugin\views\query\Sql')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->once())
      ->method('ensureTable')
      ->with('test_entity_table', NULL)
      ->willReturn('test_entity_table');
    // Ensure that we add the title field to the query, if we group by some
    // other field in the view.
    $query->expects($this->once())
      ->method('addField')
      ->with('test_entity_table', 'title');

    $this->executable->query = $query;

    $handler->query(TRUE);
  }

  /**
   * @covers ::query
   */
  public function testQueryWithGroupByForConfigField() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'body',
    ];
    $handler = new Field([], 'field', $definition, $this->entityManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer);
    $handler->view = $this->executable;

    $field_storage = $this->getConfigFieldStorage();
    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'body' => $field_storage,
      ]);

    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->any())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('body_value');
    $entity_storage = $this->getMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($entity_storage);

    $options = [
      'group_column' => 'value',
      'group_columns' => [],
      'table' => 'test_entity__body',
    ];
    $handler->init($this->executable, $this->display, $options);

    $query = $this->getMockBuilder('Drupal\views\Plugin\views\query\Sql')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->once())
      ->method('ensureTable')
      ->with('test_entity__body', NULL)
      ->willReturn('test_entity__body');
    // Ensure that we add the title field to the query, if we group by some
    // other field in the view.
    $query->expects($this->once())
      ->method('addField')
      ->with('test_entity__body', 'body_value');

    $this->executable->query = $query;

    $handler->query(TRUE);
  }

  /**
   * Returns a mocked base field storage object.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getBaseFieldStorage() {
    $title_storage = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $title_storage->expects($this->any())
      ->method('getColumns')
      ->willReturn(['value' => ['type' => 'varchar']]);
    $title_storage->expects($this->any())
      ->method('getSettings')
      ->willReturn([]);
    $title_storage->expects($this->any())
      ->method('getConstraints')
      ->willReturn([]);
    return $title_storage;
  }

  /**
   * Returns a mocked configurable field storage object.
   *
   * @return \Drupal\field\FieldStorageConfigInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getConfigFieldStorage() {
    $title_storage = $this->getMock('Drupal\field\FieldStorageConfigInterface');
    $title_storage->expects($this->any())
      ->method('getColumns')
      ->willReturn(['value' => ['type' => 'varchar']]);
    $title_storage->expects($this->any())
      ->method('getSettings')
      ->willReturn([]);
    $title_storage->expects($this->any())
      ->method('getConstraints')
      ->willReturn([]);
    return $title_storage;
  }

  /**
   * Provides sort orders for clickSort() test methods.
   *
   * @return array
   */
  public function providerSortOrders() {
    return [
      ['asc'],
      ['desc'],
      ['ASC'],
      ['DESC'],
    ];
  }

}
