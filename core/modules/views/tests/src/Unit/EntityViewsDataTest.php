<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\EntityViewsDataTest.
 */

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Core\Field\Plugin\Field\FieldType\UuidItem;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\Tests\UnitTestCase;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\EntityViewsData
 * @group Views
 */
class EntityViewsDataTest extends UnitTestCase {

  /**
   * Entity info to use in this test.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Drupal\Tests\views\Unit\TestEntityType
   */
  protected $baseEntityType;

  /**
   * The mocked entity storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translationManager;

  /**
   * The tested entity views controller.
   *
   * @var \Drupal\Tests\views\Unit\TestEntityViewsData
   */
  protected $viewsData;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityStorage = $this->getMockBuilder('Drupal\Core\Entity\Sql\SqlContentEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $typed_data_manager = $this->getMock(TypedDataManagerInterface::class);
    $typed_data_manager->expects($this->any())
      ->method('createDataDefinition')
      ->willReturn($this->getMock('Drupal\Core\TypedData\DataDefinitionInterface'));

    $typed_data_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->equalTo('field_item:string_long'))
      ->willReturn(['class' => '\Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem']);

    $this->baseEntityType = new TestEntityType([
      'base_table' => 'entity_test',
      'id' => 'entity_test',
      'label' => 'Entity test',
      'entity_keys' => [
        'uuid' => 'uuid',
        'id' => 'id',
        'langcode' => 'langcode',
        'bundle' => 'type',
        'revision' => 'revision_id',
      ],
      'provider' => 'entity_test',
      'list_cache_contexts' => ['entity_test_list_cache_context'],
    ]);

    $this->translationManager = $this->getStringTranslationStub();
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->viewsData = new TestEntityViewsData($this->baseEntityType, $this->entityStorage, $this->entityManager, $this->moduleHandler, $this->translationManager);

    $field_type_manager = $this->getMockBuilder('Drupal\Core\Field\FieldTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $field_type_manager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $field_type_manager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->willReturn([]);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    $container->set('entity.manager', $this->entityManager);
    $container->set('typed_data_manager', $typed_data_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Helper method to setup base fields.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition[] $base_fields
   *   The base fields which are adapted.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   The setup base fields.
   */
  protected function setupBaseFields(array $base_fields) {
    // Add a description field to the fields supplied by the EntityTest
    // classes. This example comes from the taxonomy Term entity.
    $base_fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel('Description')
      ->setDescription('A description of the term.')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
          'type' => 'text_textfield',
          'weight' => 0,
        ])
      ->setDisplayConfigurable('form', TRUE);

    // Add a URL field; this example is from the Comment entity.
    $base_fields['homepage'] = BaseFieldDefinition::create('uri')
      ->setLabel('Homepage')
      ->setDescription("The comment author's home page address.")
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255);

    // A base field with cardinality > 1
    $base_fields['string']  = BaseFieldDefinition::create('string')
      ->setLabel('Strong')
      ->setTranslatable(TRUE)
      ->setCardinality(2);

    foreach ($base_fields as $name => $base_field) {
      $base_field->setName($name);
    }
    return $base_fields;
  }

  /**
   * Tests base tables.
   */
  public function testBaseTables() {
    $data = $this->viewsData->getViewsData();

    $this->assertEquals('entity_test', $data['entity_test']['table']['entity type']);
    $this->assertEquals(FALSE, $data['entity_test']['table']['entity revision']);
    $this->assertEquals('Entity test', $data['entity_test']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    $this->assertEquals('id', $data['entity_test']['table']['base']['field']);
    $this->assertEquals(['entity_test_list_cache_context'], $data['entity_test']['table']['base']['cache_contexts']);
    $this->assertEquals('Entity test', $data['entity_test']['table']['base']['title']);

    $this->assertFalse(isset($data['entity_test']['table']['defaults']));

    $this->assertFalse(isset($data['entity_test_mul_property_data']));
    $this->assertFalse(isset($data['revision_table']));
    $this->assertFalse(isset($data['revision_data_table']));
  }

  /**
   * Tests data_table support.
   */
  public function testDataTable() {
    $entity_type = $this->baseEntityType
      ->set('data_table', 'entity_test_mul_property_data')
      ->set('id', 'entity_test_mul')
      ->set('translatable', TRUE)
      ->setKey('label', 'label');

    $this->viewsData->setEntityType($entity_type);

    // Tests the join definition between the base and the data table.
    $data = $this->viewsData->getViewsData();
    $base_views_data = $data['entity_test'];

    // Ensure that the base table is set to the data table.
    $this->assertEquals('id', $data['entity_test_mul_property_data']['table']['base']['field']);
    $this->assertEquals('Entity test', $data['entity_test_mul_property_data']['table']['base']['title']);
    $this->assertFalse(isset($data['entity_test']['table']['base']));

    $this->assertEquals('entity_test_mul', $data['entity_test_mul_property_data']['table']['entity type']);
    $this->assertEquals(FALSE, $data['entity_test_mul_property_data']['table']['entity revision']);
    $this->assertEquals('Entity test', $data['entity_test_mul_property_data']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);
    $this->assertEquals(['field' => 'label', 'table' => 'entity_test_mul_property_data'], $data['entity_test_mul_property_data']['table']['base']['defaults']);

    // Ensure the join information is set up properly.
    $this->assertCount(1, $base_views_data['table']['join']);
    $this->assertEquals(['entity_test_mul_property_data' => ['left_field' => 'id', 'field' => 'id', 'type' => 'INNER']], $base_views_data['table']['join']);
    $this->assertFalse(isset($data['revision_table']));
    $this->assertFalse(isset($data['revision_data_table']));
  }

  /**
   * Tests revision table without data table support.
   */
  public function testRevisionTableWithoutDataTable() {
    $entity_type = $this->baseEntityType
      ->set('revision_table', 'entity_test_mulrev_revision')
      ->set('revision_data_table', NULL)
      ->set('id', 'entity_test_mulrev')
      ->setKey('revision', 'revision_id');
    $this->viewsData->setEntityType($entity_type);

    $data = $this->viewsData->getViewsData();

    $this->assertEquals('Entity test revisions', $data['entity_test_mulrev_revision']['table']['base']['title']);
    $this->assertEquals('revision_id', $data['entity_test_mulrev_revision']['table']['base']['field']);

    $this->assertEquals(FALSE, $data['entity_test']['table']['entity revision']);
    $this->assertEquals('entity_test_mulrev', $data['entity_test_mulrev_revision']['table']['entity type']);
    $this->assertEquals(TRUE, $data['entity_test_mulrev_revision']['table']['entity revision']);
    $this->assertEquals('entity_test_mulrev', $data['entity_test_mulrev_revision']['table']['entity type']);
    $this->assertEquals(TRUE, $data['entity_test_mulrev_revision']['table']['entity revision']);

    $this->assertEquals('Entity test revision', $data['entity_test_mulrev_revision']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    // Ensure the join information is set up properly.
    // Tests the join definition between the base and the revision table.
    $revision_data = $data['entity_test_mulrev_revision'];
    $this->assertCount(1, $revision_data['table']['join']);
    $this->assertEquals(['entity_test' => ['left_field' => 'revision_id', 'field' => 'revision_id', 'type' => 'INNER']], $revision_data['table']['join']);
    $this->assertFalse(isset($data['data_table']));
  }

  /**
   * Tests revision table with data table support.
   */
  public function testRevisionTableWithRevisionDataTableAndDataTable() {
    $entity_type = $this->baseEntityType
      ->set('data_table', 'entity_test_mul_property_data')
      ->set('revision_table', 'entity_test_mulrev_revision')
      ->set('revision_data_table', 'entity_test_mulrev_property_revision')
      ->set('id', 'entity_test_mulrev')
      ->set('translatable', TRUE)
      ->setKey('revision', 'revision_id');
    $this->viewsData->setEntityType($entity_type);

    $data = $this->viewsData->getViewsData();

    $this->assertEquals('Entity test revisions', $data['entity_test_mulrev_property_revision']['table']['base']['title']);
    $this->assertEquals('revision_id', $data['entity_test_mulrev_property_revision']['table']['base']['field']);
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['table']['base']));

    $this->assertEquals('entity_test_mulrev', $data['entity_test_mulrev_property_revision']['table']['entity type']);
    $this->assertEquals('Entity test revision', $data['entity_test_mulrev_revision']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    // Ensure the join information is set up properly.
    // Tests the join definition between the base and the revision table.
    $revision_field_data = $data['entity_test_mulrev_property_revision'];
    $this->assertCount(1, $revision_field_data['table']['join']);
    $this->assertEquals([
      'entity_test_mul_property_data' => [
        'left_field' => 'revision_id',
        'field' => 'revision_id',
        'type' => 'INNER',
      ],
    ], $revision_field_data['table']['join']);

    $revision_base_data = $data['entity_test_mulrev_revision'];
    $this->assertCount(1, $revision_base_data['table']['join']);
    $this->assertEquals([
      'entity_test_mulrev_property_revision' => [
        'left_field' => 'revision_id',
        'field' => 'revision_id',
        'type' => 'INNER',
      ],
    ], $revision_base_data['table']['join']);

    $this->assertFalse(isset($data['data_table']));
  }

  /**
   * Tests revision table with data table support.
   */
  public function testRevisionTableWithRevisionDataTable() {
    $entity_type = $this->baseEntityType
      ->set('revision_table', 'entity_test_mulrev_revision')
      ->set('revision_data_table', 'entity_test_mulrev_property_revision')
      ->set('id', 'entity_test_mulrev')
      ->set('translatable', TRUE)
      ->setKey('revision', 'revision_id');
    $this->viewsData->setEntityType($entity_type);

    $data = $this->viewsData->getViewsData();

    $this->assertEquals('Entity test revisions', $data['entity_test_mulrev_property_revision']['table']['base']['title']);
    $this->assertEquals('revision_id', $data['entity_test_mulrev_property_revision']['table']['base']['field']);
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['table']['base']));

    $this->assertEquals('entity_test_mulrev', $data['entity_test_mulrev_property_revision']['table']['entity type']);
    $this->assertEquals('Entity test revision', $data['entity_test_mulrev_revision']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    // Ensure the join information is set up properly.
    // Tests the join definition between the base and the revision table.
    $revision_field_data = $data['entity_test_mulrev_property_revision'];
    $this->assertCount(1, $revision_field_data['table']['join']);
    $this->assertEquals([
      'entity_test_mulrev_field_data' => [
        'left_field' => 'revision_id',
        'field' => 'revision_id',
        'type' => 'INNER',
      ],
    ], $revision_field_data['table']['join']);

    $revision_base_data = $data['entity_test_mulrev_revision'];
    $this->assertCount(1, $revision_base_data['table']['join']);
    $this->assertEquals([
      'entity_test_mulrev_property_revision' => [
        'left_field' => 'revision_id',
        'field' => 'revision_id',
        'type' => 'INNER',
      ],
    ], $revision_base_data['table']['join']);
    $this->assertFalse(isset($data['data_table']));
  }

  /**
   * Helper method to mock all store definitions.
   */
  protected function setupFieldStorageDefinition() {
    $id_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $id_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(IntegerItem::schema($id_field_storage_definition));
    $uuid_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $uuid_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(UuidItem::schema($uuid_field_storage_definition));
    $type_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $type_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(StringItem::schema($type_field_storage_definition));
    $langcode_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $langcode_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(LanguageItem::schema($langcode_field_storage_definition));
    $name_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $name_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(StringItem::schema($name_field_storage_definition));
    $description_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $description_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(TextLongItem::schema($description_field_storage_definition));
    $homepage_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $homepage_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(UriItem::schema($homepage_field_storage_definition));
    $string_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $string_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(StringItem::schema($string_field_storage_definition));

    // Setup the user_id entity reference field.
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
          ['user', TRUE, static::userEntityInfo()],
        ]
      );
    $user_id_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $user_id_field_storage_definition->expects($this->any())
      ->method('getSetting')
      ->with('target_type')
      ->willReturn('user');
    $user_id_field_storage_definition->expects($this->any())
      ->method('getSettings')
      ->willReturn(['target_type' => 'user']);
    $user_id_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(EntityReferenceItem::schema($user_id_field_storage_definition));

    $revision_id_field_storage_definition = $this->getMock('Drupal\Core\Field\FieldStorageDefinitionInterface');
    $revision_id_field_storage_definition->expects($this->any())
      ->method('getSchema')
      ->willReturn(IntegerItem::schema($revision_id_field_storage_definition));

    $this->entityManager->expects($this->any())
      ->method('getFieldStorageDefinitions')
      ->willReturn([
        'id' => $id_field_storage_definition,
        'uuid' => $uuid_field_storage_definition,
        'type' => $type_field_storage_definition,
        'langcode' => $langcode_field_storage_definition,
        'name' => $name_field_storage_definition,
        'description' => $description_field_storage_definition,
        'homepage' => $homepage_field_storage_definition,
        'string' => $string_field_storage_definition,
        'user_id' => $user_id_field_storage_definition,
        'revision_id' => $revision_id_field_storage_definition,
      ]);
  }

  /**
   * Tests fields on the base table.
   */
  public function testBaseTableFields() {
    $base_field_definitions = $this->setupBaseFields(EntityTest::baseFieldDefinitions($this->baseEntityType));
    $user_base_field_definitions = [
      'uid' => BaseFieldDefinition::create('integer')
        ->setLabel('ID')
        ->setDescription('The ID of the user entity.')
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE)
    ];
    $this->entityManager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValueMap([
        ['user', $user_base_field_definitions],
        ['entity_test', $base_field_definitions],
      ]));
    // Setup the table mapping.
    $table_mapping = $this->getMockBuilder(DefaultTableMapping::class)
      ->disableOriginalConstructor()
      ->getMock();
    $table_mapping->expects($this->any())
      ->method('getTableNames')
      ->willReturn(['entity_test', 'entity_test__string']);
    $table_mapping->expects($this->any())
      ->method('getColumnNames')
      ->willReturnMap([
        ['id', ['value' => 'id']],
        ['uuid', ['value' => 'uuid']],
        ['type', ['value' => 'type']],
        ['langcode', ['value' => 'langcode']],
        ['name', ['value' => 'name']],
        ['description', ['value' => 'description__value', 'format' => 'description__format']],
        ['homepage', ['value' => 'homepage']],
        ['user_id', ['target_id' => 'user_id']],
        ['string', ['value' => 'value']],
      ]);
    $table_mapping->expects($this->any())
      ->method('getFieldNames')
      ->willReturnMap([
        ['entity_test', ['id', 'uuid', 'type', 'langcode', 'name', 'description', 'homepage', 'user_id']],
        ['entity_test__string', ['string']],
      ]);
    $table_mapping->expects($this->any())
      ->method('requiresDedicatedTableStorage')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        return $base_field->getName() === 'string';
      });
    $table_mapping->expects($this->any())
      ->method('getDedicatedDataTableName')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        if ($base_field->getName() === 'string') {
          return 'entity_test__string';
        }
      });

    $this->entityStorage->expects($this->once())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->setupFieldStorageDefinition();

    $data = $this->viewsData->getViewsData();

    $this->assertNumericField($data['entity_test']['id']);
    $this->assertField($data['entity_test']['id'], 'id');
    $this->assertUuidField($data['entity_test']['uuid']);
    $this->assertField($data['entity_test']['uuid'], 'uuid');
    $this->assertStringField($data['entity_test']['type']);
    $this->assertEquals('type', $data['entity_test']['type']['entity field']);

    $this->assertLanguageField($data['entity_test']['langcode']);
    $this->assertField($data['entity_test']['langcode'], 'langcode');
    $this->assertEquals('Original language', $data['entity_test']['langcode']['title']);

    $this->assertStringField($data['entity_test']['name']);
    $this->assertField($data['entity_test']['name'], 'name');

    $this->assertLongTextField($data['entity_test'], 'description');
    $this->assertField($data['entity_test']['description__value'], 'description');
    $this->assertField($data['entity_test']['description__format'], 'description');

    $this->assertUriField($data['entity_test']['homepage']);
    $this->assertField($data['entity_test']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test']['user_id']);
    $this->assertField($data['entity_test']['user_id'], 'user_id');

    $relationship = $data['entity_test']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    $this->assertStringField($data['entity_test__string']['string']);
    $this->assertField($data['entity_test__string']['string'], 'string');
    $this->assertEquals([
      'left_field' => 'id',
      'field' => 'entity_id',
      'extra' => [[
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ], $data['entity_test__string']['table']['join']['entity_test']);
  }

  /**
   * Tests fields on the data table.
   */
  public function testDataTableFields() {
    $entity_type = $this->baseEntityType
      ->set('data_table', 'entity_test_mul_property_data')
      ->set('base_table', 'entity_test_mul')
      ->set('id', 'entity_test_mul')
      ->setKey('bundle', 'type');
    $base_field_definitions = $this->setupBaseFields(EntityTestMul::baseFieldDefinitions($this->baseEntityType));
    $base_field_definitions['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('entity test type')
      ->setSetting('target_type', 'entity_test_bundle')
      ->setTranslatable(TRUE);
    $base_field_definitions = $this->setupBaseFields($base_field_definitions);
    $user_base_field_definitions = [
      'uid' => BaseFieldDefinition::create('integer')
        ->setLabel('ID')
        ->setDescription('The ID of the user entity.')
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE)
    ];
    $entity_test_type = new ConfigEntityType(['id' => 'entity_test_bundle']);

    $this->entityManager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValueMap([
        ['user', $user_base_field_definitions],
        ['entity_test_mul', $base_field_definitions],
      ]));

    $this->viewsData->setEntityType($entity_type);

    // Setup the table mapping.
    $table_mapping = $this->getMockBuilder(DefaultTableMapping::class)
      ->disableOriginalConstructor()
      ->getMock();
    $table_mapping->expects($this->any())
      ->method('getTableNames')
      ->willReturn(['entity_test_mul', 'entity_test_mul_property_data', 'entity_test_mul__string']);
    $table_mapping->expects($this->any())
      ->method('getColumnNames')
      ->willReturnMap([
        ['id', ['value' => 'id']],
        ['uuid', ['value' => 'uuid']],
        ['type', ['value' => 'type']],
        ['langcode', ['value' => 'langcode']],
        ['name', ['value' => 'name']],
        ['description', ['value' => 'description__value', 'format' => 'description__format']],
        ['homepage', ['value' => 'homepage']],
        ['user_id', ['target_id' => 'user_id']],
        ['string', ['value' => 'value']],
      ]);
    $table_mapping->expects($this->any())
      ->method('getFieldNames')
      ->willReturnMap([
        ['entity_test_mul', ['uuid']],
        ['entity_test_mul_property_data', ['id', 'type', 'langcode', 'name', 'description', 'homepage', 'user_id']],
        ['entity_test_mul__string', ['string']],
      ]);

    $table_mapping->expects($this->any())
      ->method('getFieldTableName')
      ->willReturnCallback(function ($field) {
        if ($field == 'uuid') {
          return 'entity_test_mul';
        }
        return 'entity_test_mul_property_data';
      });
    $table_mapping->expects($this->any())
      ->method('requiresDedicatedTableStorage')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        return $base_field->getName() === 'string';
      });
    $table_mapping->expects($this->any())
      ->method('getDedicatedDataTableName')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        if ($base_field->getName() === 'string') {
          return 'entity_test_mul__string';
        }
      });

    $this->entityStorage->expects($this->once())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->setupFieldStorageDefinition();

    $user_entity_type = static::userEntityInfo();
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValueMap([
        ['user', TRUE, $user_entity_type],
        ['entity_test_bundle', TRUE, $entity_test_type],
      ]));

    $data = $this->viewsData->getViewsData();

    // Check the base fields.
    $this->assertFalse(isset($data['entity_test_mul']['id']));
    $this->assertFalse(isset($data['entity_test_mul']['type']));
    $this->assertUuidField($data['entity_test_mul']['uuid']);
    $this->assertField($data['entity_test_mul']['uuid'], 'uuid');

    $this->assertFalse(isset($data['entity_test_mul']['type']['relationship']));

    // Also ensure that field_data only fields don't appear on the base table.
    $this->assertFalse(isset($data['entity_test_mul']['name']));
    $this->assertFalse(isset($data['entity_test_mul']['description']));
    $this->assertFalse(isset($data['entity_test_mul']['description__value']));
    $this->assertFalse(isset($data['entity_test_mul']['description__format']));
    $this->assertFalse(isset($data['entity_test_mul']['user_id']));
    $this->assertFalse(isset($data['entity_test_mul']['homepage']));

    // Check the data fields.
    $this->assertNumericField($data['entity_test_mul_property_data']['id']);
    $this->assertField($data['entity_test_mul_property_data']['id'], 'id');

    $this->assertBundleField($data['entity_test_mul_property_data']['type']);
    $this->assertField($data['entity_test_mul_property_data']['type'], 'type');

    $this->assertLanguageField($data['entity_test_mul_property_data']['langcode']);
    $this->assertField($data['entity_test_mul_property_data']['langcode'], 'langcode');
    $this->assertEquals('Translation language', $data['entity_test_mul_property_data']['langcode']['title']);

    $this->assertStringField($data['entity_test_mul_property_data']['name']);
    $this->assertField($data['entity_test_mul_property_data']['name'], 'name');

    $this->assertLongTextField($data['entity_test_mul_property_data'], 'description');
    $this->assertField($data['entity_test_mul_property_data']['description__value'], 'description');
    $this->assertField($data['entity_test_mul_property_data']['description__format'], 'description');

    $this->assertUriField($data['entity_test_mul_property_data']['homepage']);
    $this->assertField($data['entity_test_mul_property_data']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test_mul_property_data']['user_id']);
    $this->assertField($data['entity_test_mul_property_data']['user_id'], 'user_id');
    $relationship = $data['entity_test_mul_property_data']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    $this->assertStringField($data['entity_test_mul__string']['string']);
    $this->assertField($data['entity_test_mul__string']['string'], 'string');
    $this->assertEquals([
      'left_field' => 'id',
      'field' => 'entity_id',
      'extra' => [[
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ], $data['entity_test_mul__string']['table']['join']['entity_test_mul']);
  }

  /**
   * Tests fields on the revision table.
   */
  public function testRevisionTableFields() {
    $entity_type = $this->baseEntityType
      ->set('base_table', 'entity_test_mulrev')
      ->set('revision_table', 'entity_test_mulrev_revision')
      ->set('data_table', 'entity_test_mulrev_property_data')
      ->set('revision_data_table', 'entity_test_mulrev_property_revision')
      ->set('id', 'entity_test_mulrev')
      ->set('translatable', TRUE);
    $base_field_definitions = $this->setupBaseFields(EntityTestMulRev::baseFieldDefinitions($this->baseEntityType));
    $user_base_field_definitions = [
      'uid' => BaseFieldDefinition::create('integer')
        ->setLabel('ID')
        ->setDescription('The ID of the user entity.')
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE)
    ];
    $this->entityManager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->will($this->returnValueMap([
        ['user', $user_base_field_definitions],
        ['entity_test_mulrev', $base_field_definitions],
      ]));

    $this->viewsData->setEntityType($entity_type);

    // Setup the table mapping.
    $table_mapping = $this->getMockBuilder(DefaultTableMapping::class)
      ->disableOriginalConstructor()
      ->getMock();
    $table_mapping->expects($this->any())
      ->method('getTableNames')
      ->willReturn(['entity_test_mulrev', 'entity_test_mulrev_revision', 'entity_test_mulrev_property_data', 'entity_test_mulrev_property_revision', 'entity_test_mulrev__string', 'entity_test_mulrev_revision__string']);
    $table_mapping->expects($this->any())
      ->method('getColumnNames')
      ->willReturnMap([
        ['id', ['value' => 'id']],
        ['uuid', ['value' => 'uuid']],
        ['type', ['value' => 'type']],
        ['langcode', ['value' => 'langcode']],
        ['name', ['value' => 'name']],
        ['description', ['value' => 'description__value', 'format' => 'description__format']],
        ['homepage', ['value' => 'homepage']],
        ['user_id', ['target_id' => 'user_id']],
        ['revision_id', ['value' => 'id']],
        ['string', ['value' => 'value']],
      ]);
    $table_mapping->expects($this->any())
      ->method('getFieldNames')
      ->willReturnMap([
        ['entity_test_mulrev', ['id', 'revision_id', 'uuid', 'type']],
        ['entity_test_mulrev_revision', ['id', 'revision_id', 'langcode']],
        ['entity_test_mulrev_property_data', ['id', 'revision_id', 'langcode', 'name', 'description', 'homepage', 'user_id']],
        ['entity_test_mulrev_property_revision', ['id', 'revision_id', 'langcode', 'name', 'description', 'homepage', 'user_id']],
        ['entity_test_mulrev__string', ['string']],
        ['entity_test_mulrev_revision__string', ['string']],
      ]);
    $table_mapping->expects($this->any())
      ->method('requiresDedicatedTableStorage')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        return $base_field->getName() === 'string';
      });
    $table_mapping->expects($this->any())
      ->method('getDedicatedDataTableName')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        if ($base_field->getName() === 'string') {
          return 'entity_test_mulrev__string';
        }
      });

    $table_mapping->expects($this->any())
      ->method('getDedicatedRevisionTableName')
      ->willReturnCallback(function (BaseFieldDefinition $base_field) {
        if ($base_field->getName() === 'string') {
          return 'entity_test_mulrev_revision__string';
        }
      });

    $table_mapping->expects($this->any())
      ->method('getFieldTableName')
      ->willReturnCallback(function ($field) {
        if ($field == 'uuid') {
          return 'entity_test_mulrev';
        }
        return 'entity_test_mulrev_property_data';
      });

    $this->entityStorage->expects($this->once())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->setupFieldStorageDefinition();

    $data = $this->viewsData->getViewsData();

    // Check the base fields.
    $this->assertFalse(isset($data['entity_test_mulrev']['id']));
    $this->assertFalse(isset($data['entity_test_mulrev']['type']));
    $this->assertFalse(isset($data['entity_test_mulrev']['revision_id']));
    $this->assertUuidField($data['entity_test_mulrev']['uuid']);
    $this->assertField($data['entity_test_mulrev']['uuid'], 'uuid');

    // Also ensure that field_data only fields don't appear on the base table.
    $this->assertFalse(isset($data['entity_test_mulrev']['name']));
    $this->assertFalse(isset($data['entity_test_mul']['description']));
    $this->assertFalse(isset($data['entity_test_mul']['description__value']));
    $this->assertFalse(isset($data['entity_test_mul']['description__format']));
    $this->assertFalse(isset($data['entity_test_mul']['homepage']));
    $this->assertFalse(isset($data['entity_test_mulrev']['langcode']));
    $this->assertFalse(isset($data['entity_test_mulrev']['user_id']));

    // Check the revision fields. The revision ID should only appear in the data
    // table.
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['revision_id']));

    // Also ensure that field_data only fields don't appear on the revision table.
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['id']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['name']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['description']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['description__value']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['description__format']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['homepage']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['user_id']));

    // Check the data fields.
    $this->assertNumericField($data['entity_test_mulrev_property_data']['id']);
    $this->assertField($data['entity_test_mulrev_property_data']['id'], 'id');
    $this->assertNumericField($data['entity_test_mulrev_property_data']['revision_id']);
    $this->assertField($data['entity_test_mulrev_property_data']['revision_id'], 'revision_id');
    $this->assertLanguageField($data['entity_test_mulrev_property_data']['langcode']);
    $this->assertField($data['entity_test_mulrev_property_data']['langcode'], 'langcode');
    $this->assertStringField($data['entity_test_mulrev_property_data']['name']);
    $this->assertField($data['entity_test_mulrev_property_data']['name'], 'name');

    $this->assertLongTextField($data['entity_test_mulrev_property_data'], 'description');
    $this->assertField($data['entity_test_mulrev_property_data']['description__value'], 'description');
    $this->assertField($data['entity_test_mulrev_property_data']['description__format'], 'description');
    $this->assertUriField($data['entity_test_mulrev_property_data']['homepage']);
    $this->assertField($data['entity_test_mulrev_property_data']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test_mulrev_property_data']['user_id']);
    $this->assertField($data['entity_test_mulrev_property_data']['user_id'], 'user_id');
    $relationship = $data['entity_test_mulrev_property_data']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    // Check the property data fields.
    $this->assertNumericField($data['entity_test_mulrev_property_revision']['id']);
    $this->assertField($data['entity_test_mulrev_property_revision']['id'], 'id');

    $this->assertLanguageField($data['entity_test_mulrev_property_revision']['langcode']);
    $this->assertField($data['entity_test_mulrev_property_revision']['langcode'], 'langcode');
    $this->assertEquals('Translation language', $data['entity_test_mulrev_property_revision']['langcode']['title']);

    $this->assertStringField($data['entity_test_mulrev_property_revision']['name']);
    $this->assertField($data['entity_test_mulrev_property_revision']['name'], 'name');

    $this->assertLongTextField($data['entity_test_mulrev_property_revision'], 'description');
    $this->assertField($data['entity_test_mulrev_property_revision']['description__value'], 'description');
    $this->assertField($data['entity_test_mulrev_property_revision']['description__format'], 'description');

    $this->assertUriField($data['entity_test_mulrev_property_revision']['homepage']);
    $this->assertField($data['entity_test_mulrev_property_revision']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test_mulrev_property_revision']['user_id']);
    $this->assertField($data['entity_test_mulrev_property_revision']['user_id'], 'user_id');
    $relationship = $data['entity_test_mulrev_property_revision']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    $this->assertStringField($data['entity_test_mulrev__string']['string']);
    $this->assertField($data['entity_test_mulrev__string']['string'], 'string');
    $this->assertEquals([
      'left_field' => 'id',
      'field' => 'entity_id',
      'extra' => [[
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ], $data['entity_test_mulrev__string']['table']['join']['entity_test_mulrev_property_data']);

    $this->assertStringField($data['entity_test_mulrev_revision__string']['string']);
    $this->assertField($data['entity_test_mulrev_revision__string']['string'], 'string');
    $this->assertEquals([
      'left_field' => 'revision_id',
      'field' => 'entity_id',
      'extra' => [[
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ], $data['entity_test_mulrev_revision__string']['table']['join']['entity_test_mulrev_property_revision']);
  }

  /**
   * Tests generic stuff per field.
   *
   * @param array $data
   *   The views data to check.
   * @param string $field_name
   *   The entity field name.
   */
  protected function assertField($data, $field_name) {
    $this->assertEquals($field_name, $data['entity field']);
  }

  /**
   * Tests add link types.
   */
  public function testEntityLinks() {
    $this->baseEntityType->setLinkTemplate('canonical', '/entity_test/{entity_test}');
    $this->baseEntityType->setLinkTemplate('edit-form', '/entity_test/{entity_test}/edit');
    $this->baseEntityType->setLinkTemplate('delete-form', '/entity_test/{entity_test}/delete');

    $data = $this->viewsData->getViewsData();
    $this->assertEquals('entity_link', $data['entity_test']['view_entity_test']['field']['id']);
    $this->assertEquals('entity_link_edit', $data['entity_test']['edit_entity_test']['field']['id']);
    $this->assertEquals('entity_link_delete', $data['entity_test']['delete_entity_test']['field']['id']);
  }

  /**
   * Tests additional edit links.
   */
  public function testEntityLinksJustEditForm() {
    $this->baseEntityType->setLinkTemplate('edit-form', '/entity_test/{entity_test}/edit');

    $data = $this->viewsData->getViewsData();
    $this->assertFalse(isset($data['entity_test']['view_entity_test']));
    $this->assertFalse(isset($data['entity_test']['delete_entity_test']));

    $this->assertEquals('entity_link_edit', $data['entity_test']['edit_entity_test']['field']['id']);
  }

  /**
   * @covers ::getViewsData
   */
  public function testGetViewsDataWithoutEntityOperations() {
    // Make sure there is no list builder. The API does not document is
    // supports resetting entity handlers, so this might break in the future.
    $this->baseEntityType->setListBuilderClass(NULL);
    $data = $this->viewsData->getViewsData();
    $this->assertArrayNotHasKey('operations', $data[$this->baseEntityType->getBaseTable()]);
  }

  /**
   * @covers ::getViewsData
   */
  public function testGetViewsDataWithEntityOperations() {
    $this->baseEntityType->setListBuilderClass('\Drupal\Core\Entity\EntityListBuilder');
    $data = $this->viewsData->getViewsData();
    $this->assertSame('entity_operations', $data[$this->baseEntityType->getBaseTable()]['operations']['field']['id']);
  }

  /**
   * Tests views data for a string field.
   *
   * @param $data
   *   The views data to check.
   */
  protected function assertStringField($data) {
    $this->assertEquals('field', $data['field']['id']);
    $this->assertEquals('string', $data['filter']['id']);
    $this->assertEquals('string', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a URI field.
   *
   * @param $data
   *   The views data to check.
   */
  protected function assertUriField($data) {
    $this->assertEquals('field', $data['field']['id']);
    $this->assertEquals('string', $data['field']['default_formatter']);
    $this->assertEquals('string', $data['filter']['id']);
    $this->assertEquals('string', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a long text field.
   *
   * @param $data
   *   The views data for the table this field is in.
   * @param $field_name
   *   The name of the field being checked.
   */
  protected function assertLongTextField($data, $field_name) {
    $value_field = $data[$field_name . '__value'];
    $this->assertEquals('field', $value_field['field']['id']);
    $this->assertEquals($field_name . '__format', $value_field['field']['format']);
    $this->assertEquals('string', $value_field['filter']['id']);
    $this->assertEquals('string', $value_field['argument']['id']);
    $this->assertEquals('standard', $value_field['sort']['id']);

    $this->assertStringField($data[$field_name . '__format']);
  }

  /**
   * Tests views data for a UUID field.
   *
   * @param array $data
   *   The views data to check.
   */
  protected function assertUuidField($data) {
    // @todo Can we provide additional support for UUIDs in views?
    $this->assertEquals('field', $data['field']['id']);
    $this->assertFalse($data['field']['click sortable']);
    $this->assertEquals('string', $data['filter']['id']);
    $this->assertEquals('string', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a numeric field.
   *
   * @param array $data
   *   The views data to check.
   */
  protected function assertNumericField($data) {
    $this->assertEquals('field', $data['field']['id']);
    $this->assertEquals('numeric', $data['filter']['id']);
    $this->assertEquals('numeric', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a language field.
   *
   * @param array $data
   *   The views data to check.
   */
  protected function assertLanguageField($data) {
    $this->assertEquals('field', $data['field']['id']);
    $this->assertEquals('language', $data['filter']['id']);
    $this->assertEquals('language', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a entity reference field.
   */
  protected function assertEntityReferenceField($data) {
    $this->assertEquals('field', $data['field']['id']);
    $this->assertEquals('numeric', $data['filter']['id']);
    $this->assertEquals('numeric', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a bundle field.
   */
  protected function assertBundleField($data) {
    $this->assertEquals('field', $data['field']['id']);
    $this->assertEquals('bundle', $data['filter']['id']);
    $this->assertEquals('string', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Returns entity info for the user entity.
   *
   * @return array
   */
  protected static function userEntityInfo() {
    return new ContentEntityType([
      'id' => 'user',
      'class' => 'Drupal\user\Entity\User',
      'label' => 'User',
      'base_table' => 'users',
      'data_table' => 'users_field_data',
      'entity_keys' => [
        'id' => 'uid',
        'uuid' => 'uuid',
      ],
    ]);
  }

}

class TestEntityViewsData extends EntityViewsData {

  public function setEntityType(EntityTypeInterface $entity_type) {
    $this->entityType = $entity_type;
  }

}

class TestEntityType extends ContentEntityType {

  /**
   * Sets a specific entity key.
   *
   * @param string $key
   *   The name of the entity key.
   * @param string $value
   *   The new value of the key.
   *
   * @return $this
   */
  public function setKey($key, $value) {
    $this->entity_keys[$key] = $value;
    return $this;
  }

}

namespace Drupal\entity_test\Entity;

if (!function_exists('t')) {
  function t($string, array $args = []) {
    return strtr($string, $args);
  }
}


namespace Drupal\Core\Entity;

if (!function_exists('t')) {
  function t($string, array $args = []) {
    return strtr($string, $args);
  }
}
