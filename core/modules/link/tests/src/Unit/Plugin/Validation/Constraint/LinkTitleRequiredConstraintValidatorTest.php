<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Unit\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\LinkTitleVisibility;
use Drupal\link\Plugin\Validation\Constraint\LinkTitleRequiredConstraint;
use Drupal\link\Plugin\Validation\Constraint\LinkTitleRequiredConstraintValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests LinkTitleRequiredConstraintValidator::validate().
 */
#[CoversMethod(LinkTitleRequiredConstraintValidator::class, 'validate')]
#[Group('Link')]
class LinkTitleRequiredConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests validating a value that isn't a LinkItemInterface.
   */
  public function testUnexpectedValue(): void {
    $this->expectException(UnexpectedValueException::class);
    $context = $this->createMock(ExecutionContextInterface::class);
    $this->doValidate('bad value', $context);
  }

  /**
   * Tests passing a value with a non-required title.
   */
  #[TestWith([LinkTitleVisibility::Disabled->value])]
  #[TestWith([LinkTitleVisibility::Optional->value])]
  public function testTitleNotRequired(int $visibility): void {
    $link = $this->getMockLink($visibility);
    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');
    $this->doValidate($link, $context);
  }

  /**
   * Tests passing a value with an empty URI.
   */
  public function testEmptyUri(): void {
    $link = $this->getMockLink(LinkTitleVisibility::Required->value);
    $link->expects($this->once())
      ->method('__get')
      ->with('uri')
      ->willReturn('');
    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');
    $this->doValidate($link, $context);
  }

  /**
   * Tests the failure case of a value with a non-empty URI and an empty title.
   */
  public function testEmptyTitle(): void {
    $link = $this->getMockLink(LinkTitleVisibility::Required->value);
    $link->expects($this->exactly(2))
      ->method('__get')
      ->willReturnMap([
        ['uri', Url::fromUri('https://example.com')],
        ['title', ''],
      ]);
    $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    $constraintViolationBuilder->method('atPath')
      ->with('title')
      ->willReturn($constraintViolationBuilder);
    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->once())
      ->method('buildViolation')
      ->willReturn($constraintViolationBuilder);
    $this->doValidate($link, $context);
  }

  /**
   * Validate the field value.
   *
   * @param mixed $value
   *   A link field value.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface&\PHPUnit\Framework\MockObject\MockObject $context
   *   The validation context.
   */
  protected function doValidate($value, ExecutionContextInterface&MockObject $context): void {
    $validator = new LinkTitleRequiredConstraintValidator();
    $validator->initialize($context);
    $validator->validate($value, new LinkTitleRequiredConstraint());
  }

  /**
   * Builds a mock Link field.
   *
   * @param int $visibility
   *   The visibility of the Link title field as defined in LinkTitleVisibility.
   *
   * @return \Drupal\link\LinkItemInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The mock LinkItemInterface field item.
   */
  protected function getMockLink(int $visibility): LinkItemInterface&MockObject {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->expects($this->once())
      ->method('getSetting')
      ->with('title')
      ->willReturn($visibility);
    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($definition);
    return $link;
  }

}
