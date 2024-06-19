<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
  public function testGet(array $definition, $key, $expected): void {
    $entity_type = $this->setUpEntityType($definition);
    $this->assertSame($expected, $entity_type->get($key));
  }

  /**
   * @covers ::set
   * @covers ::get
   *
   * @dataProvider providerTestSet
   */
  public function testSet($key, $value): void {
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
  public function testGetKeys($entity_keys, $expected): void {
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
  public function testGetKey($entity_keys, $expected): void {
    $entity_type = $this->setUpEntityType(['entity_keys' => $entity_keys]);
    $this->assertSame($expected['bundle'], $entity_type->getKey('bundle'));
    $this->assertFalse($entity_type->getKey('bananas'));
  }

  /**
   * Tests the hasKey() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testHasKey($entity_keys, $expected): void {
    $entity_type = $this->setUpEntityType(['entity_keys' => $entity_keys]);
    $this->assertSame(!empty($expected['bundle']), $entity_type->hasKey('bundle'));
    $this->assertSame(!empty($expected['id']), $entity_type->hasKey('id'));
    $this->assertFalse($entity_type->hasKey('bananas'));
  }

  /**
   * Provides test data for testGet.
   */
  public static function providerTestGet() {
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
  public static function providerTestSet() {
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
  public static function providerTestGetKeys() {
    return [
      [[], ['revision' => '', 'bundle' => '', 'langcode' => '']],
      [['id' => 'id'], ['id' => 'id', 'revision' => '', 'bundle' => '', 'langcode' => '']],
      [['bundle' => 'bundle'], ['bundle' => 'bundle', 'revision' => '', 'langcode' => '']],
    ];
  }

  /**
   * Tests the isInternal() method.
   */
  public function testIsInternal(): void {
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
  public function testIsRevisionable(): void {
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
  public function testGetHandler(): void {
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
  public function testGetStorageClass(): void {
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
  public function testSetStorageClass(): void {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType([]);
    $this->assertSame($entity_type, $entity_type->setStorageClass($controller));
  }

  /**
   * Tests the getListBuilderClass() method.
   */
  public function testGetListBuilderClass(): void {
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
  public function testGetAccessControlClass(): void {
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
  public function testGetFormClass(): void {
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
  public function testHasFormClasses(): void {
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
  public function testGetViewBuilderClass(): void {
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
  public function testIdExceedsMaxLength(): void {
    $id = $this->randomMachineName(33);
    $message = 'Attempt to create an entity type with an ID longer than 32 characters: ' . $id;
    $this->expectException('Drupal\Core\Entity\Exception\EntityTypeIdLengthException');
    $this->expectExceptionMessage($message);
    $this->setUpEntityType(['id' => $id]);
  }

  /**
   * @covers ::getOriginalClass
   */
  public function testGetOriginalClassUnchanged(): void {
    $class = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(['class' => $class]);
    $this->assertEquals($class, $entity_type->getOriginalClass());
  }

  /**
   * @covers ::setClass
   * @covers ::getOriginalClass
   */
  public function testGetOriginalClassChanged(): void {
    $class = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(['class' => $class]);
    $entity_type->setClass($this->randomMachineName());
    $this->assertEquals($class, $entity_type->getOriginalClass());
  }

  /**
   * @covers ::id
   */
  public function testId(): void {
    $id = $this->randomMachineName(32);
    $entity_type = $this->setUpEntityType(['id' => $id]);
    $this->assertEquals($id, $entity_type->id());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel(): void {
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
  public function testGetGroupLabel(): void {
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
  public function testGetCollectionLabel(): void {
    $translatable_label = new TranslatableMarkup('Entity test collection', [], [], $this->getStringTranslationStub());
    $entity_type = $this->setUpEntityType(['label_collection' => $translatable_label]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('Entity test collection', $entity_type->getCollectionLabel());
  }

  /**
   * @covers ::getSingularLabel
   */
  public function testGetSingularLabel(): void {
    $translatable_label = new TranslatableMarkup('entity test singular', [], [], $this->getStringTranslationStub());
    $entity_type = $this->setUpEntityType(['label_singular' => $translatable_label]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test singular', $entity_type->getSingularLabel());
  }

  /**
   * @covers ::getSingularLabel
   */
  public function testGetSingularLabelDefault(): void {
    $entity_type = $this->setUpEntityType(['label' => 'Entity test Singular']);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test singular', $entity_type->getSingularLabel());
  }

  /**
   * @covers ::getPluralLabel
   */
  public function testGetPluralLabel(): void {
    $translatable_label = new TranslatableMarkup('entity test plural', [], [], $this->getStringTranslationStub());
    $entity_type = $this->setUpEntityType(['label_plural' => $translatable_label]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test plural', $entity_type->getPluralLabel());
  }

  /**
   * @covers ::getPluralLabel
   */
  public function testGetPluralLabelDefault(): void {
    $entity_type = $this->setUpEntityType(['label' => 'Entity test Plural']);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('entity test plural entities', $entity_type->getPluralLabel());
  }

  /**
   * @covers ::getCountLabel
   */
  public function testGetCountLabel(): void {
    $entity_type = $this->setUpEntityType(['label_count' => ['singular' => 'one entity test', 'plural' => '@count entity test']]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('one entity test', $entity_type->getCountLabel(1));
    $this->assertEquals('2 entity test', $entity_type->getCountLabel(2));
    $this->assertEquals('200 entity test', $entity_type->getCountLabel(200));
    $this->assertArrayNotHasKey('context', $entity_type->getCountLabel(1)->getOptions());

    // Test a custom context.
    $entity_type = $this->setUpEntityType(['label_count' => ['singular' => 'one entity test', 'plural' => '@count entity test', 'context' => 'custom context']]);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertSame('custom context', $entity_type->getCountLabel(1)->getOption('context'));
  }

  /**
   * @covers ::getCountLabel
   */
  public function testGetCountLabelDefault(): void {
    $entity_type = $this->setUpEntityType(['label' => 'Entity test Plural']);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals('1 entity test plural', $entity_type->getCountLabel(1));
    $this->assertEquals('2 entity test plural entities', $entity_type->getCountLabel(2));
    $this->assertEquals('200 entity test plural entities', $entity_type->getCountLabel(200));
    $this->assertSame('Entity type label', $entity_type->getCountLabel(1)->getOption('context'));
  }

  /**
   * Tests the ::getBundleLabel() method.
   *
   * @covers ::getBundleLabel
   * @dataProvider providerTestGetBundleLabel
   */
  public function testGetBundleLabel($definition, $expected): void {
    $entity_type = $this->setUpEntityType($definition);
    $entity_type->setStringTranslation($this->getStringTranslationStub());
    $this->assertEquals($expected, $entity_type->getBundleLabel());
  }

  /**
   * Provides test data for ::testGetBundleLabel().
   */
  public static function providerTestGetBundleLabel() {
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
  public function testSetLinkTemplateWithInvalidPath(): void {
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
  public function testConstraintMethods(): void {
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
   *   The entity type.
   *
   * @internal
   */
  protected function assertNoPublicProperties(EntityTypeInterface $entity_type): void {
    $reflection = new \ReflectionObject($entity_type);
    $this->assertEmpty($reflection->getProperties(\ReflectionProperty::IS_PUBLIC));
  }

  /**
   * @covers ::entityClassImplements
   */
  public function testEntityClassImplements(): void {
    $entity_type = $this->setUpEntityType(['class' => EntityFormMode::class]);
    $this->assertTrue($entity_type->entityClassImplements(ConfigEntityInterface::class));
    $this->assertFalse($entity_type->entityClassImplements(\DateTimeInterface::class));
  }

}
