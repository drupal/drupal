<?php

namespace Drupal\Tests\hal\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Normalizer- and denormalizeException unittest base class.
 *
 * Common ancestor for FieldItemNormalizerDenormalizeExceptionsUnitTest and
 * FieldNormalizerDenormalizeExceptionsUnitTest as they have the same
 * dataProvider.
 */
abstract class NormalizerDenormalizeExceptionsUnitTestBase extends UnitTestCase {

  /**
   * Provides data for both exceptions.
   *
   * - FieldItemNormalizerDenormalizeExceptionsUnitTest::testFieldItemNormalizerDenormalizeExceptions()
   * = FieldNormalizerDenormalizeExceptionsUnitTest::testFieldNormalizerDenormalizeExceptions()
   *
   * @return array Test data.
   */
  public function providerNormalizerDenormalizeExceptions() {
    $mock = $this->getMockBuilder('\Drupal\Core\Field\Plugin\DataType\FieldItem')
      ->setMethods(['getParent'])
      ->getMock();
    $mock->expects($this->any())
      ->method('getParent')
      ->will($this->returnValue(NULL));
    return [
      [[]],
      [['target_instance' => $mock]],
    ];
  }

}
