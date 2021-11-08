<?php

namespace Drupal\Tests\Core\TypedData;

use Drupal\Core\TypedData\Validation\ConstraintViolationBuilder;
use Drupal\Core\TypedData\Validation\ExecutionContext;
use Drupal\Core\TypedData\Validation\ExecutionContextFactory;
use Drupal\Core\Validation\TranslatorInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Test the deprecation of TypedData classes.
 * @group typedData
 * @group legacy
 */
class ValidationLegacyTest extends UnitTestCase {

  /**
   * @covers Drupal\Core\TypedData\Validation\ConstraintViolationBuilder::__construct
   */
  public function testConstraintViolationBuilderDeprecated() {
    $this->expectDeprecation("Drupal\Core\TypedData\Validation\ConstraintViolationBuilder is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class \Symfony\Component\Validator\Violation\ConstraintViolationBuilder instead. See https://www.drupal.org/node/3238432");
    $constraint_violation_list = $this->prophesize(ConstraintViolationList::class)->reveal();
    $constraint = $this->prophesize(Constraint::class)->reveal();
    $translator = $this->prophesize(TranslatorInterface::class)->reveal();
    $this->assertInstanceOf(ConstraintViolationBuilder::class, new ConstraintViolationBuilder($constraint_violation_list, $constraint, '', [], '', '', '', $translator));
  }

  /**
   * @covers Drupal\Core\TypedData\Validation\ExecutionContext::__construct
   */
  public function testExecutionContextDeprecated() {
    $this->expectDeprecation("Drupal\Core\TypedData\Validation\ExecutionContext is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class \Symfony\Component\Validator\Context\ExecutionContext instead. See https://www.drupal.org/node/3238432");
    $validator = $this->prophesize(ValidatorInterface::class)->reveal();
    $translator = $this->prophesize(TranslatorInterface::class)->reveal();
    $this->assertInstanceOf(ExecutionContext::class, new ExecutionContext($validator, '', $translator));
  }

  /**
   * @covers Drupal\Core\TypedData\Validation\ExecutionContextFactory::__construct
   */
  public function testExecutionContextFactoryDeprecated() {
    $this->expectDeprecation("Drupal\Core\TypedData\Validation\ExecutionContextFactory is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use the class \Symfony\Component\Validator\Context\ExecutionContextFactory instead. See https://www.drupal.org/node/3238432");
    $translator = $this->prophesize(TranslatorInterface::class)->reveal();
    $this->assertInstanceOf(ExecutionContextFactory::class, new ExecutionContextFactory($translator));
  }

}
