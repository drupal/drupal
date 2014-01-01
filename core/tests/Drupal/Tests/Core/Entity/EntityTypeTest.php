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

}
