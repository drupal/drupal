<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\field\FieldTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Unit\Plugin\HandlerTestTrait;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\EntityField
 * @group views
 */
class FieldTest extends UnitTestCase {

  use HandlerTestTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * The mocked formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formatterPluginManager;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The mocked field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);
    $this->formatterPluginManager = $this->getMockBuilder('Drupal\Core\Field\FormatterPluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->fieldTypePluginManager = $this->createMock('Drupal\Core\Field\FieldTypePluginManagerInterface');
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->willReturn([]);

    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->renderer = $this->createMock('Drupal\Core\Render\RendererInterface');

    $this->setupExecutableAndView();
    $this->setupViewsData();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $this->container = new ContainerBuilder();
    $this->container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($this->container);
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
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);

    $this->assertEquals('title', $handler->definition['field_name']);
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptionsWithNoOptions() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);

    // Setup the entity field manager to allow fetching the storage definitions.
    $title_storage = $this->getBaseFieldStorage();

    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $options = [];
    $handler->init($this->executable, $this->display, $options);

    $this->assertEquals('value', $handler->options['group_column']);
    $this->assertEquals(0, $handler->options['delta_limit']);
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptionsWithDefaultFormatterOnFieldDefinition() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
      'default_formatter' => 'test_example',
      'default_formatter_settings' => ['link_to_entity' => TRUE],
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);

    // Setup the entity field manager to allow fetching the storage definitions.
    $title_storage = $this->getBaseFieldStorage();

    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $options = [];
    $handler->init($this->executable, $this->display, $options);

    $this->assertEquals('test_example', $handler->options['type']);
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptionsWithDefaultFormatterOnFieldType() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
      'default_formatter_settings' => ['link_to_entity' => TRUE],
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);

    // Setup the entity field manager to allow fetching the storage definitions.
    $title_storage = $this->getBaseFieldStorage();

    $this->entityFieldManager->expects($this->atLeastOnce())
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
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithBaseField() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);

    $title_storage = $this->getBaseFieldStorage();
    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $dependencies = $handler->calculateDependencies();
    $this->assertEmpty($dependencies);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependenciesWithConfiguredField() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'body',
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);

    $body_storage = $this->getConfigFieldStorage();
    $this->entityFieldManager->expects($this->atLeastOnce())
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
   * @covers ::access
   */
  public function testAccess() {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
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
        'table' => ['entity type' => 'test_entity'],
      ]);

    $access_control_handler = $this->createMock('Drupal\Core\Entity\EntityAccessControlHandlerInterface');
    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getAccessControlHandler')
      ->with('test_entity')
      ->willReturn($access_control_handler);

    $title_storage = $this->getBaseFieldStorage();
    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $title_storage,
      ]);

    $account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $access_control_handler->expects($this->atLeastOnce())
      ->method('fieldAccess')
      ->with('view', $this->anything(), $account, NULL, $this->anything())
      ->willReturn(TRUE);

    $this->assertTrue($handler->access($account));
  }

  /**
   * @dataProvider providerSortOrders
   *
   * @param string $order
   *   The sort order.
   */
  public function testClickSortWithOutConfiguredColumn($order) {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'title',
    ];
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
    $handler->view = $this->executable;

    $this->entityFieldManager->expects($this->never())
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
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
    $handler->view = $this->executable;

    $field_storage = $this->getBaseFieldStorage();
    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $field_storage,
      ]);

    $table_mapping = $this->createMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->atLeastOnce())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('title');
    $entity_storage = $this->createMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->atLeastOnce())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityTypeManager->expects($this->atLeastOnce())
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
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
    $handler->view = $this->executable;

    $field_storage = $this->getConfigFieldStorage();
    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'body' => $field_storage,
      ]);

    $table_mapping = $this->createMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->atLeastOnce())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('body_value');
    $entity_storage = $this->createMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->atLeastOnce())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityTypeManager->expects($this->atLeastOnce())
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
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
    $handler->view = $this->executable;
    $handler->view->field = [$handler];

    $this->setupLanguageRenderer($handler, $definition);

    $field_storage = $this->getBaseFieldStorage();
    $this->entityFieldManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'title' => $field_storage,
      ]);

    $table_mapping = $this->createMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->any())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('title');
    $entity_storage = $this->createMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityTypeManager->expects($this->any())
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
    $handler = new EntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
    $handler->view = $this->executable;
    $handler->view->field = [$handler];

    $this->setupLanguageRenderer($handler, $definition);

    $field_storage = $this->getConfigFieldStorage();
    $this->entityFieldManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'body' => $field_storage,
      ]);

    $table_mapping = $this->createMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->any())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('body_value');
    $entity_storage = $this->createMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityTypeManager->expects($this->any())
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
   * @covers ::prepareItemsByDelta
   *
   * @dataProvider providerTestPrepareItemsByDelta
   */
  public function testPrepareItemsByDelta(array $options, array $expected_values) {
    $definition = [
      'entity_type' => 'test_entity',
      'field_name' => 'integer',
    ];
    $handler = new FieldTestEntityField([], 'field', $definition, $this->entityTypeManager, $this->formatterPluginManager, $this->fieldTypePluginManager, $this->languageManager, $this->renderer, $this->entityRepository, $this->entityFieldManager);
    $handler->view = $this->executable;
    $handler->view->field = [$handler];

    $this->setupLanguageRenderer($handler, $definition);

    $field_storage = $this->getConfigFieldStorage();
    $field_storage->expects($this->any())
      ->method('getCardinality')
      ->willReturn(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $this->entityFieldManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->with('test_entity')
      ->willReturn([
        'integer' => $field_storage,
      ]);

    $table_mapping = $this->createMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping
      ->expects($this->any())
      ->method('getFieldColumnName')
      ->with($field_storage, 'value')
      ->willReturn('integer_value');
    $entity_storage = $this->createMock('Drupal\Core\Entity\Sql\SqlEntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('getTableMapping')
      ->willReturn($table_mapping);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('test_entity')
      ->willReturn($entity_storage);

    $options = [
      'group_column' => 'value',
      'group_columns' => [],
      'table' => 'test_entity__integer',
    ] + $options;
    $handler->init($this->executable, $this->display, $options);

    $this->executable->row_index = 0;
    $this->executable->result = [0 => new ResultRow([])];

    $items = [3, 1, 4, 1, 5, 9];
    $this->assertEquals($expected_values, $handler->executePrepareItemsByDelta($items));
  }

  /**
   * Provides test data for testPrepareItemsByDelta().
   */
  public function providerTestPrepareItemsByDelta() {
    $data = [];

    // Let's display all values.
    $data[] = [[], [3, 1, 4, 1, 5, 9]];
    // Test just reversed deltas.
    $data[] = [['delta_reversed' => TRUE], [9, 5, 1, 4, 1, 3]];

    // Test combinations of delta limit, offset and first_last.
    $data[] = [['group_rows' => TRUE, 'delta_limit' => 3], [3, 1, 4]];
    $data[] = [['group_rows' => TRUE, 'delta_limit' => 3, 'delta_offset' => 2], [4, 1, 5]];
    $data[] = [['group_rows' => TRUE, 'delta_reversed' => TRUE, 'delta_limit' => 3, 'delta_offset' => 2], [1, 4, 1]];
    $data[] = [['group_rows' => TRUE, 'delta_first_last' => TRUE], [3, 9]];
    $data[] = [['group_rows' => TRUE, 'delta_limit' => 1, 'delta_first_last' => TRUE], [3]];
    $data[] = [['group_rows' => TRUE, 'delta_offset' => 1, 'delta_first_last' => TRUE], [1, 9]];

    return $data;
  }

  /**
   * Returns a mocked base field storage object.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getBaseFieldStorage() {
    $title_storage = $this->createMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
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
   * @return \Drupal\field\FieldStorageConfigInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getConfigFieldStorage() {
    $title_storage = $this->createMock('Drupal\field\FieldStorageConfigInterface');
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

  /**
   * Setup the mock data needed to make language renderers work.
   *
   * @param \Drupal\views\Plugin\views\field\EntityField $handler
   *   The field handler.
   * @param $definition
   *   An array with entity type definition data.
   */
  protected function setupLanguageRenderer(EntityField $handler, $definition) {
    $display_handler = $this->getMockBuilder('\Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $display_handler->expects($this->any())
      ->method('getOption')
      ->with($this->equalTo('rendering_language'))
      ->willReturn('en');
    $handler->view->display_handler = $display_handler;

    $data['table']['entity type'] = $definition['entity_type'];
    $views_data = $this->getMockBuilder('\Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->any())
      ->method('get')
      ->willReturn($data);
    $this->container->set('views.views_data', $views_data);

    $entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('id')
      ->willReturn($definition['entity_type']);

    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->willReturn($entity_type);
  }

}

class FieldTestEntityField extends EntityField {

  public function executePrepareItemsByDelta(array $all_values) {
    return $this->prepareItemsByDelta($all_values);
  }

}
