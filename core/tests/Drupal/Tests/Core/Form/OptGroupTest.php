<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\OptGroupTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Form\OptGroup;

/**
 * Tests the OptGroup class.
 *
 * @coversDefaultClass \Drupal\Core\Form\OptGroup
 *
 * @group Drupal
 * @group Form
 */
class OptGroupTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'OptGroup test',
      'description' => 'Tests the OptGroup class.',
      'group' => 'Form API',
    );
  }

  /**
   * Tests the flattenOptions() method.
   *
   * @dataProvider providerTestFlattenOptions
   */
  public function testFlattenOptions($options) {
    $this->assertSame(array('foo' => 1), OptGroup::flattenOptions($options));
  }

  /**
   * Provides test data for the flattenOptions() method.
   *
   * @return array
   */
  public function providerTestFlattenOptions() {
    $object1 = new \stdClass();
    $object1->option = array('foo' => 'foo');
    $object2 = new \stdClass();
    $object2->option = array(array('foo' => 'foo'), array('foo' => 'foo'));
    return array(
      array(array('foo' => 'foo')),
      array(array(array('foo' => 'foo'))),
      array(array($object1)),
      array(array($object2)),
    );
  }

}
