<?php

/**
 * @file
 * Contains \Drupal\Tests\serialization\Unit\Encoder\JsonEncoderTest.
 */

namespace Drupal\Tests\serialization\Unit\Encoder;

use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\serialization\Encoder\JsonEncoder
 * @group serialization
 */
class JsonEncoderTest extends UnitTestCase {

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
