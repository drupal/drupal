<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidatorTest.
 */

namespace Drupal\Tests\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator
 * @group validation
 */
class PrimitiveTypeConstraintValidatorTest extends UnitTestCase {

  /**
   * @covers ::validate
   *
   * @dataProvider provideTestValidate
   */
  public function testValidate(PrimitiveInterface $typed_data, $value, $valid) {
    $metadata = $this->getMockBuilder('Drupal\Core\TypedData\Validation\Metadata')
      ->disableOriginalConstructor()
      ->getMock();
    $metadata->expects($this->any())
      ->method('getTypedData')
      ->willReturn($typed_data);

    $context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
    $context->expects($this->any())
      ->method('getMetadata')
      ->willReturn($metadata);

    if ($valid) {
      $context->expects($this->never())
        ->method('addViolation');
    }
    else {
      $context->expects($this->once())
        ->method('addViolation');
    }

    $constraint = new PrimitiveTypeConstraint();

    $validate = new PrimitiveTypeConstraintValidator();
    $validate->initialize($context);
    $validate->validate($value, $constraint);
  }

  public function provideTestValidate() {
    $data = [];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\BooleanInterface'), NULL, TRUE];

    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\BooleanInterface'), 1, TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\BooleanInterface'), 'test', FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\FloatInterface'), 1.5, TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\FloatInterface'), 'test', FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\IntegerInterface'), 1, TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\IntegerInterface'), 1.5, FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\IntegerInterface'), 'test', FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\StringInterface'), 'test', TRUE];
    // It is odd that 1 is a valid string.
    // $data[] = [$this->getMock('Drupal\Core\TypedData\Type\StringInterface'), 1, FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\StringInterface'), [], FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'http://www.drupal.org', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'https://www.drupal.org', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'Invalid', FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'entity:node/1', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'base:', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'base:node', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'user-path:', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'public://', FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'public://foo.png', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'private://', FALSE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'private://foo.png', TRUE];
    $data[] = [$this->getMock('Drupal\Core\TypedData\Type\UriInterface'), 'drupal.org', FALSE];

    return $data;
  }

}
