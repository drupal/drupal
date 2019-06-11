<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use PHPUnit\Framework\TestCase;

/**
 * Tests the EmailValidator utility class.
 *
 * @coversDefaultClass \Drupal\Component\Utility\EmailValidator
 * @group Utility
 */
class EmailValidatorTest extends TestCase {

  /**
   * @covers ::isValid
   */
  public function testIsValid() {
    // Note that \Drupal\Component\Utility\EmailValidator wraps
    // \Egulias\EmailValidator\EmailValidator so we don't do anything more than
    // test that the wrapping works since the dependency has its own test
    // coverage.
    $validator = new EmailValidator();
    $this->assertTrue($validator->isValid('example@example.com'));
    $this->assertFalse($validator->isValid('example@example.com@'));
  }

  /**
   * @covers ::isValid
   */
  public function testIsValidException() {
    $validator = new EmailValidator();
    if (method_exists($this, 'expectException')) {
      $this->expectException(\BadMethodCallException::class);
      $this->expectExceptionMessage('Calling \Drupal\Component\Utility\EmailValidator::isValid() with the second argument is not supported. See https://www.drupal.org/node/2997196');
    }
    else {
      $this->expectException(\BadMethodCallException::class);
      $this->expectExceptionMessage('Calling \Drupal\Component\Utility\EmailValidator::isValid() with the second argument is not supported. See https://www.drupal.org/node/2997196');
    }
    $validator->isValid('example@example.com', (new RFCValidation()));
  }

}
