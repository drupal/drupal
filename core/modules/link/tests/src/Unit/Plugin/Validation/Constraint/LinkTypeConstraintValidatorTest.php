<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Unit\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Validation\Constraint\LinkTypeConstraint;
use Drupal\link\Plugin\Validation\Constraint\LinkTypeConstraintValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests LinkTypeConstraintValidator.
 */
#[CoversMethod(LinkTypeConstraintValidator::class, 'validate')]
#[Group('link')]
class LinkTypeConstraintValidatorTest extends UnitTestCase {

  /**
   * Validate a good internal link.
   */
  public function testInternal(): void {
    $url = Url::fromRoute('example.existing_route');

    $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->with('example.existing_route', [], [])
      ->willReturn('/example/existing');
    $url->setUrlGenerator($urlGenerator);

    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_INTERNAL));
    $link->expects($this->once())
      ->method('getUrl')
      ->willReturn($url);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');

    $this->doValidate($link, $context);
  }

  /**
   * Validate an external link in an internal-only field.
   */
  public function testBadInternal(): void {
    $url = Url::fromUri('https://www.drupal.org');

    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_INTERNAL));
    $link->expects($this->once())
      ->method('getUrl')
      ->willReturn($url);

    $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    $constraintViolationBuilder->method('atPath')
      ->with('uri')
      ->willReturn($constraintViolationBuilder);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->once())
      ->method('buildViolation')
      ->willReturn($constraintViolationBuilder);

    $this->doValidate($link, $context);
  }

  /**
   * Validate a good external link.
   */
  public function testExternal(): void {
    $url = Url::fromUri('https://www.drupal.org');

    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_EXTERNAL));
    $link->expects($this->once())
      ->method('getUrl')
      ->willReturn($url);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');

    $this->doValidate($link, $context);
  }

  /**
   * Validate an internal link in an external-only field.
   */
  public function testBadExternal(): void {
    $url = Url::fromRoute('example.existing_route');

    $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->with('example.existing_route', [], [])
      ->willReturn('/example/existing');
    $url->setUrlGenerator($urlGenerator);

    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_EXTERNAL));
    $link->expects($this->any())
      ->method('getUrl')
      ->willReturn($url);

    $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    $constraintViolationBuilder->method('atPath')
      ->with('uri')
      ->willReturn($constraintViolationBuilder);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->once())
      ->method('buildViolation');

    $this->doValidate($link, $context);
  }

  /**
   * Validate a URL that throws an exception.
   */
  public function testBadUrl(): void {
    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_INTERNAL));
    $link->expects($this->once())
      ->method('getUrl')
      ->willThrowException(new \InvalidArgumentException());

    $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    $constraintViolationBuilder->method('atPath')
      ->with('uri')
      ->willReturn($constraintViolationBuilder);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->once())
      ->method('buildViolation')
      ->willReturn($constraintViolationBuilder);

    $this->doValidate($link, $context);
  }

  /**
   * Validate a URL in a field that accepts both internal and external URLs.
   */
  public function testGeneric(): void {
    $url = Url::fromRoute('example.existing_route');

    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->once())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_GENERIC));
    $link->expects($this->once())
      ->method('getUrl')
      ->willReturn($url);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');

    $this->doValidate($link, $context);
  }

  /**
   * Tests validating a value that isn't a LinkItemInterface.
   */
  public function testUnexpectedValue(): void {
    $this->expectException(UnexpectedValueException::class);
    $context = $this->createMock(ExecutionContextInterface::class);
    $this->doValidate('bad value', $context);
  }

  /**
   * Tests validating an empty Link field.
   */
  public function testEmptyField(): void {
    $link = $this->createMock(LinkItemInterface::class);
    $link->expects($this->any())
      ->method('getFieldDefinition')
      ->willReturn($this->getMockFieldDefinition(LinkItemInterface::LINK_INTERNAL));
    $link->expects($this->once())
      ->method('isEmpty')
      ->willReturn(TRUE);
    $link->expects($this->never())
      ->method('getUrl');

    $context = $this->createMock(ExecutionContextInterface::class);
    $this->doValidate($link, $context);
  }

  /**
   * Validate the link.
   *
   * @param mixed $link
   *   A field value to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface&\PHPUnit\Framework\MockObject\MockObject $context
   *   The execution context.
   */
  protected function doValidate($link, ExecutionContextInterface&MockObject $context): void {
    $validator = new LinkTypeConstraintValidator();
    $validator->initialize($context);
    $validator->validate($link, new LinkTypeConstraint());
  }

  /**
   * Builds a mock Link field definition.
   *
   * @param int $type
   *   The type of Link field as defined in LinkItemInterface.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The mock field definition.
   */
  protected function getMockFieldDefinition(int $type): FieldDefinitionInterface {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->expects($this->any())
      ->method('getSetting')
      ->with('link_type')
      ->willReturn($type);
    return $definition;
  }

}
