<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\EntityViewsDataTest.
 */

namespace Drupal\Tests\views\Unit {

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Field\Plugin\Field\FieldType\LanguageItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Core\Field\Plugin\Field\FieldType\UuidItem;
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
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Drupal\views\Tests\TestEntityType
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
   * @var \Drupal\views\Tests\TestEntityViewsData
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

    $this->baseEntityType = new TestEntityType([
      'base_table' => 'entity_test',
      'id' => 'entity_test',
      'label' => 'Entity test',
      'entity_keys' => ['id' => 'id'],
      'provider' => 'entity_test',
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
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the term.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'text_textfield',
          'weight' => 0,
        ))
      ->setDisplayConfigurable('form', TRUE);

    // Add a URL field; this example is from the Comment entity.
    $base_fields['homepage'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Homepage'))
      ->setDescription(t("The comment author's home page address."))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255);

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
    $this->assertEquals('Entity test', $data['entity_test']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    $this->assertEquals('id', $data['entity_test']['table']['base']['field']);
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
    $entity_type = $this->baseEntityType->set('data_table', 'entity_test_mul_property_data')
      ->set('id', 'entity_test_mul')
      ->setKey('label', 'label');

    $this->viewsData->setEntityType($entity_type);

    // Tests the join definition between the base and the data table.
    $data = $this->viewsData->getViewsData();
    $field_views_data = $data['entity_test_mul_property_data'];

    $this->assertEquals('entity_test_mul', $data['entity_test_mul_property_data']['table']['entity type']);
    $this->assertEquals('Entity test', $data['entity_test_mul_property_data']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);
    $this->assertEquals(['field' => 'label', 'table' => 'entity_test_mul_property_data'], $data['entity_test']['table']['base']['defaults']);

    // Ensure the join information is set up properly.
    $this->assertCount(1, $field_views_data['table']['join']);
    $this->assertEquals(['entity_test' => ['left_field' => 'id', 'field' => 'id', 'type' => 'INNER']], $field_views_data['table']['join']);
    $this->assertFalse(isset($data['revision_table']));
    $this->assertFalse(isset($data['revision_data_table']));
  }

  /**
   * Tests revision table support.
   */
  public function testRevisionTable() {
    $entity_type = $this->baseEntityType
      ->set('revision_table', 'entity_test_mulrev_revision')
      ->set('revision_data_table', 'entity_test_mulrev_property_revision')
      ->set('id', 'entity_test_mulrev')
      ->setKey('revision', 'revision_id')
    ;
    $this->viewsData->setEntityType($entity_type);

    $data = $this->viewsData->getViewsData();

    $this->assertEquals('entity_test_mulrev', $data['entity_test_mulrev_revision']['table']['entity type']);
    $this->assertEquals('entity_test_mulrev', $data['entity_test_mulrev_property_revision']['table']['entity type']);
    $this->assertEquals('Entity test revision', $data['entity_test_mulrev_revision']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    // Ensure the join information is set up properly.
    // Tests the join definition between the base and the revision table.
    $revision_data = $data['entity_test_mulrev_revision'];
    $this->assertCount(1, $revision_data['table']['join']);
    $this->assertEquals(['entity_test' => ['left_field' => 'revision_id', 'field' => 'revision_id', 'type' => 'INNER']], $revision_data['table']['join']);
    $revision_data = $data['entity_test_mulrev_property_revision'];
    $this->assertCount(1, $revision_data['table']['join']);
    $this->assertEquals(['entity_test_mulrev_revision' => ['left_field' => 'revision_id', 'field' => 'revision_id', 'type' => 'INNER']], $revision_data['table']['join']);
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
        'user_id' => $user_id_field_storage_definition,
        'revision_id' => $revision_id_field_storage_definition,
      ]);
  }

  /**
   * Tests fields on the base table.
   */
  public function testBaseTableFields() {
    $base_field_definitions = $this->setupBaseFields(EntityTest::baseFieldDefinitions($this->baseEntityType));

    $this->entityManager->expects($this->once())
      ->method('getBaseFieldDefinitions')
      ->with('entity_test')
      ->willReturn($base_field_definitions);

    // Setup the table mapping.
    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping->expects($this->any())
      ->method('getTableNames')
      ->willReturn(['entity_test']);
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
      ]);
    $table_mapping->expects($this->any())
      ->method('getFieldNames')
      ->willReturnMap([
        ['entity_test', ['id', 'uuid', 'type', 'langcode', 'name', 'description', 'homepage', 'user_id']]
      ]);

    $this->entityStorage->expects($this->once())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->setupFieldStorageDefinition();

    $data = $this->viewsData->getViewsData();

    $this->assertNumericField($data['entity_test']['id']);
    $this->assertUuidField($data['entity_test']['uuid']);
    $this->assertStringField($data['entity_test']['type']);

    $this->assertLanguageField($data['entity_test']['langcode']);
    $this->assertEquals('Original language', $data['entity_test']['langcode']['title']);

    $this->assertStringField($data['entity_test']['name']);

    $this->assertLongTextField($data['entity_test'], 'description');

    $this->assertUriField($data['entity_test']['homepage']);

    $this->assertEntityReferenceField($data['entity_test']['user_id']);
    $relationship = $data['entity_test']['user_id']['relationship'];
    $this->assertEquals('users', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);
  }

  /**
   * Tests fields on the data table.
   */
  public function testDataTableFields() {
    $entity_type = $this->baseEntityType
      ->set('data_table', 'entity_test_mul_property_data')
      ->set('base_table', 'entity_test_mul')
      ->set('id', 'entity_test_mul')
      ->setKey('bundle', 'type')
    ;
    $base_field_definitions = $this->setupBaseFields(EntityTestMul::baseFieldDefinitions($this->baseEntityType));
    $base_field_definitions['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('entity test type')
      ->setSettings(array('target_type' => 'entity_test_bundle'))
      ->setTranslatable(TRUE);
    $base_field_definitions = $this->setupBaseFields($base_field_definitions);
    $entity_test_type = new ConfigEntityType(['id' => 'entity_test_bundle']);
    $user_entity_type = new ContentEntityType(['id' => 'user', 'base_table' => 'users', 'entity_keys' => ['id' => 'uid']]);
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
        ['entity_test_bundle', TRUE, $entity_test_type],
        ['user', TRUE, $user_entity_type],
      ]);

    $this->entityManager->expects($this->once())
      ->method('getBaseFieldDefinitions')
      ->with('entity_test_mul')
      ->willReturn($base_field_definitions);

    $this->viewsData->setEntityType($entity_type);

     // Setup the table mapping.
    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping->expects($this->any())
      ->method('getTableNames')
      ->willReturn(['entity_test_mul', 'entity_test_mul_property_data']);
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
      ]);
    $table_mapping->expects($this->any())
      ->method('getFieldNames')
      ->willReturnMap([
        ['entity_test_mul', ['id', 'uuid', 'type', 'langcode']],
        ['entity_test_mul_property_data', ['id', 'langcode', 'name', 'description', 'homepage', 'user_id']],
      ]);

    $this->entityStorage->expects($this->once())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->setupFieldStorageDefinition();

    $data = $this->viewsData->getViewsData();

    // Check the base fields.
    $this->assertNumericField($data['entity_test_mul']['id']);
    $this->assertUuidField($data['entity_test_mul']['uuid']);

    $this->assertBundleField($data['entity_test_mul']['type']);
    $this->assertFalse(isset($data['entity_test_mul']['type']['relationship']));

    $this->assertLanguageField($data['entity_test_mul']['langcode']);
    // Also ensure that field_data only fields don't appear on the base table.
    $this->assertFalse(isset($data['entity_test_mul']['name']));
    $this->assertFalse(isset($data['entity_test_mul']['description']));
    $this->assertFalse(isset($data['entity_test_mul']['description__value']));
    $this->assertFalse(isset($data['entity_test_mul']['description__format']));
    $this->assertFalse(isset($data['entity_test_mul']['user_id']));
    $this->assertFalse(isset($data['entity_test_mul']['homepage']));

    // Check the data fields.
    $this->assertNumericField($data['entity_test_mul_property_data']['id']);

    $this->assertLanguageField($data['entity_test_mul_property_data']['langcode']);
    $this->assertEquals('Translation language', $data['entity_test_mul_property_data']['langcode']['title']);

    $this->assertStringField($data['entity_test_mul_property_data']['name']);

    $this->assertLongTextField($data['entity_test_mul_property_data'], 'description');

    $this->assertUriField($data['entity_test_mul_property_data']['homepage']);

    $this->assertEntityReferenceField($data['entity_test_mul_property_data']['user_id']);
    $relationship = $data['entity_test_mul_property_data']['user_id']['relationship'];
    $this->assertEquals('users', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);
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
      ->set('id', 'entity_test_mulrev');
    $base_field_definitions = $this->setupBaseFields(EntityTestMulRev::baseFieldDefinitions($this->baseEntityType));
    $this->entityManager->expects($this->once())
      ->method('getBaseFieldDefinitions')
      ->with('entity_test_mulrev')
      ->willReturn($base_field_definitions);

    $this->viewsData->setEntityType($entity_type);

     // Setup the table mapping.
    $table_mapping = $this->getMock('Drupal\Core\Entity\Sql\TableMappingInterface');
    $table_mapping->expects($this->any())
      ->method('getTableNames')
      ->willReturn(['entity_test_mulrev', 'entity_test_mulrev_revision', 'entity_test_mulrev_property_data', 'entity_test_mulrev_property_revision']);
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
      ]);
    $table_mapping->expects($this->any())
      ->method('getFieldNames')
      ->willReturnMap([
        ['entity_test_mulrev', ['id', 'revision_id', 'uuid', 'type']],
        ['entity_test_mulrev_revision', ['id', 'revision_id', 'langcode']],
        ['entity_test_mulrev_property_data', ['id', 'revision_id', 'langcode', 'name', 'description', 'homepage', 'user_id']],
        ['entity_test_mulrev_property_revision', ['id', 'revision_id', 'langcode', 'name', 'description', 'homepage', 'user_id']],
      ]);

    $this->entityStorage->expects($this->once())
      ->method('getTableMapping')
      ->willReturn($table_mapping);

    $this->setupFieldStorageDefinition();

    $data = $this->viewsData->getViewsData();

    // Check the base fields.
    $this->assertNumericField($data['entity_test_mulrev']['id']);
    $this->assertNumericField($data['entity_test_mulrev']['revision_id']);
    $this->assertUuidField($data['entity_test_mulrev']['uuid']);
    $this->assertStringField($data['entity_test_mulrev']['type']);

    // Also ensure that field_data only fields don't appear on the base table.
    $this->assertFalse(isset($data['entity_test_mulrev']['name']));
    $this->assertFalse(isset($data['entity_test_mul']['description']));
    $this->assertFalse(isset($data['entity_test_mul']['description__value']));
    $this->assertFalse(isset($data['entity_test_mul']['description__format']));
    $this->assertFalse(isset($data['entity_test_mul']['homepage']));
    $this->assertFalse(isset($data['entity_test_mulrev']['langcode']));
    $this->assertFalse(isset($data['entity_test_mulrev']['user_id']));

    // Check the revision fields.
    $this->assertNumericField($data['entity_test_mulrev_revision']['id']);
    $this->assertNumericField($data['entity_test_mulrev_revision']['revision_id']);

    $this->assertLanguageField($data['entity_test_mulrev_revision']['langcode']);
    $this->assertEquals('Original language', $data['entity_test_mulrev_revision']['langcode']['title']);

    // Also ensure that field_data only fields don't appear on the revision table.
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['name']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['description']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['description__value']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['description__format']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['homepage']));
    $this->assertFalse(isset($data['entity_test_mulrev_revision']['user_id']));

    // Check the data fields.
    $this->assertNumericField($data['entity_test_mulrev_property_data']['id']);
    $this->assertLanguageField($data['entity_test_mulrev_property_data']['langcode']);
    $this->assertStringField($data['entity_test_mulrev_property_data']['name']);

    $this->assertLongTextField($data['entity_test_mulrev_property_data'], 'description');
    $this->assertUriField($data['entity_test_mulrev_property_data']['homepage']);

    $this->assertEntityReferenceField($data['entity_test_mulrev_property_data']['user_id']);
    $relationship = $data['entity_test_mulrev_property_data']['user_id']['relationship'];
    $this->assertEquals('users', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    // Check the property data fields.
    $this->assertNumericField($data['entity_test_mulrev_property_revision']['id']);

    $this->assertLanguageField($data['entity_test_mulrev_property_revision']['langcode']);
    $this->assertEquals('Translation language', $data['entity_test_mulrev_property_revision']['langcode']['title']);

    $this->assertStringField($data['entity_test_mulrev_property_revision']['name']);

    $this->assertLongTextField($data['entity_test_mulrev_property_revision'], 'description');

    $this->assertUriField($data['entity_test_mulrev_property_revision']['homepage']);

    $this->assertEntityReferenceField($data['entity_test_mulrev_property_revision']['user_id']);
    $relationship = $data['entity_test_mulrev_property_revision']['user_id']['relationship'];
    $this->assertEquals('users', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);
  }

  /**
   * Tests views data for a string field.
   *
   * @param $data
   *   The views data to check.
   */
  protected function assertStringField($data) {
    $this->assertEquals('standard', $data['field']['id']);
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
    $this->assertEquals('url', $data['field']['id']);
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
    $this->assertEquals('markup', $value_field['field']['id']);
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
    $this->assertEquals('standard', $data['field']['id']);
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
    $this->assertEquals('numeric', $data['field']['id']);
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
    $this->assertEquals('language', $data['field']['id']);
    $this->assertEquals('language', $data['filter']['id']);
    $this->assertEquals('language', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a entity reference field.
   */
  protected function assertEntityReferenceField($data) {
    $this->assertEquals('numeric', $data['field']['id']);
    $this->assertEquals('numeric', $data['filter']['id']);
    $this->assertEquals('numeric', $data['argument']['id']);
    $this->assertEquals('standard', $data['sort']['id']);
  }

  /**
   * Tests views data for a bundle field.
   */
  protected function assertBundleField($data) {
    $this->assertEquals('standard', $data['field']['id']);
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

class TestEntityType extends EntityType {

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

}


namespace {
  use Drupal\Component\Utility\String;

  if (!function_exists('t')) {
    function t($string, array $args = []) {
      return String::format($string, $args);
    }
  }
}
