<?php

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\EntityViewsData;

/**
 * Tests entity views data.
 *
 * @coversDefaultClass \Drupal\views\EntityViewsData
 * @group views
 */
class EntityViewsDataTest extends KernelTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The base entity type definition, which some tests modify.
   *
   * Uses a custom class which allows changing entity keys.
   *
   * @var \Drupal\Tests\views\Kernel\Entity\TestEntityType
   */
  protected $baseEntityType;

  /**
   * The common base fields for test entity types.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected $commonBaseFields;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // A common entity type definition. Tests may change this prior to passing
    // it to setUpEntityType().
    $this->baseEntityType = new TestEntityType([
      // A normal entity type would have its class picked up during discovery,
      // but as we're mocking this without an annotation we have to specify it.
      'class' => ViewsTestEntity::class,
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
      'handlers' => [
        'views_data' => EntityViewsData::class,
      ],
      'provider' => 'entity_test',
      'list_cache_contexts' => ['entity_test_list_cache_context'],
    ]);

    // Base fields for the test entity types.
    $this->commonBaseFields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the test entity.'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 32);

    $this->commonBaseFields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('Time the entity was created'))
      ->setTranslatable(TRUE);

    $this->commonBaseFields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The ID of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      // Default EntityTest entities to have the root user as the owner, to
      // simplify testing.
      ->setDefaultValue([0 => ['target_id' => 1]])
      ->setTranslatable(TRUE);

    // Add a description field. This example comes from the taxonomy Term
    // entity.
    $this->commonBaseFields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel('Description')
      ->setDescription('A description of the term.')
      ->setTranslatable(TRUE);

    // Add a URL field; this example is from the Comment entity.
    $this->commonBaseFields['homepage'] = BaseFieldDefinition::create('uri')
      ->setLabel('Homepage')
      ->setDescription("The comment author's home page address.")
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255);

    // A base field with cardinality > 1
    $this->commonBaseFields['string'] = BaseFieldDefinition::create('string')
      ->setLabel('Strong')
      ->setTranslatable(TRUE)
      ->setCardinality(2);

    // Set up the basic 'entity_test' entity type. This is used by several
    // tests; others customize it and the base fields.
    $this->setUpEntityType($this->baseEntityType, $this->commonBaseFields);
  }

  /**
   * Mocks an entity type and its base fields.
   *
   * This works by:
   * - inserting the entity type definition into the entity type manager's cache
   * - setting the base fields on the ViewsTestEntity class as a static property
   *   for its baseFieldsDefinitions() method to use.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $definition
   *   An entity type definition to add to the entity type manager.
   * @param \Drupal\Core\Field\BaseFieldDefinition[] $base_fields
   *   An array of base field definitions
   */
  protected function setUpEntityType(EntityTypeInterface $definition, array $base_fields = []) {
    // Replace the cache backend in the entity type manager so it returns
    // our test entity type in addition to the existing ones.
    $definitions = $this->entityTypeManager->getDefinitions();
    $definitions[$definition->id()] = $definition;

    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $cache_data = new \StdClass();
    $cache_data->data = $definitions;
    $cache_backend->get('entity_type')->willReturn($cache_data);
    $this->entityTypeManager->setCacheBackend($cache_backend->reveal(), 'entity_type', ['entity_types']);
    $this->entityTypeManager->clearCachedDefinitions();

    if ($base_fields) {
      ViewsTestEntity::setMockedBaseFieldDefinitions($definition->id(), $base_fields);
    }
  }

  /**
   * Tests base tables.
   */
  public function testBaseTables() {
    $data = $this->entityTypeManager->getHandler('entity_test', 'views_data')->getViewsData();

    $this->assertEquals('entity_test', $data['entity_test']['table']['entity type']);
    $this->assertEquals(FALSE, $data['entity_test']['table']['entity revision']);
    $this->assertEquals('Entity test', $data['entity_test']['table']['group']);
    $this->assertEquals('entity_test', $data['entity_test']['table']['provider']);

    $this->assertEquals('id', $data['entity_test']['table']['base']['field']);
    $this->assertEquals(['entity_test_list_cache_context'], $data['entity_test']['table']['base']['cache_contexts']);
    $this->assertEquals('Entity test', $data['entity_test']['table']['base']['title']);

    // TODO: change these to assertArrayNotHasKey().
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

    $this->setUpEntityType($entity_type);

    // Tests the join definition between the base and the data table.
    $data = $this->entityTypeManager->getHandler('entity_test_mul', 'views_data')->getViewsData();
    // TODO: change the base table in the entity type definition to match the
    // changed entity ID.
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

    $this->setUpEntityType($entity_type);

    $data = $this->entityTypeManager->getHandler('entity_test_mulrev', 'views_data')->getViewsData();

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
    $this->setUpEntityType($entity_type);

    $data = $this->entityTypeManager->getHandler('entity_test_mulrev', 'views_data')->getViewsData();

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
    $this->setUpEntityType($entity_type);

    $data = $this->entityTypeManager->getHandler('entity_test_mulrev', 'views_data')->getViewsData();

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
   * Tests fields on the base table.
   */
  public function testBaseTableFields() {
    $data = $this->entityTypeManager->getHandler('entity_test', 'views_data')->getViewsData();

    $this->assertNumericField($data['entity_test']['id']);
    $this->assertViewsDataField($data['entity_test']['id'], 'id');
    $this->assertUuidField($data['entity_test']['uuid']);
    $this->assertViewsDataField($data['entity_test']['uuid'], 'uuid');
    $this->assertStringField($data['entity_test']['type']);
    $this->assertEquals('type', $data['entity_test']['type']['entity field']);

    $this->assertLanguageField($data['entity_test']['langcode']);
    $this->assertViewsDataField($data['entity_test']['langcode'], 'langcode');
    $this->assertEquals('Original language', $data['entity_test']['langcode']['title']);

    $this->assertStringField($data['entity_test']['name']);
    $this->assertViewsDataField($data['entity_test']['name'], 'name');

    $this->assertLongTextField($data['entity_test'], 'description');
    $this->assertViewsDataField($data['entity_test']['description__value'], 'description');
    $this->assertViewsDataField($data['entity_test']['description__format'], 'description');

    $this->assertUriField($data['entity_test']['homepage']);
    $this->assertViewsDataField($data['entity_test']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test']['user_id']);
    $this->assertViewsDataField($data['entity_test']['user_id'], 'user_id');

    $relationship = $data['entity_test']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    // The string field name should be used as the 'entity field' but the actual
    // field should reflect what the column mapping is using for multi-value
    // base fields NOT just the field name. The actual column name returned from
    // mappings in the test mocks is 'value'.
    $this->assertStringField($data['entity_test__string']['string_value']);
    $this->assertViewsDataField($data['entity_test__string']['string_value'], 'string');
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
    $entity_test_type = new ConfigEntityType([
      'class' => ConfigEntityBase::class,
      'id' => 'entity_test_bundle',
      'entity_keys' => [
        'id' => 'type',
        'label' => 'name',
      ],
    ]);
    $this->setUpEntityType($entity_test_type);

    $entity_type = $this->baseEntityType
      ->set('data_table', 'entity_test_mul_property_data')
      ->set('base_table', 'entity_test_mul')
      ->set('translatable', TRUE)
      ->set('id', 'entity_test_mul')
      ->set('bundle_entity_type', 'entity_test_bundle')
      ->setKey('bundle', 'type');

    $base_field_definitions = $this->commonBaseFields;
    $base_field_definitions['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('entity test type')
      ->setSetting('target_type', 'entity_test_bundle');

    $this->setUpEntityType($entity_type, $base_field_definitions);

    $data = $this->entityTypeManager->getHandler('entity_test_mul', 'views_data')->getViewsData();

    // Check the base fields.
    $this->assertFalse(isset($data['entity_test_mul']['id']));
    $this->assertFalse(isset($data['entity_test_mul']['type']));
    $this->assertUuidField($data['entity_test_mul']['uuid']);
    $this->assertViewsDataField($data['entity_test_mul']['uuid'], 'uuid');

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
    $this->assertViewsDataField($data['entity_test_mul_property_data']['id'], 'id');

    $this->assertBundleField($data['entity_test_mul_property_data']['type']);
    $this->assertViewsDataField($data['entity_test_mul_property_data']['type'], 'type');

    $this->assertLanguageField($data['entity_test_mul_property_data']['langcode']);
    $this->assertViewsDataField($data['entity_test_mul_property_data']['langcode'], 'langcode');
    $this->assertEquals('Translation language', $data['entity_test_mul_property_data']['langcode']['title']);

    $this->assertStringField($data['entity_test_mul_property_data']['name']);
    $this->assertViewsDataField($data['entity_test_mul_property_data']['name'], 'name');

    $this->assertLongTextField($data['entity_test_mul_property_data'], 'description');
    $this->assertViewsDataField($data['entity_test_mul_property_data']['description__value'], 'description');
    $this->assertViewsDataField($data['entity_test_mul_property_data']['description__format'], 'description');

    $this->assertUriField($data['entity_test_mul_property_data']['homepage']);
    $this->assertViewsDataField($data['entity_test_mul_property_data']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test_mul_property_data']['user_id']);
    $this->assertViewsDataField($data['entity_test_mul_property_data']['user_id'], 'user_id');
    $relationship = $data['entity_test_mul_property_data']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    $this->assertStringField($data['entity_test_mul__string']['string_value']);
    $this->assertViewsDataField($data['entity_test_mul__string']['string_value'], 'string');
    $this->assertEquals([
      'left_field' => 'id',
      'field' => 'entity_id',
      'extra' => [[
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ], $data['entity_test_mul__string']['table']['join']['entity_test_mul_property_data']);
  }

  /**
   * Tests fields on the revision table.
   */
  public function testRevisionTableFields() {
    $entity_type = $this->baseEntityType
      ->set('id', 'entity_test_mulrev')
      ->set('base_table', 'entity_test_mulrev')
      ->set('revision_table', 'entity_test_mulrev_revision')
      ->set('data_table', 'entity_test_mulrev_property_data')
      ->set('revision_data_table', 'entity_test_mulrev_property_revision')
      ->set('translatable', TRUE);

    $base_field_definitions = $this->commonBaseFields;

    $base_field_definitions['name']->setRevisionable(TRUE);
    $base_field_definitions['description']->setRevisionable(TRUE);
    $base_field_definitions['homepage']->setRevisionable(TRUE);
    $base_field_definitions['user_id']->setRevisionable(TRUE);

    $base_field_definitions['non_rev_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non Revisionable Field'))
      ->setDescription(t('A non-revisionable test field.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    $base_field_definitions['non_mul_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Non translatable'))
      ->setDescription(t('A non-translatable string field'))
      ->setRevisionable(TRUE);

    $this->setUpEntityType($entity_type, $base_field_definitions);

    $data = $this->entityTypeManager->getHandler('entity_test_mulrev', 'views_data')->getViewsData();

    // Check the base fields.
    $this->assertFalse(isset($data['entity_test_mulrev']['id']));
    $this->assertFalse(isset($data['entity_test_mulrev']['type']));
    $this->assertFalse(isset($data['entity_test_mulrev']['revision_id']));
    $this->assertUuidField($data['entity_test_mulrev']['uuid']);
    $this->assertViewsDataField($data['entity_test_mulrev']['uuid'], 'uuid');

    // Also ensure that field_data only fields don't appear on the base table.
    $this->assertFalse(isset($data['entity_test_mulrev']['name']));
    $this->assertFalse(isset($data['entity_test_mul']['description']));
    $this->assertFalse(isset($data['entity_test_mul']['description__value']));
    $this->assertFalse(isset($data['entity_test_mul']['description__format']));
    $this->assertFalse(isset($data['entity_test_mul']['homepage']));
    // $this->assertFalse(isset($data['entity_test_mulrev']['langcode']));
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
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['id'], 'id');
    $this->assertNumericField($data['entity_test_mulrev_property_data']['revision_id']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['revision_id'], 'revision_id');
    $this->assertLanguageField($data['entity_test_mulrev_property_data']['langcode']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['langcode'], 'langcode');
    $this->assertStringField($data['entity_test_mulrev_property_data']['name']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['name'], 'name');

    $this->assertLongTextField($data['entity_test_mulrev_property_data'], 'description');
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['description__value'], 'description');
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['description__format'], 'description');
    $this->assertUriField($data['entity_test_mulrev_property_data']['homepage']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test_mulrev_property_data']['user_id']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_data']['user_id'], 'user_id');
    $relationship = $data['entity_test_mulrev_property_data']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    // Check the property data fields.
    $this->assertNumericField($data['entity_test_mulrev_property_revision']['id']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['id'], 'id');

    $this->assertLanguageField($data['entity_test_mulrev_property_revision']['langcode']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['langcode'], 'langcode');
    $this->assertEquals('Translation language', $data['entity_test_mulrev_property_revision']['langcode']['title']);

    $this->assertStringField($data['entity_test_mulrev_property_revision']['name']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['name'], 'name');

    $this->assertLongTextField($data['entity_test_mulrev_property_revision'], 'description');
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['description__value'], 'description');
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['description__format'], 'description');

    $this->assertUriField($data['entity_test_mulrev_property_revision']['homepage']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['homepage'], 'homepage');

    $this->assertEntityReferenceField($data['entity_test_mulrev_property_revision']['user_id']);
    $this->assertViewsDataField($data['entity_test_mulrev_property_revision']['user_id'], 'user_id');
    $relationship = $data['entity_test_mulrev_property_revision']['user_id']['relationship'];
    $this->assertEquals('users_field_data', $relationship['base']);
    $this->assertEquals('uid', $relationship['base field']);

    $this->assertStringField($data['entity_test_mulrev__string']['string_value']);
    $this->assertViewsDataField($data['entity_test_mulrev__string']['string_value'], 'string');
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

    $this->assertStringField($data['entity_test_mulrev_revision__string']['string_value']);
    $this->assertViewsDataField($data['entity_test_mulrev_revision__string']['string_value'], 'string');
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
  protected function assertViewsDataField($data, $field_name) {
    $this->assertEquals($field_name, $data['entity field']);
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
   * Tests views data for an entity reference field.
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

}

/**
 * Entity type class which allows changing the entity keys.
 */
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

/**
 * Generic entity class for our test entity types.
 *
 * Allows mocked base field definitions.
 */
class ViewsTestEntity extends ContentEntityBase {

  /**
   * The mocked base fields for test entity types.
   *
   * An array keyed by entity type ID, whose values are arrays of base field
   * definitions.
   *
   * @var array
   */
  protected static $mockedBaseFieldDefinitions = [];

  /**
   * Sets up the mocked base field definitions.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $definitions
   *   The array of base field definitions to mock. These are added to the
   *   defaults ones from the parent class.
   */
  public static function setMockedBaseFieldDefinitions(string $entity_type_id, array $definitions) {
    static::$mockedBaseFieldDefinitions[$entity_type_id] = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    if (isset(static::$mockedBaseFieldDefinitions[$entity_type->id()])) {
      $mocked_fields = static::$mockedBaseFieldDefinitions[$entity_type->id()];
      // Mocked fields take priority over ones from the base class.
      $fields = $mocked_fields + $fields;
    }

    return $fields;
  }

}
