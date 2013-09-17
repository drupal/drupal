<?php

/**
 * @file
 * Contains \Drupal\serialization\Tests\Encoder\JsonEncoderTest.
 */

namespace Drupal\serialization\Tests\Encoder;

use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the JsonEncoder class.
 *
 * @see \Drupal\serialization\Encoder\JsonEncoder
 */
class JsonEncoderTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'JsonEncoderTest',
      'description' => 'Tests the JsonEncoder class.',
      'group' => 'Serialization',
    );
  }

  /**
   * Tests the supportsEncoding() method.
   */
  public function testSupportsEncoding() {
    $encoder = new JsonEncoder();

    $this->assertTrue($encoder->supportsEncoding('json'));
    $this->assertTrue($encoder->supportsEncoding('ajax'));
    $this->assertFalse($encoder->supportsEncoding('xml'));
  }

}
