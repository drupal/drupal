<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests Drupal\Core\Entity\ContentEntityBase.
 */
#[CoversClass(ContentEntityBase::class)]
#[Group('Entity')]
#[Group('Access')]
class ContentEntityBaseUnitTest extends UnitTestCase {

  /**
   * The bundle of the entity under test.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity under test.
   */
  protected ContentEntityBaseMockableClass $entity;

  /**
   * An entity with no defined language to test.
   */
  protected ContentEntityBaseMockableClass $entityUnd;

  /**
   * The entity type used for testing.
   */
  protected EntityTypeInterface&Stub $entityType;

  /**
   * The entity field manager used for testing.
   */
  protected EntityFieldManagerInterface&MockObject $entityFieldManager;

  /**
   * The entity type bundle manager used for testing.
   */
  protected EntityTypeBundleInfoInterface&Stub $entityTypeBundleInfo;

  /**
   * The entity type manager used for testing.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The type ID of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The typed data manager used for testing.
   */
  protected TypedDataManagerInterface&Stub $typedDataManager;

  /**
   * The field type manager used for testing.
   */
  protected FieldTypePluginManager&MockObject $fieldTypePluginManager;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface&Stub $languageManager;

  /**
   * The UUID generator used for testing.
   */
  protected UuidInterface&Stub $uuid;

  /**
   * The entity ID.
   *
   * @var int
   */
  protected $id;

  /**
   * Field definitions.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected $fieldDefinitions;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->id = 1;
    $values = [
      'id' => $this->id,
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
      'defaultLangcode' => [LanguageInterface::LANGCODE_DEFAULT => 'en'],
    ];
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->entityType = $this->createStub(EntityTypeInterface::class);
    $this->entityType
      ->method('getKeys')
      ->willReturn([
        'id' => 'id',
        'uuid' => 'uuid',
      ]);
    $this->entityType
      ->method('getKey')
      ->willReturnMap([
        ['default_langcode', 'default_langcode'],
        ['id', 'id'],
        ['langcode', 'langcode'],
        ['revision', 'revision_id'],
        ['uuid', 'uuid'],
      ]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $this->entityTypeBundleInfo = $this->createStub(EntityTypeBundleInfoInterface::class);

    $this->uuid = $this->createStub(UuidInterface::class);

    $this->typedDataManager = $this->createStub(TypedDataManagerInterface::class);
    $this->typedDataManager
      ->method('getDefinition')
      ->willReturn(['class' => '\Drupal\Core\Entity\Plugin\DataType\EntityAdapter']);

    $this->languageManager = $this->createStub('\Drupal\Core\Language\LanguageManagerInterface');

    $this->fieldTypePluginManager = $this->getMockBuilder('\Drupal\Core\Field\FieldTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->fieldTypePluginManager->expects($this->atLeastOnce())
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager->expects($this->atLeastOnce())
      ->method('getDefaultFieldSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager
      ->method('createFieldItemList')
      ->willReturn($this->createStub(FieldItemListInterface::class));

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('entity_type.bundle.info', $this->entityTypeBundleInfo);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('language_manager', $this->languageManager);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    $this->fieldDefinitions = [
      'id' => BaseFieldDefinition::create('integer'),
      'revision_id' => BaseFieldDefinition::create('integer'),
    ];

    $this->entityFieldManager
      ->method('getFieldDefinitions')
      ->with($this->entityTypeId, $this->bundle)
      ->willReturn($this->fieldDefinitions);

    $this->entity = new ContentEntityBaseMockableClass($values, $this->entityTypeId, $this->bundle);
    $values['defaultLangcode'] = [LanguageInterface::LANGCODE_DEFAULT => LanguageInterface::LANGCODE_NOT_SPECIFIED];
    $this->entityUnd = new ContentEntityBaseMockableClass($values, $this->entityTypeId, $this->bundle);
  }

  /**
   * Reinitializes the entity type as a mock object.
   */
  protected function setUpMockEntityType(): void {
    $this->entityType = $this->createMock(EntityTypeInterface::class);
    $this->entityType
      ->method('getKeys')
      ->willReturn([
        'id' => 'id',
        'uuid' => 'uuid',
      ]);
    $this->entityType
      ->method('getKey')
      ->willReturnMap([
        ['default_langcode', 'default_langcode'],
        ['id', 'id'],
        ['langcode', 'langcode'],
        ['revision', 'revision_id'],
        ['uuid', 'uuid'],
      ]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);
    \Drupal::getContainer()->set('entity_type.manager', $this->entityTypeManager);
  }

  /**
   * Reinitializes the language manager as a mock object.
   */
  protected function setUpMockLanguageManager(): void {
    $english = new Language(['id' => 'en']);
    $not_specified = new Language(['id' => LanguageInterface::LANGCODE_NOT_SPECIFIED, 'locked' => TRUE]);
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager
      ->method('getLanguages')
      ->willReturn([
        'en' => $english,
        LanguageInterface::LANGCODE_NOT_SPECIFIED => $not_specified,
      ]);
    $this->languageManager
      ->method('getLanguage')
      ->with('en')
      ->willReturn($english);
    $this->languageManager
      ->method('getLanguage')
      ->with(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->willReturn($not_specified);
    \Drupal::getContainer()->set('language_manager', $this->languageManager);
  }

  protected function setUpMockTypedDataManager(): void {
    $this->typedDataManager = $this->createMock(TypedDataManagerInterface::class);
    $this->typedDataManager
      ->method('getDefinition')
      ->willReturn(['class' => '\Drupal\Core\Entity\Plugin\DataType\EntityAdapter']);
    \Drupal::getContainer()->set('typed_data_manager', $this->typedDataManager);
  }

  /**
   * Tests is new revision.
   *
   * @legacy-covers ::isNewRevision
   * @legacy-covers ::setNewRevision
   */
  public function testIsNewRevision(): void {
    $this->setUpMockEntityType();
    // Set up the entity type so that on the first call there is no revision key
    // and on the second call there is one.
    $this->entityType->expects($this->exactly(4))
      ->method('hasKey')
      ->with('revision')
      ->willReturnOnConsecutiveCalls(FALSE, TRUE, TRUE, TRUE);

    $field_item_list = $this->createStub(FieldItemListInterface::class);
    $field_item = new StubFieldItemBase();

    $this->fieldTypePluginManager
      ->method('createFieldItemList')
      ->with($this->entity, 'revision_id', NULL)
      ->willReturn($field_item_list);

    $this->fieldDefinitions['revision_id']->getItemDefinition()->setClass(get_class($field_item));

    $this->assertFalse($this->entity->isNewRevision());
    $this->assertTrue($this->entity->isNewRevision());
    $this->entity->setNewRevision(TRUE);
    $this->assertTrue($this->entity->isNewRevision());
  }

  /**
   * Tests set new revision exception.
   */
  public function testSetNewRevisionException(): void {
    $this->setUpMockEntityType();
    $this->entityType->expects($this->once())
      ->method('hasKey')
      ->with('revision')
      ->willReturn(FALSE);
    $this->expectException('LogicException');
    $this->expectExceptionMessageIs('Entity type ' . $this->entityTypeId . ' does not support revisions.');
    $this->entity->setNewRevision();
  }

  /**
   * Tests is default revision.
   */
  public function testIsDefaultRevision(): void {
    $this->entity = $this->getMockBuilder(ContentEntityBaseMockableClass::class)
      ->setConstructorArgs([[], $this->entityTypeId, $this->bundle])
      ->onlyMethods(['isNew'])
      ->getMock();

    // The default value is TRUE.
    $this->assertTrue($this->entity->isDefaultRevision());
    // Change the default revision, verify that the old value is returned.
    $this->assertTrue($this->entity->isDefaultRevision(FALSE));
    // The last call changed the return value for this call.
    $this->assertFalse($this->entity->isDefaultRevision());
    // The revision for a new entity should always be the default revision.
    $this->entity->isDefaultRevision(TRUE);
    $this->entity->expects($this->once())
      ->method('isNew')
      ->willReturn(TRUE);
    $this->entity->isDefaultRevision(FALSE);
    $this->assertTrue($this->entity->isDefaultRevision());
  }

  /**
   * Tests get revision id.
   */
  public function testGetRevisionId(): void {
    // The default getRevisionId() implementation returns NULL.
    $this->assertNull($this->entity->getRevisionId());
  }

  /**
   * Tests is translatable.
   */
  public function testIsTranslatable(): void {
    $this->setUpMockLanguageManager();

    $this->entityTypeBundleInfo
      ->method('getBundleInfo')
      ->willReturn([
        $this->bundle => [
          'translatable' => TRUE,
        ],
      ]);
    $this->languageManager
      ->method('isMultilingual')
      ->willReturn(TRUE);
    $this->assertSame('en', $this->entity->language()->getId());
    $this->assertFalse($this->entity->language()->isLocked());
    $this->assertTrue($this->entity->isTranslatable());

    $this->assertSame(LanguageInterface::LANGCODE_NOT_SPECIFIED, $this->entityUnd->language()->getId());
    $this->assertTrue($this->entityUnd->language()->isLocked());
    $this->assertFalse($this->entityUnd->isTranslatable());
  }

  /**
   * Tests is translatable for monolingual.
   */
  public function testIsTranslatableForMonolingual(): void {
    $this->languageManager
      ->method('isMultilingual')
      ->willReturn(FALSE);
    $this->assertFalse($this->entity->isTranslatable());
  }

  /**
   * Tests pre save revision.
   */
  public function testPreSaveRevision(): void {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->createStub(EntityStorageInterface::class);
    $record = new \stdClass();
    // Our mocked entity->preSaveRevision() returns NULL, so assert that.
    $this->assertNull($this->entity->preSaveRevision($storage, $record));
  }

  /**
   * Data provider for the ::getTypedData() test.
   *
   * The following entity data definitions, the first two being derivatives of
   * the last definition, will be tested in order:
   *
   * 1. entity:$entity_type:$bundle
   * 2. entity:$entity_type
   * 3. entity
   *
   * @see \Drupal\Core\Entity\EntityBase::getTypedData()
   * @see \Drupal\Core\Entity\EntityBase::getTypedDataClass()
   * @see \Drupal\Core\Entity\Plugin\DataType\Deriver\EntityDeriver
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - A bool whether to provide a bundle-specific definition.
   *   - A bool whether to provide an entity type-specific definition.
   */
  public static function providerTestTypedData(): array {
    return [
      'Entity data definition derivative with entity type and bundle' => [
        TRUE,
        TRUE,
      ],
      'Entity data definition derivative with entity type' => [
        FALSE,
        TRUE,
      ],
      'Entity data definition' => [
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Tests each condition in EntityBase::getTypedData().
   *
   * @legacy-covers ::getTypedData
   */
  #[DataProvider('providerTestTypedData')]
  public function testTypedData(bool $bundle_typed_data_definition, bool $entity_type_typed_data_definition): void {
    $expected = EntityAdapter::class;

    $typedDataManager = $this->createMock(TypedDataManagerInterface::class);
    $typedDataManager->expects($this->once())
      ->method('getDefinition')
      ->willReturnMap([
        [
          "entity:{$this->entityTypeId}:{$this->bundle}", FALSE,
          $bundle_typed_data_definition ? ['class' => $expected] : NULL,
        ],
        [
          "entity:{$this->entityTypeId}", FALSE,
          $entity_type_typed_data_definition ? ['class' => $expected] : NULL,
        ],
        [
          'entity', TRUE,
          ['class' => $expected],
        ],
      ]);

    // Temporarily replace the appropriate services in the container.
    $container = \Drupal::getContainer();
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);

    $entity = new ContentEntityBaseMockableClass([], $this->entityTypeId, $this->bundle);

    // Assert that the returned data type is an instance of EntityAdapter.
    $this->assertInstanceOf($expected, $entity->getTypedData());
  }

  /**
   * Tests validate.
   */
  public function testValidate(): void {
    $this->setUpMockTypedDataManager();

    $validator = $this->createMock(ValidatorInterface::class);
    /** @var \Symfony\Component\Validator\ConstraintViolationList $empty_violation_list */
    $empty_violation_list = new ConstraintViolationList();
    $non_empty_violation_list = clone $empty_violation_list;
    $non_empty_violation_list->add($this->createStub(ConstraintViolationInterface::class));
    $validator->expects($this->exactly(2))
      ->method('validate')
      ->with($this->entity->getTypedData())
      ->willReturnOnConsecutiveCalls($empty_violation_list, $non_empty_violation_list);
    $this->typedDataManager->expects($this->exactly(2))
      ->method('getValidator')
      ->willReturn($validator);
    $this->assertCount(0, $this->entity->validate());
    $this->assertCount(1, $this->entity->validate());
  }

  /**
   * Tests required validation.
   *
   * @legacy-covers ::validate
   * @legacy-covers ::isValidationRequired
   * @legacy-covers ::setValidationRequired
   * @legacy-covers ::save
   * @legacy-covers ::preSave
   */
  public function testRequiredValidation(): void {
    $validator = $this->createMock(ValidatorInterface::class);
    /** @var \Symfony\Component\Validator\ConstraintViolationList $empty_violation_list */
    $empty_violation_list = new ConstraintViolationList();
    $validator->expects($this->once())
      ->method('validate')
      ->with($this->entity->getTypedData())
      ->willReturn($empty_violation_list);
    $this->typedDataManager
      ->method('getValidator')
      ->willReturn($validator);

    /** @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $storage */
    $storage = $this->createStub(EntityStorageInterface::class);
    $storage
      ->method('save')
      ->willReturnCallback(function (ContentEntityInterface $entity) use ($storage): void {
        $entity->preSave($storage);
      });

    $this->entityTypeManager
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    // Check that entities can be saved normally when validation is not
    // required.
    $this->assertFalse($this->entity->isValidationRequired());
    $this->entity->save();

    // Make validation required and check that if the entity is validated, it
    // can be saved normally.
    $this->entity->setValidationRequired(TRUE);
    $this->assertTrue($this->entity->isValidationRequired());
    $this->entity->validate();
    $this->entity->save();

    // Check that the "validated" status is reset after saving the entity and
    // that trying to save a non-validated entity when validation is required
    // results in an exception.
    $this->assertTrue($this->entity->isValidationRequired());
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessageIs('Entity validation is required, but was skipped.');
    $this->entity->save();
  }

  /**
   * Tests bundle.
   */
  public function testBundle(): void {
    $this->assertSame($this->bundle, $this->entity->bundle());
  }

  /**
   * Tests access.
   */
  public function testAccess(): void {
    $access = $this->createMock('\Drupal\Core\Entity\EntityAccessControlHandlerInterface');
    $operation = $this->randomMachineName();
    $access->expects($this->exactly(2))
      ->method('access')
      ->with($this->entity, $operation)
      ->willReturnOnConsecutiveCalls(TRUE, AccessResult::allowed());
    $access->expects($this->exactly(2))
      ->method('createAccess')
      ->willReturnOnConsecutiveCalls(TRUE, AccessResult::allowed());
    $this->entityTypeManager->expects($this->exactly(4))
      ->method('getAccessControlHandler')
      ->willReturn($access);
    $this->assertTrue($this->entity->access($operation));
    $this->assertEquals(AccessResult::allowed(), $this->entity->access($operation, NULL, TRUE));
    $this->assertTrue($this->entity->access('create'));
    $this->assertEquals(AccessResult::allowed(), $this->entity->access('create', NULL, TRUE));
  }

  /**
   * Data provider for testGet().
   *
   * @return array
   *   - Expected output from get().
   *   - Field name parameter to get().
   *   - Language code for $activeLanguage.
   *   - Fields array for $fields.
   */
  public static function providerGet(): array {
    return [
      // Populated fields array.
      ['result', 'field_name', 'langcode', ['field_name' => ['langcode' => 'result']]],
      // Incomplete fields array.
      ['getTranslatedField_result', 'field_name', 'langcode', ['field_name' => 'no_langcode']],
      // Empty fields array.
      ['getTranslatedField_result', 'field_name', 'langcode', []],
    ];
  }

  /**
   * Tests get.
   */
  #[DataProvider('providerGet')]
  public function testGet(string $expected, string $field_name, string $active_langcode, array $fields): void {
    // Mock ContentEntityBase.
    $mock_base = $this->getMockBuilder(ContentEntityBaseMockableClass::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getTranslatedField'])
      ->getMock();

    // Set up expectations for getTranslatedField() method. In get(),
    // getTranslatedField() is only called if the field name and language code
    // are not present as keys in the fields array.
    if (isset($fields[$field_name][$active_langcode])) {
      $mock_base->expects($this->never())
        ->method('getTranslatedField');
    }
    else {
      $mock_base->expects($this->once())
        ->method('getTranslatedField')
        ->with(
          $this->equalTo($field_name),
          $this->equalTo($active_langcode)
        )
        ->willReturn($expected);
    }

    // Poke in activeLangcode.
    $ref_langcode = new \ReflectionProperty($mock_base, 'activeLangcode');
    $ref_langcode->setValue($mock_base, $active_langcode);

    // Poke in fields.
    $ref_fields = new \ReflectionProperty($mock_base, 'fields');
    $ref_fields->setValue($mock_base, $fields);

    // Exercise get().
    $this->assertEquals($expected, $mock_base->get($field_name));
  }

  /**
   * Data provider for testGetFields().
   *
   * @return array
   *   - Expected output from getFields().
   *   - $include_computed value to pass to getFields().
   *   - Value to mock from all field definitions for isComputed().
   *   - Array of field names to return from mocked getFieldDefinitions(). A
   *     Drupal\Core\Field\FieldDefinitionInterface object will be mocked for
   *     each name.
   */
  public static function providerGetFields(): array {
    return [
      [[], FALSE, FALSE, []],
      [['field' => 'field', 'field2' => 'field2'], TRUE, FALSE, ['field', 'field2']],
      [['field3' => 'field3'], TRUE, TRUE, ['field3']],
      [[], FALSE, TRUE, ['field4']],
    ];
  }

  /**
   * Tests get fields.
   */
  #[DataProvider('providerGetFields')]
  public function testGetFields(array $expected, bool $include_computed, bool $is_computed, array $field_definitions): void {
    // Mock ContentEntityBase.
    $mock_base = $this->getMockBuilder(ContentEntityBaseMockableClass::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getFieldDefinitions', 'get'])
      ->getMock();

    // Mock field definition objects for each element of $field_definitions.
    $mocked_field_definitions = [];
    foreach ($field_definitions as $name) {
      $mock_definition = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
      // Set expectations for isComputed(). isComputed() gets called whenever
      // $include_computed is FALSE, but not otherwise. It returns the value of
      // $is_computed.
      $mock_definition->expects($this->exactly(
        $include_computed ? 0 : 1
        ))
        ->method('isComputed')
        ->willReturn($is_computed);
      $mocked_field_definitions[$name] = $mock_definition;
    }

    // Set up expectations for getFieldDefinitions().
    $mock_base->expects($this->once())
      ->method('getFieldDefinitions')
      ->willReturn($mocked_field_definitions);

    // How many time will we call get()? Since we are rigging all defined fields
    // to be computed based on $is_computed, then if $include_computed is FALSE,
    // get() will never be called.
    $get_count = 0;
    if ($include_computed) {
      $get_count = count($field_definitions);
    }

    // Set up expectations for get(). It simply returns the name passed in.
    $mock_base->expects($this->exactly($get_count))
      ->method('get')
      ->willReturnArgument(0);

    // Exercise getFields().
    $this->assertEquals(
      $expected,
      $mock_base->getFields($include_computed)
    );
  }

  /**
   * Tests set.
   */
  public function testSet(): void {
    // Exercise set(), check if it returns $this.
    $this->assertSame(
      $this->entity,
      $this->entity->set('id', 0)
    );
  }

}
