<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityType
 * @group Entity
 */
class EntityTypeTest extends UnitTestCase {

  /**
   * Sets up an EntityType object for a given set of values.
   *
   * @param array $definition
   *   An array of values to use for the EntityType.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  protected function setUpEntityType($definition) {
    $definition += [
      'id' => 'example_entity_type',
    ];
    return new EntityType($definition);
  }

  /**
   * @covers ::get
   *
   * @dataProvider providerTestGet
   */
  public function testGet(array $defintion, $key, $expected) {
    $entity_type = $this->setUpEntityType($defintion);
    $this->assertSame($expected, $entity_type->get($key));
  }

  /**
   * @covers ::set
   * @covers ::get
   *
   * @dataProvider providerTestSet
   */
  public function testSet($key, $value) {
    $entity_type = $this->setUpEntityType([]);
    $this->assertInstanceOf('Drupal\Core\Entity\EntityTypeInterface', $entity_type->set($key, $value));
    $this->assertSame($value, $entity_type->get($key));
    $this->assertNoPublicProperties($entity_type);
  }

  /**
   * Tests the getKeys() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testGetKeys($entity_keys, $expected) {
    $entity_type = $this->setUpEntityType(['entity_keys' => $entity_keys]);
    $expected += [
      'default_langcode' => 'default_langcode',
      'revision_translation_affected' => 'revision_translation_affected',
    ];
    $this->assertSame($expected, $entity_type->getKeys());
  }

  /**
   * Tests the getKey() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testGetKey($entity_keys, $expected) {
    $entity_type = $this->setUpEntityType(['entity_keys' => $entity_keys]);
    $this->assertSame($expected['bundle'], $entity_type->getKey('bundle'));
    $this->assertSame(FALSE, $entity_type->getKey('bananas'));
  }

  /**
   * Tests the hasKey() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testHasKey($entity_keys, $expected) {
    $entity_type = $this->setUpEntityType(['entity_keys' => $entity_keys]);
    $this->assertSame(!empty($expected['bundle']), $entity_type->hasKey('bundle'));
    $this->assertSame(!empty($expected['id']), $entity_type->hasKey('id'));
    $this->assertSame(FALSE, $entity_type->hasKey('bananas'));
  }

  /**
   * Provides test data for testGet.
   */
  public function providerTestGet() {
    return [
      [[], 'provider', NULL],
      [['provider' => ''], 'provider', ''],
      [['provider' => 'test'], 'provider', 'test'],
      [[], 'something_additional', NULL],
      [['something_additional' => ''], 'something_additional', ''],
      [['something_additional' => 'additional'], 'something_additional', 'additional'],
    ];
  }

  /**
   * Provides test data for testSet.
   */
  public function providerTestSet() {
    return [
      ['provider', NULL],
      ['provider', ''],
      ['provider', 'test'],
      ['something_additional', NULL],
      ['something_additional', ''],
      ['something_additional', 'additional'],
    ];
  }

  /**
   * Provides test data.
   */
  public function providerTestGetKeys() {
    return [
      [[], ['revision' => '', 'bundle' => '', 'langcode' => '']],
      [['id' => 'id'], ['id' => 'id', 'revision' => '', 'bundle' => '', 'langcode' => '']],
      [['bundle' => 'bundle'], ['bundle' => 'bundle', 'revision' => '', 'langcode' => '']],
    ];
  }

  /**
   * Tests the isInternal() method.
   */
  public function testIsInternal() {
    $entity_type = $this->setUpEntityType(['internal' => TRUE]);
    $this->assertTrue($entity_type->isInternal());
    $entity_type = $this->setUpEntityType(['internal' => FALSE]);
    $this->assertFalse($entity_type->isInternal());
    $entity_type = $this->setUpEntityType([]);
    $this->assertFalse($entity_type->isInternal());
  }

  /**
   * Tests the isRevisionable() method.
   */
  public function testIsRevisionable() {
    $entity_type = $this->setUpEntityType(['entity_keys' => ['id' => 'id']]);
    $this->assertFalse($entity_type->isRevisionable());
    $entity_type = $this->setUpEntityType(['entity_keys' => ['id' => 'id', 'revision' => FALSE]]);
    $this->assertFalse($entity_type->isRevisionable());
    $entity_type = $this->setUpEntityType(['entity_keys' => ['id' => 'id', 'revision' => TRUE]]);
    $this->assertTrue($entity_type->isRevisionable());
  }

  /**
   * Tests the getHandler() method.
   */
  public function testGetHandler() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([
      'handlers' => [
        'storage' => $controller,
        'form' => [
          'default' => $controller,
        ],
      ],
    ]);
    $this->assertSame($controller, $entity_type->getHandlerClass('storage'));
    $this->assertSame($controller, $entity_type->getHandlerClass('form', 'default'));
    $this->assertNull($entity_type->getHandlerClass('foo'));
    $this->assertNull($entity_type->getHandlerClass('foo', 'bar'));
  }

  /**
   * Tests the getStorageClass() method.
   */
  public function testGetStorageClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([
      'handlers' => [
        'storage' => $controller,
      ],
    ]);
    $this->assertSame($controller, $entity_type->getStorageClass());
  }

  /**
   * Tests the setStorageClass() method.
   */
  public function testSetStorageClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([]);
    $this->assertSame($entity_type, $entity_type->setStorageClass($controller));
  }

  /**
   * Tests the getListBuilderClass() method.
   */
  public function testGetListBuilderClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([
      'handlers' => [
        'list_builder' => $controller,
      ],
    ]);
    $this->assertSame($controller, $entity_type->getListBuilderClass());
  }

  /**
   * Tests the getAccessControlClass() method.
   */
  public function testGetAccessControlClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([
      'handlers' => [
        'access' => $controller,
      ],
    ]);
    $this->assertSame($controller, $entity_type->getAccessControlClass());
  }

  /**
   * Tests the getFormClass() method.
   */
  public function testGetFormClass() {
    $controller = $this->getTestHandlerClass();
    $operation = 'default';
    $entity_type = $this->setUpEntityType([
      'handlers' => [
        'form' => [
          $operation => $controller,
        ],
      ],
    ]);
    $this->assertSame($controller, $entity_type->getFormClass($operation));
  }

  /**
   * Tests the hasFormClasses() method.
   */
  public function testHasFormClasses() {
    $controller = $this->getTestHandlerClass();
    $operation = 'default';
    $entity_type1 = $this->setUpEntityType([
      'handlers' => [
        'form' => [
          $operation => $controller,
        ],
      ],
    ]);
    $entity_type2 = $this->setUpEntityType([
      'handlers' => [],
    ]);
    $this->assertTrue($entity_type1->hasFormClasses());
    $this->assertFalse($entity_type2->hasFormClasses());
  }

  /**
   * Tests the getViewBuilderClass() method.
   */
  public function testGetViewBuilderClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([
      'handlers' => [
        'view_builder' => $controller,
      ],
    ]);
    $this->assertSame($controller, $entity_type->getViewBuilderClass());
  }

  /**
   * @covers ::__construct
   */
  public function testIdExceedsMaxLength() {
    $id = $this->randomMachineName(33);
    $message = 'Attempt to create an entity type with an ID longer than 32 characters: ' . $id;
    $this->expectException('Drupal\Core\Entity\Exception\EntityTypeIdLengthException');
    $this->expectExceptionMessage($message);
    $this->setUpEntityType(['id' => $id]);
  }

  /**
   * @covers ::getOriginalClass
   */
  public function testgetOriginalClassUnchanged() {
    $class = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(['class' => $class]);
    $this->assertEquals($class, $entity_type->getOriginalClass());
  }

  /**
   * @covers ::setClass
   * @covers ::getOriginalClass
   */
  public function testgetOriginalClassChanged() {
    $class = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(['class' => $class]);
    $entity_type->setClass($this->randomMachineName());
    $this->assertEquals($class, $entity_type->getOriginalClass());
  }

  /**
   * @covers ::id
   */
  public function testId() {
    $id = $this->randomMachineName(32);
    $entity_type = $this->setUpEntityType(['id' => $id]);
    $this->assertEquals($id, $entity_type->id());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $translatable_label = new TranslatableMarkup($this->randomMachineName());
    $entity_type = $this->setUpEntityType(['label' => $translatable_label]);
    $this->assertSame($translatable_label, $entity_type->getLabel());

    $label = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(['label' => $label]);
    $this->assertSame($label, $entity_type->getLabel());
  }

  /**
   * @covers ::getGroupLabel
   */
  public function testGetGroupLabel() {
    $translatable_group_label = new TranslatableMarkup($this->randomMachineName());
    $entity_type = $this->setUpEntityType(['group_label' => $translatable_group_label]);
    $this->assertSame($translatable_group_label, $entity_type->getGroupLabel());

    $default_label = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(['group_label' => $default_label]);
    $this->assertSame($default_label, $entity_type->getGroupLabel());

    $default_label = new TranslatableMarkup('Other', [], ['context' => 'Entity type group']);
    $entity_type = $this->setUpEntityType(['group_label' => $default_label]);
    $this->assertSame($default_label, $entity_type->getGroupLabel());
  }

  /**
   * @covers ::getCollectionLabel
   */
  public function testGetCollectionLabel() {
    $translatable_label = new TranslatableMarkup('Entity test collection', [], [], $this->getStringTranslationStub());
    $entity_type = $this->setUpEntityType(['label_collection' => $translatable_label]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('Entity test collection', $entity_type->getCollectionLabel());
  }

  /**
   * @covers ::getSingularLabel
   */
  public function testGetSingularLabel() {
    $translatable_label = new TranslatableMarkup('entity test singular', [], [], $this->getStringTranslationStub());
    $entity_type = $this->setUpEntityType(['label_singular' => $translatable_label]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test singular', $entity_type->getSingularLabel());
  }

  /**
   * @covers ::getSingularLabel
   */
  public function testGetSingularLabelDefault() {
    $entity_type = $this->setUpEntityType(['label' => 'Entity test Singular']);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test singular', $entity_type->getSingularLabel());
  }

  /**
   * @covers ::getPluralLabel
   */
  public function testGetPluralLabel() {
    $translatable_label = new TranslatableMarkup('entity test plural', [], [], $this->getStringTranslationStub());
    $entity_type = $this->setUpEntityType(['label_plural' => $translatable_label]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test plural', $entity_type->getPluralLabel());
  }

  /**
   * @covers ::getPluralLabel
   */
  public function testGetPluralLabelDefault() {
    $entity_type = $this->setUpEntityType(['label' => 'Entity test Plural']);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test plural entities', $entity_type->getPluralLabel());
  }

  /**
   * @covers ::getCountLabel
   */
  public function testGetCountLabel() {
    $entity_type = $this->setUpEntityType(['label_count' => ['singular' => 'one entity test', 'plural' => '@count entity test']]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('one entity test', $entity_type->getCountLabel(1));
    $this->assertEquals('2 entity test', $entity_type->getCountLabel(2));
    $this->assertEquals('200 entity test', $entity_type->getCountLabel(200));
  }

  /**
   * @covers ::getCountLabel
   */
  public function testGetCountLabelDefault() {
    $entity_type = $this->setUpEntityType(['label' => 'Entity test Plural']);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('1 entity test plural', $entity_type->getCountLabel(1));
    $this->assertEquals('2 entity test plural entities', $entity_type->getCountLabel(2));
    $this->assertEquals('200 entity test plural entities', $entity_type->getCountLabel(200));
  }

  /**
   * Tests the ::getBundleLabel() method.
   *
   * @covers ::getBundleLabel
   * @dataProvider providerTestGetBundleLabel
   */
  public function testGetBundleLabel($definition, $expected) {
    $entity_type = $this->setUpEntityType($definition);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals($expected, $entity_type->getBundleLabel());
  }

  /**
   * Provides test data for ::testGetBundleLabel().
   */
  public function providerTestGetBundleLabel() {
    return [
      [['label' => 'Entity Label Foo'], 'Entity Label Foo bundle'],
      [['bundle_label' => 'Bundle Label Bar'], 'Bundle Label Bar'],
    ];
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestHandlerClass() {
    return get_class($this->getMockForAbstractClass('Drupal\Core\Entity\EntityHandlerBase'));
  }

  /**
   * @covers ::setLinkTemplate
   */
  public function testSetLinkTemplateWithInvalidPath() {
    $entity_type = $this->setUpEntityType(['id' => $this->randomMachineName()]);
    $this->expectException(\InvalidArgumentException::class);
    $entity_type->setLinkTemplate('test', 'invalid-path');
  }

  /**
   * Tests the constraint methods.
   *
   * @covers ::getConstraints
   * @covers ::setConstraints
   * @covers ::addConstraint
   */
  public function testConstraintMethods() {
    $definition = [
      'constraints' => [
        'EntityChanged' => [],
      ],
    ];
    $entity_type = $this->setUpEntityType($definition);
    $this->assertEquals($definition['constraints'], $entity_type->getConstraints());
    $entity_type->addConstraint('Test');
    $this->assertEquals($definition['constraints'] + ['Test' => NULL], $entity_type->getConstraints());
    $entity_type->setConstraints([]);
    $this->assertEquals([], $entity_type->getConstraints());
  }

  /**
   * Asserts there on no public properties on the object instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   */
  protected function assertNoPublicProperties(EntityTypeInterface $entity_type) {
    $reflection = new \ReflectionObject($entity_type);
    $this->assertEmpty($reflection->getProperties(\ReflectionProperty::IS_PUBLIC));
  }

  /**
   * @covers ::entityClassImplements
   */
  public function testEntityClassImplements() {
    $entity_type = $this->setUpEntityType(['class' => EntityFormMode::class]);
    $this->assertSame(TRUE, $entity_type->entityClassImplements(ConfigEntityInterface::class));
    $this->assertSame(FALSE, $entity_type->entityClassImplements(\DateTimeInterface::class));
  }

  /**
   * @covers ::isSubclassOf
   * @group legacy
   * @expectedDeprecation Drupal\Core\Entity\EntityType::isSubclassOf() is deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use Drupal\Core\Entity\EntityTypeInterface::entityClassImplements() instead. See https://www.drupal.org/node/2842808
   */
  public function testIsSubClassOf() {
    $entity_type = $this->setUpEntityType(['class' => EntityFormMode::class]);
    $this->assertSame(TRUE, $entity_type->isSubclassOf(ConfigEntityInterface::class));
    $this->assertSame(FALSE, $entity_type->isSubclassOf(\DateTimeInterface::class));
  }

  /**
   * Tests that the EntityType object can be serialized.
   */
  public function testIsSerializable() {
    $entity_type = $this->setUpEntityType([]);

    $translation = $this->prophesize(TranslationInterface::class);
    $translation->willImplement(\Serializable::class);
    $translation->serialize()->willThrow(\Exception::class);
    $translation_service = $translation->reveal();
    $translation_service->_serviceId = 'string_translation';

    $entity_type->setStringTranslation($translation_service);
    $entity_type = unserialize(serialize($entity_type));

    $this->assertEquals('example_entity_type', $entity_type->id());
  }

  /**
   * @covers ::getLabelCallback
   *
   * @group legacy
   *
   * @deprecatedMessage EntityType::getLabelCallback() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Override the EntityInterface::label() method instead for dynamic labels. See https://www.drupal.org/node/3050794
   */
  public function testGetLabelCallack() {
    $entity_type = $this->setUpEntityType(['label_callback' => 'label_function']);
    $this->assertSame('label_function', $entity_type->getLabelCallback());

    $entity_type = $this->setUpEntityType([]);
    $this->assertNull($entity_type->getLabelCallback());
  }

  /**
   * @covers ::setLabelCallback
   *
   * @group legacy
   *
   * @deprecatedMessage EntityType::setLabelCallback() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Override the EntityInterface::label() method instead for dynamic labels. See https://www.drupal.org/node/3050794
   */
  public function testSetLabelCallack() {
    $entity_type = $this->setUpEntityType([]);
    $entity_type->setLabelCallback('label_function');
    $this->assertSame('label_function', $entity_type->get('label_callback'));
  }

  /**
   * @covers ::hasLabelCallback
   *
   * @group legacy
   *
   * @deprecatedMessage EntityType::hasLabelCallback() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Override the EntityInterface::label() method instead for dynamic labels. See https://www.drupal.org/node/3050794
   */
  public function testHasLabelCallack() {
    $entity_type = $this->setUpEntityType(['label_callback' => 'label_function']);
    $this->assertTrue($entity_type->hasLabelCallback());

    $entity_type = $this->setUpEntityType([]);
    $this->assertFalse($entity_type->hasLabelCallback());
  }

}
