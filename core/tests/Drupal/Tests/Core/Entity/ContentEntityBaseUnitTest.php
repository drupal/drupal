<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\ContentEntityBaseUnitTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Language\Language;

/**
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityBase
 *
 * @group Drupal
 */
class ContentEntityBaseUnitTest extends UnitTestCase {

  /**
   * The bundle of the entity under test.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity under test.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The type ID of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The typed data manager used for testing.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedDataManager;

  /**
   * The field type manager used for testing.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $uuid;

  /**
   * The entity ID.
   *
   * @var int
   */
  protected $id;

  /**
   * Field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinition[]
   */
  protected $fieldDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Entity\ContentEntityBase unit test',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->id = 1;
    $values = array(
      'id' => $this->id,
      'langcode' => 'en',
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
    );
    $this->entityTypeId = $this->randomName();
    $this->bundle = $this->randomName();

    $this->entityType = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getKeys')
      ->will($this->returnValue(array(
        'id' => 'id',
        'uuid' => 'uuid',
    )));

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));

    $this->uuid = $this->getMock('\Drupal\Component\Uuid\UuidInterface');

    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

    $language = new Language(array('id' => 'en'));
    $this->languageManager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getLanguages')
      ->will($this->returnValue(array('en' => $language)));
    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with('en')
      ->will($this->returnValue($language));

    $this->fieldTypePluginManager = $this->getMockBuilder('\Drupal\Core\Field\FieldTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultSettings')
      ->will($this->returnValue(array()));
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultInstanceSettings')
      ->will($this->returnValue(array()));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('uuid', $this->uuid);
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('language_manager', $this->languageManager);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    $this->fieldDefinitions = array(
      'id' => FieldDefinition::create('integer'),
      'revision_id' => FieldDefinition::create('integer'),
    );

    $this->entityManager->expects($this->any())
      ->method('getFieldDefinitions')
      ->with($this->entityTypeId, $this->bundle)
      ->will($this->returnValue($this->fieldDefinitions));
    $this->entity = $this->getMockForAbstractClass('\Drupal\Core\Entity\ContentEntityBase', array($values, $this->entityTypeId, $this->bundle));
  }

  /**
   * @covers ::isNewRevision
   * @covers ::setNewRevision
   */
  public function testIsNewRevision() {
    // Set up the entity type so that on the first call there is no revision key
    // and on the second call there is one.
    $this->entityType->expects($this->at(0))
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(FALSE));
    $this->entityType->expects($this->at(1))
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->at(2))
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->at(3))
      ->method('getKey')
      ->with('revision')
      ->will($this->returnValue('revision_id'));
    $this->entityType->expects($this->at(4))
      ->method('hasKey')
      ->with('revision')
      ->will($this->returnValue(TRUE));
    $this->entityType->expects($this->at(5))
      ->method('getKey')
      ->with('revision')
      ->will($this->returnValue('revision_id'));

    $field_item_list = $this->getMockBuilder('\Drupal\Core\Field\FieldItemList')
      ->disableOriginalConstructor()
      ->getMock();
    $field_item = $this->getMockBuilder('\Drupal\Core\Field\FieldItemBase')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->entity, 'revision_id', NULL)
      ->will($this->returnValue($field_item_list));

    $this->fieldDefinitions['revision_id']->getItemDefinition()->setClass(get_class($field_item));

    $this->assertFalse($this->entity->isNewRevision());
    $this->assertTrue($this->entity->isNewRevision());
    $this->entity->setNewRevision(TRUE);
    $this->assertTRUE($this->entity->isNewRevision());
  }

  /**
   * @covers ::isDefaultRevision
   */
  public function testIsDefaultRevision() {
    // The default value is TRUE.
    $this->assertTrue($this->entity->isDefaultRevision());
    // Change the default revision, verify that the old value is returned.
    $this->assertTrue($this->entity->isDefaultRevision(FALSE));
    // The last call changed the return value for this call.
    $this->assertFalse($this->entity->isDefaultRevision());
  }

  /**
   * @covers ::getRevisionId
   */
  public function testGetRevisionId() {
    // The default getRevisionId() implementation returns NULL.
    $this->assertNull($this->entity->getRevisionId());
  }

  /**
   * @covers ::isTranslatable
   */
  public function testIsTranslatable() {
    $this->entityManager->expects($this->at(0))
      ->method('getBundleInfo')
      ->with($this->entityTypeId)
      ->will($this->returnValue(array(
        $this->bundle => array(
          'translatable' => TRUE,
        ),
      )));
    $this->assertTrue($this->entity->isTranslatable());
    $this->assertFalse($this->entity->isTranslatable());
  }

  /**
   * @covers ::preSaveRevision
   */
  public function testPreSaveRevision() {
    // This method is internal, so check for errors on calling it only.
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $record = new \stdClass();
    $this->entity->preSaveRevision($storage, $record);
  }

  /**
   * @covers ::getString
   */
  public function testGetString() {
    $label = $this->randomName();
    /** @var \Drupal\Core\Entity\ContentEntityBase|\PHPUnit_Framework_MockObject_MockObject $entity */
    $entity = $this->getMockBuilder('\Drupal\Core\Entity\ContentEntityBase')
      ->setMethods(array('label'))
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $entity->expects($this->once())
      ->method('label')
      ->will($this->returnValue($label));

    $this->assertSame($label, $entity->getString());
  }

  /**
   * @covers ::validate
   */
  public function testValidate() {
    $validator = $this->getMock('\Symfony\Component\Validator\ValidatorInterface');
    /** @var \Symfony\Component\Validator\ConstraintViolationList|\PHPUnit_Framework_MockObject_MockObject $empty_violation_list */
    $empty_violation_list = $this->getMockBuilder('\Symfony\Component\Validator\ConstraintViolationList')
      ->setMethods(NULL)
      ->getMock();
    $non_empty_violation_list = clone $empty_violation_list;
    $violation = $this->getMock('\Symfony\Component\Validator\ConstraintViolationInterface');
    $non_empty_violation_list->add($violation);
    $validator->expects($this->at(0))
      ->method('validate')
      ->with($this->entity)
      ->will($this->returnValue($empty_violation_list));
    $validator->expects($this->at(1))
      ->method('validate')
      ->with($this->entity)
      ->will($this->returnValue($non_empty_violation_list));
    $this->typedDataManager->expects($this->exactly(2))
      ->method('getValidator')
      ->will($this->returnValue($validator));
    $this->assertSame(0, count($this->entity->validate()));
    $this->assertSame(1, count($this->entity->validate()));
  }

  /**
   * @covers ::getConstraints
   */
  public function testGetConstraints() {
    $this->assertInternalType('array', $this->entity->getConstraints());
  }

  /**
   * @covers ::getName
   */
  public function testGetName() {
    $this->assertNull($this->entity->getName());
  }

  /**
   * @covers ::getRoot
   */
  public function testGetRoot() {
    $this->assertSame(spl_object_hash($this->entity), spl_object_hash($this->entity->getRoot()));
  }

  /**
   * @covers ::getPropertyPath
   */
  public function testGetPropertyPath() {
    $this->assertSame('', $this->entity->getPropertyPath());
  }

  /**
   * @covers ::getParent
   */
  public function testGetParent() {
    $this->assertNull($this->entity->getParent());
  }

  /**
   * @covers ::setContext
   */
  public function testSetContext() {
    $name = $this->randomName();
    $parent = $this->getMock('\Drupal\Core\TypedData\TypedDataInterface');
    $this->entity->setContext($name, $parent);
  }

  /**
   * @covers ::bundle
   */
  public function testBundle() {
    $this->assertSame($this->bundle, $this->entity->bundle());
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $access = $this->getMock('\Drupal\Core\Entity\EntityAccessControllerInterface');
    $operation = $this->randomName();
    $access->expects($this->at(0))
      ->method('access')
      ->with($this->entity, $operation)
      ->will($this->returnValue(TRUE));
    $access->expects($this->at(1))
      ->method('createAccess')
      ->will($this->returnValue(TRUE));
    $this->entityManager->expects($this->exactly(2))
      ->method('getAccessController')
      ->will($this->returnValue($access));
    $this->assertTrue($this->entity->access($operation));
    $this->assertTrue($this->entity->access('create'));
  }

  /**
   * @covers ::label
   */
  public function testLabel() {
    // Make a mock with one method that we use as the entity's label callback.
    // We check that it is called, and that the entity's label is the callback's
    // return value.
    $callback_label = $this->randomName();
    $callback_container = $this->getMock(get_class());
    $callback_container->expects($this->once())
      ->method(__FUNCTION__)
      ->will($this->returnValue($callback_label));
    $this->entityType->expects($this->once())
      ->method('getLabelCallback')
      ->will($this->returnValue(array($callback_container, __FUNCTION__)));

    $this->assertSame($callback_label, $this->entity->label());
  }
}
