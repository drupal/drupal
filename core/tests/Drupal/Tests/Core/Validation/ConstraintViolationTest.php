<?php

namespace Drupal\Tests\Core\Validation;

use Drupal\Core\Render\Markup;
use Drupal\Core\Validation\ConstraintViolation;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ConstraintViolation class.
 *
 * @group Validation
 *
 * @coversDefaultClass \Drupal\Core\Validation\ConstraintViolation
 */
class ConstraintViolationTest extends UnitTestCase {

  /**
   * Tests that the getMessage method returns the original markup.
   *
   * @covers ::getMessage
   */
  public function testGetMessage() {
    $markup = Markup::create($this->getRandomGenerator()->string(32));
    $violation = new ConstraintViolation($markup, (string) $markup, [], 'TestClass', 'test', 0);
    $this->assertSame($markup, $violation->getMessage());
  }

}
