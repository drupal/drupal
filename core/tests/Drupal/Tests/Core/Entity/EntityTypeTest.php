<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityTypeTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the \Drupal\Core\Entity\EntityType class.
 *
 * @group Drupal
 * @group Entity
 */
class EntityTypeTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Entity type test',
      'description' => 'Unit test entity type info.',
      'group' => 'Entity',
    );
  }

  /**
   * Sets up an EntityType object for a given set of values.
   *
   * @param array $definition
   *   An array of values to use for the EntityType.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  protected function setUpEntityType($definition) {
    return new EntityType($definition);
  }

  /**
   * Tests the getKeys() method.
   *
   * @dataProvider providerTestGetKeys
   */
  public function testGetKeys($entity_keys, $expected) {
    $entity_type = $this->setUpEntityType(array('entity_keys' => $entity_keys));
    $this->assertSame($expected, $entity_type->getKeys());
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
   * Provides test data.
   */
  public function providerTestGetKeys() {
    return array(
      array(array(), array('revision' => '', 'bundle' => '')),
      array(array('id' => 'id'), array('id' => 'id', 'revision' => '', 'bundle' => '')),
      array(array('bundle' => 'bundle'), array('bundle' => 'bundle', 'revision' => '')),
    );
  }

  /**
   * Tests the getController() method.
   */
  public function testGetController() {
    $controller = $this->getTestControllerClass();
    $entity_type = $this->setUpEntityType(array(
      'controllers' => array(
        'storage' => $controller,
        'form' => array(
          'default' => $controller,
        ),
      ),
    ));
    $this->assertSame($controller, $entity_type->getControllerClass('storage'));
    $this->assertSame($controller, $entity_type->getControllerClass('form', 'default'));
  }

  /**
   * Tests the getStorageClass() method.
   */
  public function testGetStorageClass() {
    $controller = $this->getTestControllerClass();
    $entity_type = $this->setUpEntityType(array(
      'controllers' => array(
        'storage' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getStorageClass());
  }

  /**
   * Tests the getListClass() method.
   */
  public function testGetListClass() {
    $controller = $this->getTestControllerClass();
    $entity_type = $this->setUpEntityType(array(
      'controllers' => array(
        'list' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getListClass());
  }

  /**
   * Tests the getAccessClass() method.
   */
  public function testGetAccessClass() {
    $controller = $this->getTestControllerClass();
    $entity_type = $this->setUpEntityType(array(
      'controllers' => array(
        'access' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getAccessClass());
  }

  /**
   * Tests the getFormClass() method.
   */
  public function testGetFormClass() {
    $controller = $this->getTestControllerClass();
    $operation = 'default';
    $entity_type = $this->setUpEntityType(array(
      'controllers' => array(
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
    $controller = $this->getTestControllerClass();
    $operation = 'default';
    $entity_type1 = $this->setUpEntityType(array(
      'controllers' => array(
        'form' => array(
          $operation => $controller,
        ),
      ),
    ));
    $entity_type2 = $this->setUpEntityType(array(
      'controllers' => array(),
    ));
    $this->assertTrue($entity_type1->hasFormClasses());
    $this->assertFalse($entity_type2->hasFormClasses());
  }

  /**
   * Tests the getViewBuilderClass() method.
   */
  public function testGetViewBuilderClass() {
    $controller = $this->getTestControllerClass();
    $entity_type = $this->setUpEntityType(array(
      'controllers' => array(
        'view_builder' => $controller,
      ),
    ));
    $this->assertSame($controller, $entity_type->getViewBuilderClass());
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestControllerClass() {
    return get_class($this->getMockForAbstractClass('Drupal\Core\Entity\EntityControllerBase'));
  }

}
