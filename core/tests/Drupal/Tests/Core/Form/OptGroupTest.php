<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Form\OptGroup;

/**
 * @coversDefaultClass \Drupal\Core\Form\OptGroup
 * @group Form
 */
class OptGroupTest extends UnitTestCase {

  /**
   * Tests the flattenOptions() method.
   *
   * @dataProvider providerTestFlattenOptions
   */
  public function testFlattenOptions($options) {
    $this->assertSame(array('foo' => 'foo'), OptGroup::flattenOptions($options));
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
    $object3 = new \stdClass();
    return array(
      array(array('foo' => 'foo')),
      array(array(array('foo' => 'foo'))),
      array(array($object1)),
      array(array($object2)),
      array(array($object1, $object2)),
      array(array('foo' => $object3, $object1, array('foo' => 'foo'))),
    );
  }

}
