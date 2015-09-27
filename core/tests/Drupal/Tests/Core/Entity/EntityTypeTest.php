<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityTypeTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableString;
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
    $definition += array(
      'id' => 'example_entity_type',
    );
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
    $entity_type = $this->setUpEntityType(array('entity_keys' => $entity_keys));
    $this->assertSame($expected + ['default_langcode' => 'default_langcode'], $entity_type->getKeys());
  }

  /**
   * Tests the getKey() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testGetKey($entity_keys, $expected) {
    $entity_type = $this->setUpEntityType(array('entity_keys' => $entity_keys));
    $this->assertSame($expected['bundle'], $entity_type->getKey('bundle'));
    $this->assertSame(FALSE, $entity_type->getKey('bananas'));
  }

  /**
   * Tests the hasKey() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testHasKey($entity_keys, $expected) {
    $entity_type = $this->setUpEntityType(array('entity_keys' => $entity_keys));
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
    return array(
      array(array(), array('revision' => '', 'bundle' => '', 'langcode' => '')),
      array(array('id' => 'id'), array('id' => 'id', 'revision' => '', 'bundle' => '', 'langcode' => '')),
      array(array('bundle' => 'bundle'), array('bundle' => 'bundle', 'revision' => '', 'langcode' => '')),
    );
  }

  /**
   * Tests the isRevisionable() method.
   */
  public function testIsRevisionable() {
    $entity_type = $this->setUpEntityType(array('entity_keys' => array('id' => 'id')));
    $this->assertFalse($entity_type->isRevisionable());
    $entity_type = $this->setUpEntityType(array('entity_keys' => array('id' => 'id', 'revision' => FALSE)));
    $this->assertFalse($entity_type->isRevisionable());
    $entity_type = $this->setUpEntityType(array('entity_keys' => array('id' => 'id', 'revision' => TRUE)));
    $this->assertTrue($entity_type->isRevisionable());
  }

  /**
   * Tests the getHandler() method.
   */
  public function testGetHandler() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType(array(
      'handlers' => array(
        'storage' => $controller,
        'form' => array(
          'default' => $controller,
        ),
      ),
    ));
    $this->assertSame($controller, $entity_type->getHandlerClass('storage'));
    $this->assertSame($controller, $entity_type->getHandlerClass('form', 'default'));
  }

  /**
   * Tests the getStorageClass() method.
   */
  public function testGetStorageClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType(array(
      'handlers' => array(
        'storage' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getStorageClass());
  }

  /**
   * Tests the getListBuilderClass() method.
   */
  public function testGetListBuilderClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType(array(
      'handlers' => array(
        'list_builder' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getListBuilderClass());
  }

  /**
   * Tests the getAccessControlClass() method.
   */
  public function testGetAccessControlClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType(array(
      'handlers' => array(
        'access' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getAccessControlClass());
  }

  /**
   * Tests the getFormClass() method.
   */
  public function testGetFormClass() {
    $controller = $this->getTestHandlerClass();
    $operation = 'default';
    $entity_type = $this->setUpEntityType(array(
      'handlers' => array(
        'form' => array(
          $operation => $controller,
        ),
      ),
    ));
    $this->assertSame($controller, $entity_type->getFormClass($operation));
  }

  /**
   * Tests the hasFormClasses() method.
   */
  public function testHasFormClasses() {
    $controller = $this->getTestHandlerClass();
    $operation = 'default';
    $entity_type1 = $this->setUpEntityType(array(
      'handlers' => array(
        'form' => array(
          $operation => $controller,
        ),
      ),
    ));
    $entity_type2 = $this->setUpEntityType(array(
      'handlers' => array(),
    ));
    $this->assertTrue($entity_type1->hasFormClasses());
    $this->assertFalse($entity_type2->hasFormClasses());
  }

  /**
   * Tests the getViewBuilderClass() method.
   */
  public function testGetViewBuilderClass() {
    $controller = $this->getTestHandlerClass();
    $entity_type = $this->setUpEntityType(array(
      'handlers' => array(
        'view_builder' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getViewBuilderClass());
  }

  /**
   * @covers ::__construct
   */
  public function testIdExceedsMaxLength() {
    $id = $this->randomMachineName(33);
    $message = 'Attempt to create an entity type with an ID longer than 32 characters: ' . $id;
    $this->setExpectedException('Drupal\Core\Entity\Exception\EntityTypeIdLengthException', $message);
    $this->setUpEntityType(array('id' => $id));
  }

  /**
   * @covers ::getOriginalClass
   */
  public function testgetOriginalClassUnchanged() {
    $class = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(array('class' => $class));
    $this->assertEquals($class, $entity_type->getOriginalClass());
  }

  /**
   * @covers ::setClass
   * @covers ::getOriginalClass
   */
  public function testgetOriginalClassChanged() {
    $class = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(array('class' => $class));
    $entity_type->setClass($this->randomMachineName());
    $this->assertEquals($class, $entity_type->getOriginalClass());
  }

  /**
   * @covers ::id
   */
  public function testId() {
    $id = $this->randomMachineName(32);
    $entity_type = $this->setUpEntityType(array('id' => $id));
    $this->assertEquals($id, $entity_type->id());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $translatable_label = new TranslatableString($this->randomMachineName());
    $entity_type = $this->setUpEntityType(array('label' => $translatable_label));
    $this->assertSame($translatable_label, $entity_type->getLabel());

    $label = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(array('label' => $label));
    $this->assertSame($label, $entity_type->getLabel());
  }

  /**
   * @covers ::getGroupLabel
   */
  public function testGetGroupLabel() {
    $translatable_group_label = new TranslatableString($this->randomMachineName());
    $entity_type = $this->setUpEntityType(array('group_label' => $translatable_group_label));
    $this->assertSame($translatable_group_label, $entity_type->getGroupLabel());

    $default_label = $this->randomMachineName();
    $entity_type = $this->setUpEntityType(array('group_label' => $default_label));
    $this->assertSame($default_label, $entity_type->getGroupLabel());

    $default_label = new TranslatableString('Other', array(), array('context' => 'Entity type group'));
    $entity_type = $this->setUpEntityType([]);

    $string_translation = $this->getMock(TranslationInterface::class);
    $string_translation->expects($this->atLeastOnce())
      ->method('translate')
      ->with('Other', array(), array('context' => 'Entity type group'))
      ->willReturn($default_label);
    $entity_type->setStringTranslation($string_translation);

    $this->assertSame($default_label, $entity_type->getGroupLabel());
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
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetLinkTemplateWithInvalidPath() {
    $entity_type = $this->setUpEntityType(['id' => $this->randomMachineName()]);
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

}
