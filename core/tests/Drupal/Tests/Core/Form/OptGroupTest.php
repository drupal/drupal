<?php

declare(strict_types=1);

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
  public function testFlattenOptions($options): void {
    $this->assertSame(['foo' => 'foo'], OptGroup::flattenOptions($options));
  }

  /**
   * Provides test data for the flattenOptions() method.
   *
   * @return array
   */
  public static function providerTestFlattenOptions() {
    $object1 = new \stdClass();
    $object1->option = ['foo' => 'foo'];
    $object2 = new \stdClass();
    $object2->option = [['foo' => 'foo'], ['foo' => 'foo']];
    $object3 = new \stdClass();
    return [
      [['foo' => 'foo']],
      [[['foo' => 'foo']]],
      [[$object1]],
      [[$object2]],
      [[$object1, $object2]],
      [['foo' => $object3, $object1, ['foo' => 'foo']]],
    ];
  }

}
