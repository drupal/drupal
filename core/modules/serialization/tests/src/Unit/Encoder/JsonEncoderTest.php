<?php

declare(strict_types=1);

namespace Drupal\Tests\serialization\Unit\Encoder;

use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\serialization\Encoder\JsonEncoder.
 */
#[CoversClass(JsonEncoder::class)]
#[Group('serialization')]
class JsonEncoderTest extends UnitTestCase {

  /**
   * Tests the supportsEncoding() method.
   */
  public function testSupportsEncoding(): void {
    $encoder = new JsonEncoder();

    $this->assertTrue($encoder->supportsEncoding('json'));
    $this->assertTrue($encoder->supportsEncoding('ajax'));
    $this->assertFalse($encoder->supportsEncoding('xml'));
  }

}
