<?php

namespace Drupal\Tests\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\FloatData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @coversDefaultClass \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator
 * @group validation
 */
class PrimitiveTypeConstraintValidatorTest extends UnitTestCase {

  /**
   * @covers ::validate
   *
   * @dataProvider provideTestValidate
   */
  public function testValidate(PrimitiveInterface $typed_data, $value, $valid) {
    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->any())
      ->method('getObject')
      ->willReturn($typed_data);

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
    $data[] = [new BooleanData(DataDefinition::create('boolean')), NULL, TRUE];

    $data[] = [new BooleanData(DataDefinition::create('boolean')), 1, TRUE];
    $data[] = [new BooleanData(DataDefinition::create('boolean')), 'test', FALSE];
    $data[] = [new FloatData(DataDefinition::create('float')), 1.5, TRUE];
    $data[] = [new FloatData(DataDefinition::create('float')), 'test', FALSE];
    $data[] = [new IntegerData(DataDefinition::create('integer')), 1, TRUE];
    $data[] = [new IntegerData(DataDefinition::create('integer')), 1.5, FALSE];
    $data[] = [new IntegerData(DataDefinition::create('integer')), 'test', FALSE];
    $data[] = [new StringData(DataDefinition::create('string')), 'test', TRUE];
    $data[] = [new StringData(DataDefinition::create('string')), new TranslatableMarkup('test'), TRUE];
    // It is odd that 1 is a valid string.
    // $data[] = [$this->createMock('Drupal\Core\TypedData\Type\StringInterface'), 1, FALSE];
    $data[] = [new StringData(DataDefinition::create('string')), [], FALSE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'http://www.example.com', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'https://www.example.com', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'Invalid', FALSE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'entity:node/1', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'base:', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'base:node', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'internal:', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'public://', FALSE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'public://foo.png', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'private://', FALSE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'private://foo.png', TRUE];
    $data[] = [new Uri(DataDefinition::create('uri')), 'example.com', FALSE];

    return $data;
  }

}
